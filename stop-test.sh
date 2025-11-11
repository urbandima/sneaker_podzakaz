#!/usr/bin/env bash
# Остановка тестового окружения

set -euo pipefail

echo "🛑 Остановка тестового окружения..."

# Остановка ngrok
if pgrep -f "ngrok http" > /dev/null; then
    pkill -f "ngrok http"
    echo "✅ ngrok остановлен"
else
    echo "ℹ️  ngrok не запущен"
fi

# Остановка PHP сервера на 8080
if lsof -i :8080 -sTCP:LISTEN -t >/dev/null 2>&1; then
    PID=$(lsof -i :8080 -sTCP:LISTEN -t)
    kill "${PID}"
    echo "✅ PHP сервер остановлен (PID: ${PID})"
else
    echo "ℹ️  PHP сервер не запущен"
fi

echo ""
echo "✅ Все процессы остановлены"
