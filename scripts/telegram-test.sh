#!/usr/bin/env bash
# Отправляет тестовое оповещение в группу Telegram.
set -euo pipefail

cd "$(dirname "$0")/.."

SERVICE=wordpress

if ! docker compose ps --status running --services | grep -qx "$SERVICE"; then
  echo "Сервис '$SERVICE' не запущен. Сначала: docker compose up -d" >&2
  exit 1
fi

docker compose cp scripts/telegram-test.php "$SERVICE:/tmp/telegram-test.php" >/dev/null

trap 'docker compose exec -T "$SERVICE" rm -f /tmp/telegram-test.php >/dev/null 2>&1 || true' EXIT

docker compose exec -T "$SERVICE" php /tmp/telegram-test.php
