#!/usr/bin/env bash
# Отправляет по одному тестовому событию каждого типа в Meta Conversions API.
# Ничего не создаёт и не меняет в CRM.
#
# Перед запуском заполните в .env: MINKA_META_PIXEL_ID, MINKA_META_CAPI_TOKEN,
# MINKA_META_TEST_EVENT_CODE — и выполните: docker compose up -d
set -euo pipefail

cd "$(dirname "$0")/.."

SERVICE=wordpress

if ! docker compose ps --status running --services | grep -qx "$SERVICE"; then
  echo "Сервис '$SERVICE' не запущен. Сначала: docker compose up -d" >&2
  exit 1
fi

docker compose cp scripts/capi-smoke-test.php "$SERVICE:/tmp/capi-smoke-test.php"

# Временный файл убирается в любом случае, в том числе после ошибки.
trap 'docker compose exec -T "$SERVICE" rm -f /tmp/capi-smoke-test.php >/dev/null 2>&1 || true' EXIT

docker compose exec -T "$SERVICE" php /tmp/capi-smoke-test.php
