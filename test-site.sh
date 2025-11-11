#!/usr/bin/env bash
# Быстрый запуск тестового окружения с ngrok

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PORT=8080

echo "🚀 Проверка тестового окружения..."

# Проверка PHP сервера
if ! lsof -i :"${PORT}" -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo "▶️  Запуск PHP сервера на порту ${PORT}..."
    cd "${ROOT_DIR}"
    nohup php yii serve --port="${PORT}" --docroot=web > runtime/share-server.log 2>&1 &
    sleep 2
    echo "✅ PHP сервер запущен"
else
    echo "✅ PHP сервер уже работает на порту ${PORT}"
fi

# Проверка ngrok
if ! pgrep -f "ngrok http ${PORT}" > /dev/null; then
    echo "▶️  Запуск ngrok..."
    nohup ngrok http "${PORT}" --log=stdout --log-format=logfmt > runtime/share-ngrok.log 2>&1 &
    sleep 3
    echo "✅ ngrok запущен"
else
    echo "✅ ngrok уже работает"
fi

# Получение и вывод URL
sleep 1
if [[ -f runtime/share-ngrok.log ]]; then
    URL=$(grep -o 'url=https://[^ ]*' runtime/share-ngrok.log | head -n 1 | cut -d= -f2 || true)
    if [[ -n "${URL}" ]]; then
        echo ""
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "🌐 ПУБЛИЧНЫЙ URL ДЛЯ ТЕСТИРОВАНИЯ:"
        echo ""
        echo "   ${URL}"
        echo ""
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo ""
        echo "💡 Для остановки: ./stop-test.sh"
    else
        echo "⚠️  URL пока не получен, попробуй через пару секунд:"
        echo "   grep 'url=https' runtime/share-ngrok.log | head -n 1"
    fi
else
    echo "⚠️  Лог ngrok не найден"
fi
