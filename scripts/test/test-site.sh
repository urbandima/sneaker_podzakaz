#!/usr/bin/env bash
# Упрощенный запуск локального тестового стенда с публичным URL

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SHARE_SCRIPT="${ROOT_DIR}/bin/share.sh"
PORT="${1:-8080}"

if [[ ! -x "${SHARE_SCRIPT}" ]]; then
    echo "❌ Не найден исполняемый скрипт ${SHARE_SCRIPT}" >&2
    echo "   Убедитесь, что файл существует и выполните: chmod +x bin/share.sh" >&2
    exit 1
fi

export PHP_BIN="${PHP_BIN:-php}"
export NGROK_BIN="${NGROK_BIN:-ngrok}"

echo "🚀 Подготовка тестового окружения на порту ${PORT}..."
"${SHARE_SCRIPT}" start "${PORT}"

PUBLIC_URL=$("${SHARE_SCRIPT}" url 2>/dev/null || true)

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Локальное окружение запущено"
echo "   Порт:       ${PORT}"
if [[ -n "${PUBLIC_URL}" ]]; then
    echo "   Публичный URL: ${PUBLIC_URL}"
else
    echo "   Публичный URL: определяется... (см. runtime/share-ngrok.log)"
fi
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🛑 Для остановки выполните: ./bin/scripts/stop-test.sh"
