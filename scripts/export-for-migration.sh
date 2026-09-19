#!/usr/bin/env bash
# Готовит архив для переезда на хостинг.
#
# Без аргументов: база + только картинки (плагины ставятся заново).
#   --full     база + всё wp-content целиком, точная копия сайта.
#
# Полная копия удобнее, если не хочется доустанавливать плагины руками.
set -euo pipefail

cd "$(dirname "$0")/.."

OUT="migration"
STAMP=$(date +%Y%m%d-%H%M)

if ! docker compose ps --status running --services | grep -qx wordpress; then
  echo "Сервис 'wordpress' не запущен. Сначала: docker compose up -d" >&2
  exit 1
fi

mkdir -p "$OUT"

# --for-domain: перед снятием дампа адреса в базе переводятся на боевой домен,
# а сразу после — возвращаются обратно. Так на хостинг приезжает база, готовая
# к работе: заменять адреса там уже не нужно, а значит не нужен и доступ к
# командной строке сервера.
RESTORE=0
if [ "${2:-}" = "--for-domain" ] || [ "${1:-}" = "--for-domain" ]; then
  echo "==> Перевожу адреса на боевой домен"
  ./scripts/set-domain.sh >/dev/null
  RESTORE=1
  trap 'if [ "$RESTORE" = "1" ]; then echo "==> Возвращаю локальные адреса"; ./scripts/set-domain.sh --back >/dev/null; fi' EXIT
fi

echo "==> Дамп базы"
# --no-tablespaces: без него дамп требует прав, которых на shared-хостинге нет.
docker compose exec -T db mariadb-dump \
  -uwordpress -pwordpress \
  --no-tablespaces --default-character-set=utf8mb4 \
  --single-transaction --quick \
  wordpress > "$OUT/database-$STAMP.sql"

SIZE=$(du -h "$OUT/database-$STAMP.sql" | cut -f1)
echo "    $OUT/database-$STAMP.sql ($SIZE)"

if [ "${1:-}" = "--full" ]; then
  echo "==> Полная копия wp-content"
  # Всё целиком: картинки, плагины, темы, mu-plugins, переводы. Сайт на новом
  # месте получится точной копией — ничего доустанавливать не придётся.
  docker compose exec -T wordpress tar czf - \
    -C /var/www/html/wp-content \
    --exclude=cache \
    --exclude=upgrade \
    --exclude=ai1wm-backups \
    --exclude=uploads/wc-logs \
    --exclude=uploads/woocommerce_transient_files \
    . 2>/dev/null > "$OUT/wp-content-$STAMP.tar.gz"

  SIZE=$(du -h "$OUT/wp-content-$STAMP.tar.gz" | cut -f1)
  echo "    $OUT/wp-content-$STAMP.tar.gz ($SIZE)"
else
  echo "==> Загруженные файлы"
  # Только uploads: это фотографии и вложения, их взять больше неоткуда.
  # Плагины и переводы не кладём — они ставятся заново, а весят 230 МБ.
  docker compose exec -T wordpress tar czf - \
    -C /var/www/html/wp-content \
    --exclude='*.zip' \
    --exclude=uploads/wc-logs \
    --exclude=uploads/woocommerce_transient_files \
    uploads 2>/dev/null > "$OUT/uploads-$STAMP.tar.gz"

  SIZE=$(du -h "$OUT/uploads-$STAMP.tar.gz" | cut -f1)
  echo "    $OUT/uploads-$STAMP.tar.gz ($SIZE)"
fi

echo "==> Список активных плагинов"
docker compose run --rm wpcli wp plugin list --status=active --format=table > "$OUT/plugins-$STAMP.txt" 2>/dev/null
cat "$OUT/plugins-$STAMP.txt" | sed 's/^/    /'

cat > "$OUT/README-$STAMP.txt" <<TXT
Архив для переезда, собран $(date '+%d.%m.%Y %H:%M')

  database-$STAMP.sql    — база целиком
  uploads-$STAMP.tar.gz  — фотографии и вложения
  plugins-$STAMP.txt     — какие плагины были включены

В архив НЕ входят и заливаются отдельно:
  тема custom-theme и mu-plugins — из репозитория, они в git;
  плагины — ставятся заново на новом сайте (см. plugins-$STAMP.txt);
  переводы — WordPress скачает сам.

Порядок восстановления описан в ../espo-minka-crm/MIGRATION.md
TXT

echo
echo "==> Готово, папка $OUT/"
echo "    Внимание: дамп содержит ключи и пароли плагинов — не кладите его в git."
