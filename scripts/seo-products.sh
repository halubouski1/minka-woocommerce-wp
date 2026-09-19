#!/usr/bin/env bash
# Заполняет базовые SEO и Open Graph у карточек товара.
#
#   --dry-run   показать, ничего не сохраняя
#   --force     перезаписать даже там, где SEO уже заполнено вручную
set -euo pipefail

cd "$(dirname "$0")/.."

SERVICE=wordpress

if ! docker compose ps --status running --services | grep -qx "$SERVICE"; then
  echo "Сервис '$SERVICE' не запущен. Сначала: docker compose up -d" >&2
  exit 1
fi

docker compose cp scripts/seo-products.php "$SERVICE:/tmp/seo-products.php" >/dev/null

trap 'docker compose exec -T "$SERVICE" rm -f /tmp/seo-products.php >/dev/null 2>&1 || true' EXIT

docker compose exec -T "$SERVICE" php /tmp/seo-products.php "$@"
