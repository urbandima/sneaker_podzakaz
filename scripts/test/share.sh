#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${2:-8080}"
HOST="localhost"
LOG_DIR="${ROOT_DIR}/runtime"
STATE_DIR="${LOG_DIR}/share"
SERVER_LOG="${LOG_DIR}/share-server.log"
NGROK_LOG="${LOG_DIR}/share-ngrok.log"
SERVER_PID_FILE="${STATE_DIR}/server.pid"
NGROK_PID_FILE="${STATE_DIR}/ngrok.pid"
URL_FILE="${STATE_DIR}/url.txt"

mkdir -p "${STATE_DIR}"

PHP_BIN="${PHP_BIN:-php}"
NGROK_BIN="${NGROK_BIN:-ngrok}"

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

is_pid_running() {
    local pid="$1"
    if [[ -z "${pid}" ]]; then
        return 1
    fi
    if kill -0 "${pid}" >/dev/null 2>&1; then
        return 0
    fi
    return 1
}

start_server() {
    if lsof -Pi :"${PORT}" -sTCP:LISTEN -t >/dev/null 2>&1; then
        echo "⚠️  Порт ${PORT} уже используется. Остановите процесс или выберите другой порт." >&2
        exit 1
    fi

    echo "▶️  Запуск локального сервера на http://${HOST}:${PORT}"
    cd "${ROOT_DIR}" >/dev/null
    nohup "${PHP_BIN}" yii serve --port="${PORT}" --docroot=web >"${SERVER_LOG}" 2>&1 &
    local server_pid=$!
    echo "${server_pid}" > "${SERVER_PID_FILE}"

    for attempt in $(seq 1 30); do
        if curl --silent --fail "http://${HOST}:${PORT}/" >/dev/null 2>&1; then
            echo "✅ Локальный сервер отвечает"
            return 0
        fi
        sleep 1
    done

    echo "❌ Сервер не ответил в течение 30 секунд" >&2
    exit 1
}

start_ngrok() {
    if ! command_exists "${NGROK_BIN}"; then
        echo "❌ Не найден ngrok. Установи с https://ngrok.com/download или brew install ngrok" >&2
        exit 1
    fi

    echo "▶️  Запуск ngrok для порта ${PORT}"
    cd "${ROOT_DIR}" >/dev/null
    nohup "${NGROK_BIN}" http "${PORT}" --log=stdout --log-format=logfmt >"${NGROK_LOG}" 2>&1 &
    local ngrok_pid=$!
    echo "${ngrok_pid}" > "${NGROK_PID_FILE}"

    local url=""
    for attempt in $(seq 1 30); do
        if [[ -f "${NGROK_LOG}" ]]; then
            url=$(grep -o 'url=https://[^ ]*' "${NGROK_LOG}" | head -n 1 | cut -d= -f2 || true)
            if [[ -n "${url}" ]]; then
                echo "${url}" > "${URL_FILE}"
                echo "🌐 Публичный URL: ${url}"
                return 0
            fi
        fi
        sleep 1
    done

    echo "❌ Не удалось получить URL от ngrok" >&2
    exit 1
}

stop_process() {
    local pid_file="$1"
    local name="$2"

    if [[ -f "${pid_file}" ]]; then
        local pid
        pid=$(cat "${pid_file}")
        if is_pid_running "${pid}"; then
            kill "${pid}" >/dev/null 2>&1 || true
            sleep 1
            if is_pid_running "${pid}"; then
                kill -9 "${pid}" >/dev/null 2>&1 || true
            fi
            echo "🛑 Остановлен ${name} (PID ${pid})"
        fi
        rm -f "${pid_file}"
    fi
}

show_status() {
    echo "=== Состояние share-сервиса ==="
    if [[ -f "${SERVER_PID_FILE}" ]]; then
        local pid
        pid=$(cat "${SERVER_PID_FILE}")
        if is_pid_running "${pid}"; then
            echo "• Локальный сервер: PID ${pid}, порт ${PORT}"
        else
            echo "• Локальный сервер: запись есть, процесс не найден"
        fi
    else
        echo "• Локальный сервер: не запущен"
    fi

    if [[ -f "${NGROK_PID_FILE}" ]]; then
        local pid
        pid=$(cat "${NGROK_PID_FILE}")
        if is_pid_running "${pid}"; then
            echo "• ngrok: PID ${pid}";
        else
            echo "• ngrok: запись есть, процесс не найден"
        fi
    else
        echo "• ngrok: не запущен"
    fi

    if [[ -f "${URL_FILE}" ]]; then
        echo "• Последний URL: $(cat "${URL_FILE}")"
    else
        echo "• URL: неизвестен"
    fi
}

show_url() {
    if [[ -f "${URL_FILE}" ]]; then
        echo "$(cat "${URL_FILE}")"
    else
        echo "URL пока не сохранён. Запусти: $0 start" >&2
        exit 1
    fi
}

stop_all() {
    stop_process "${NGROK_PID_FILE}" "ngrok"
    stop_process "${SERVER_PID_FILE}" "локальный сервер"
    rm -f "${URL_FILE}"
}

ensure_requirements() {
    if ! command_exists "${PHP_BIN}"; then
        echo "❌ Не найден php. Установи PHP 8+ и добавь в PATH." >&2
        exit 1
    fi
}

case "${1:-start}" in
    start)
        ensure_requirements
        start_server
        start_ngrok
        ;;
    stop)
        stop_all
        ;;
    status)
        show_status
        ;;
    url)
        show_url
        ;;
    *)
        echo "Использование: $0 [start|stop|status|url] [порт]" >&2
        exit 1
        ;;
esac
