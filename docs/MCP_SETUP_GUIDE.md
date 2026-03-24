# 🚀 MCP Servers Setup Guide

## ✅ Установленные MCP серверы

### 1. Browser MCP (@sanity-labs/browser-mcp)
- **Назначение**: Автоматизация браузера, веб-скрапинг, тестирование
- **Функции**:
  - `open_session` - Открыть вкладку браузера
  - `close_session` - Закрыть вкладку
  - `overview` - Получить сводку страницы
  - `query` - Искать элементы по CSS селекторам
  - `section` - Извлекать контент под заголовками
  - `elements` - Список элементов по типу
  - `action` - Взаимодействовать (клик, заполнить, навигация)

### 2. 21st Magic (@21st-dev/magic)
- **Назначение**: Magic UI builder и инструменты разработки
- **Версия**: 0.0.46
- **Функции**: Построение UI, магическая разработка

## 🔧 Конфигурация для IDE

### Windsurf
Конфигурация создана: `~/.windsurf/mcp_settings.json`

```json
{
  "mcpServers": {
    "browser-mcp": {
      "command": "npx",
      "args": ["@sanity-labs/browser-mcp"],
      "description": "Browser automation MCP server for web scraping and testing"
    },
    "21st-magic": {
      "command": "npx", 
      "args": ["@21st-dev/magic"],
      "description": "Magic UI builder and development tools by 21st.dev"
    }
  }
}
```

### Claude Desktop
Создайте файл: `~/Library/Application Support/Claude/claude_desktop_config.json`

```json
{
  "mcpServers": {
    "browser-mcp": {
      "command": "npx",
      "args": ["@sanity-labs/browser-mcp"]
    },
    "21st-magic": {
      "command": "npx",
      "args": ["@21st-dev/magic"]
    }
  }
}
```

### Cursor
Создайте файл: `~/.cursor/mcp_settings.json`

```json
{
  "mcp": {
    "servers": {
      "browser-mcp": {
        "command": "npx",
        "args": ["@sanity-labs/browser-mcp"]
      },
      "21st-magic": {
        "command": "npx",
        "args": ["@21st-dev/magic"]
      }
    }
  }
}
```

## 🎯 Примеры использования

### Browser MCP
```javascript
// Открыть страницу
await mcp.call("browser-mcp", "open_session", { 
  url: "https://example.com" 
});

// Получить сводку страницы
await mcp.call("browser-mcp", "overview");

// Найти элементы
await mcp.call("browser-mcp", "query", {
  selector: "button.submit",
  count: 5
});

// Кликнуть по элементу
await mcp.call("browser-mcp", "action", {
  action: "click",
  selector: "button.submit"
});
```

### 21st Magic
```javascript
// Создать UI компонент
await mcp.call("21st-magic", "create_ui", {
  type: "form",
  fields: ["email", "password"]
});
```

## 🔄 Перезапуск IDE

После настройки конфигурации перезапустите вашу IDE чтобы активировать MCP серверы.

## ✅ Проверка установки

```bash
# Проверить Browser MCP
npx @sanity-labs/browser-mcp --help

# Проверить 21st Magic  
npx @21st-dev/magic --help
```

## 📝 Заметки

- Оба сервера работают через stdio транспорт
- Browser MCP поддерживает headless и headed режимы
- 21st Magic предоставляет инструменты для UI разработки
- Конфигурации сохранены в соответствующих директориях IDE
