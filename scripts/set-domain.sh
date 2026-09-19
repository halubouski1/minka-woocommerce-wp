#!/usr/bin/env bash
# Переводит сайт на другой домен.
#
#   ./scripts/set-domain.sh --dry-run                  показать, что изменится
#   ./scripts/set-domain.sh                            перевести на боевой домен
#   ./scripts/set-domain.sh --back                     вернуть на локальный
#
# Меняет адреса в базе (через WP-CLI, чтобы не поломать сериализованные
# значения) и адреса CRM в .env. Сторона EspoCRM переключается своим скриптом
# в соседнем проекте: ../espo-minka-crm/scripts/set-domain.sh
set -euo pipefail

cd "$(dirname "$0")/.."

SITE_PROD="https://minka-furs.com"
CRM_PROD="https://crm.minka-furs.com"
SITE_LOCAL="http://127.0.0.1:9000"
CRM_LOCAL="http://localhost:8080"
CRM_API_LOCAL="http://host.docker.internal:8080/api/v1"

DRY=""
FROM="$SITE_LOCAL"; TO="$SITE_PROD"
CRM_UI="$CRM_PROD"; CRM_API="$CRM_PROD/api/v1"

for arg in "$@"; do
  case "$arg" in
    --dry-run) DRY="--dry-run" ;;
    --back)
      FROM="$SITE_PROD"; TO="$SITE_LOCAL"
      CRM_UI="$CRM_LOCAL"; CRM_API="$CRM_API_LOCAL"
      ;;
    *) echo "Неизвестный аргумент: $arg" >&2; exit 1 ;;
  esac
done

if ! docker compose ps --status running --services | grep -qx wordpress; then
  echo "Сервис 'wordpress' не запущен. Сначала: docker compose up -d" >&2
  exit 1
fi

echo "==> Адреса сайта: $FROM → $TO"

# Замену делает WP-CLI: он умеет разбирать сериализованные массивы, а простой
# SQL-заменой их длины «съезжают» и значения перестают читаться.
docker compose run --rm wpcli wp search-replace "$FROM" "$TO" \
  --all-tables-with-prefix --skip-columns=guid --report-changed-only $DRY

# guid менять нельзя: он служит постоянным идентификатором записи в RSS,
# и его правка ломает подписчиков. На работу сайта он не влияет.

if [ -n "$DRY" ]; then
  echo
  echo "==> В .env заменилось бы:"
  echo "    MINKA_ESPO_API_URL = $CRM_API"
  echo "    MINKA_ESPO_UI_URL  = $CRM_UI"
  echo
  echo "Пробный запуск, ничего не сохранено."
  exit 0
fi

echo "==> Обновляю адреса CRM в .env"
python3 - "$CRM_API" "$CRM_UI" <<'PY'
import re, sys
api, ui = sys.argv[1], sys.argv[2]
s = open('.env', encoding='utf-8').read()
s = re.sub(r'^MINKA_ESPO_API_URL=.*$', 'MINKA_ESPO_API_URL=' + api, s, flags=re.M)
s = re.sub(r'^MINKA_ESPO_UI_URL=.*$', 'MINKA_ESPO_UI_URL=' + ui, s, flags=re.M)
open('.env', 'w', encoding='utf-8').write(s)
PY

echo "==> Пересоздаю контейнер"
docker compose up -d >/dev/null

echo "==> Чищу кэш Yoast: адреса страниц он держит в своей таблице"
docker compose run --rm wpcli wp db query "DELETE FROM wp_yoast_indexable" 2>/dev/null || true

echo
echo "==> Готово. Осталось на стороне EspoCRM:"
echo "    cd ../espo-minka-crm && ./scripts/set-domain.sh"
