#!/usr/bin/env bash
# Переносит title и description из исходных HTML-макетов в Yoast SEO.
# Запуск с --dry-run показывает, что будет записано, ничего не меняя.
set -euo pipefail

cd "$(dirname "$0")/.."

SERVICE=wordpress

if ! docker compose ps --status running --services | grep -qx "$SERVICE"; then
  echo "Сервис '$SERVICE' не запущен. Сначала: docker compose up -d" >&2
  exit 1
fi

STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"; docker compose exec -T "$SERVICE" rm -rf /tmp/minka-html /tmp/import-seo.php >/dev/null 2>&1 || true' EXIT

cp ./*.html "$STAGE/"

docker compose cp "$STAGE" "$SERVICE:/tmp/minka-html" >/dev/null
docker compose cp scripts/import-seo.php "$SERVICE:/tmp/import-seo.php" >/dev/null

docker compose exec -T "$SERVICE" php /tmp/import-seo.php "$@"
