#!/usr/bin/env bash
# Синхронизирует формы Gravity Forms с minka_form_definitions().
# Запуск с --dry-run показывает изменения, ничего не сохраняя.
set -euo pipefail

cd "$(dirname "$0")/.."

SERVICE=wordpress

if ! docker compose ps --status running --services | grep -qx "$SERVICE"; then
  echo "Сервис '$SERVICE' не запущен. Сначала: docker compose up -d" >&2
  exit 1
fi

docker compose cp scripts/sync-gf-forms.php "$SERVICE:/tmp/sync-gf-forms.php" >/dev/null

trap 'docker compose exec -T "$SERVICE" rm -f /tmp/sync-gf-forms.php >/dev/null 2>&1 || true' EXIT

docker compose exec -T "$SERVICE" php /tmp/sync-gf-forms.php "$@"
