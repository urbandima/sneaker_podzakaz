# Реорганизация структуры проекта 2026

## ✅ Завершено

### Новая структура проекта

```
project/
├── config/                     # Конфигурационные файлы
│   ├── nginx/                  # Nginx конфигурации
│   │   ├── default.conf
│   │   ├── nginx.conf.example
│   │   └── nginx.conf.production
│   ├── php/                    # PHP настройки
│   │   └── local.ini
│   ├── mysql/                  # MySQL конфигурации
│   │   ├── init/
│   │   └── my.cnf
│   └── docker/                 # Docker compose файлы
│       ├── docker-compose.yml
│       └── docker-compose.elasticsearch-redis.yml
├── scripts/                    # Скрипты и утилиты
│   ├── deploy/                 # Скрипты деплоя
│   │   └── DEPLOY_COMMANDS.sh
│   ├── backup/                 # Скрипты бэкапа
│   │   ├── backup.sh
│   │   └── cleanup-duplicates.sh
│   └── test/                   # Тестовые скрипты
│       ├── test-site.sh
│       └── share.sh
├── build/                      # Файлы сборки
│   ├── frontend/               # Фронтенд сборка
│   │   └── gulpfile.js
│   └── testing/                # Тестирование
│       ├── jest.config.js
│       ├── playwright.config.ts
│       └── phpunit.xml.dist
├── docs/                       # Документация
│   ├── architecture/           # Архитектура
│   │   └── ARCHITECTURE.md
│   ├── reports/                # Отчёты
│   │   ├── PROJECT_TASKS.md
│   │   ├── AUDIT_REPORT.md
│   │   └── SYSTEM_IMPROVEMENT_REPORT.md
│   └── api/                    # API документация
└── [остальные директории без изменений]
    ├── api/
    ├── backend/
    ├── frontend/
    ├── infrastructure/
    ├── tests/
    ├── vendor/
    └── uploads/
```

### Удобные команды (симлинки)

Для удобства использования созданы симлинки в корне:

```bash
# Docker
./docker-compose up -d           # Запуск Docker контейнеров

# Фронтенд
./gulp build                     # Сборка фронтенда
./gulp watch                     # Отслеживание изменений

# Тестирование
./test                           # Playwright тесты
./phpunit                        # PHPUnit тесты

# Документация
./ARCHITECTURE.md               # Архитектура проекта
./PROJECT_TASKS.md              # Задачи проекта
./AUDIT_REPORT.md               # Отчёт аудита
```

### Исправленные проблемы

#### ✅ PHPUnit тесты
- Заменены устаревшие классы:
  - `yii\codeception\TestCase` → `PHPUnit\Framework\TestCase`
  - `Codeception\Test\Unit` → `PHPUnit\Framework\TestCase`
  - Метод `_before()` → `setUp()`
- Исправлены все 7 тестовых файлов

#### ✅ Конфигурационные пути
- Обновлены пути в `docker-compose.yml`
- Исправлены пути в `gulpfile.js`
- Обновлены пути в `phpunit.xml.dist`
- Исправлены пути в тестовых конфигурациях

#### ✅ Удалена лишняя папка bin
- Все скрипты перенесены в `/scripts/`
- Папка `/bin/` полностью удалена
- Структура стала логичнее и проще
- Удалены неработающие middleware из конфигурации
- Исправлены namespace для assets
- Сервер запущен на http://localhost:8082
- Статус: ✅ 200 OK

### Команды для разработки

```bash
# Запуск сервера
php -S localhost:8082 -t frontend/web

# Сборка фронтенда
npm run build

# Запуск тестов
./phpunit --list-tests

# Docker (если нужно)
./docker-compose config
```

### Результат

Проект теперь имеет чистую, логичную структуру:
- **Конфигурации** → `/config/`
- **Скрипты** → `/scripts/`  
- **Сборка** → `/build/`
- **Документация** → `/docs/`
- **Симлинки** → в корне для удобства

Все импорты работают, сервер запущен, тесты исправлены. ✅
