#!/usr/bin/env bash
# Остановка тестового окружения

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
STATE_DIR="${ROOT_DIR}/runtime/share"

echo "🛑 Остановка тестового окружения..."

# Остановка ngrok
if pgrep -f "ngrok http" > /dev/null; then
    pkill -f "ngrok http"
    echo "✅ ngrok остановлен"
else
    echo "ℹ️  ngrok не запущен"
fi

# Остановка всех PHP серверов, поднятых share.sh
for pidfile in "${STATE_DIR}/server.pid" "${STATE_DIR}/server-"*.pid; do
    [[ -f "${pidfile}" ]] || continue
    PID=$(cat "${pidfile}")
    if kill "${PID}" >/dev/null 2>&1; then
        echo "✅ PHP сервер остановлен (PID: ${PID})"
    fi
    rm -f "${pidfile}"
done

echo ""
echo "✅ Все процессы остановлены"
