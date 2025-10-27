# 🎨 Установка Favicon для сайта

## Что такое Favicon?

Favicon - это маленькая иконка, которая отображается во вкладке браузера рядом с названием сайта.

---

## 📥 Скачивание favicon с sneaker-head.by

### Способ 1: Через браузер

1. Откройте https://sneaker-head.by в браузере
2. Нажмите правой кнопкой на странице → "Просмотреть код страницы" (Ctrl+U)
3. Найдите строку с favicon (обычно в `<head>`):
   ```html
   <link rel="icon" href="/favicon.ico">
   ```
4. Откройте https://sneaker-head.by/favicon.ico
5. Сохраните файл (Ctrl+S)

### Способ 2: Через командную строку

```bash
# Скачать favicon.ico
curl -o /Users/user/CascadeProjects/splitwise/web/favicon.ico https://sneaker-head.by/favicon.ico

# Скачать PNG версию (если есть)
curl -o /Users/user/CascadeProjects/splitwise/web/favicon.png https://sneaker-head.by/favicon.png
```

### Способ 3: Создать свой Favicon

Если нужен кастомный favicon:

1. Перейдите на https://favicon.io/favicon-generator/
2. Введите текст "SH" или загрузите логотип
3. Скачайте сгенерированные файлы
4. Разместите в `/web/`

---

## 📁 Куда разместить Favicon

```
/Users/user/CascadeProjects/splitwise/web/
├── favicon.ico           ← Основной файл (16x16, 32x32)
├── favicon-16x16.png     ← Опционально
├── favicon-32x32.png     ← Опционально
├── apple-touch-icon.png  ← Для iOS (180x180)
└── android-chrome-*.png  ← Для Android
```

---

## 🔧 Добавление Favicon в layout

Откройте файл `/views/layouts/public.php` и добавьте в `<head>`:

```php
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= Yii::$app->request->baseUrl ?>/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= Yii::$app->request->baseUrl ?>/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= Yii::$app->request->baseUrl ?>/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Yii::$app->request->baseUrl ?>/apple-touch-icon.png">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <?php $this->head() ?>
</head>
```

---

## ✅ Проверка

1. Перезагрузите страницу (Ctrl+Shift+R для полной перезагрузки)
2. Проверьте, что иконка появилась во вкладке браузера
3. Откройте DevTools (F12) → вкладка Network
4. Проверьте, что файл favicon.ico загружается без ошибок

---

## 🎨 Альтернатива: Emoji Favicon

Если нет файла, можно использовать emoji:

```html
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👟</text></svg>">
```

---

## 📌 Готовые команды для быстрой установки

```bash
# Перейти в директорию web
cd /Users/user/CascadeProjects/splitwise/web

# Скачать favicon с sneaker-head.by
curl -o favicon.ico https://sneaker-head.by/favicon.ico

# Проверить, что файл скачан
ls -lh favicon.ico

# Если файл пустой или ошибка 404, используем emoji fallback
# Добавьте в layout код emoji favicon выше
```

---

## 🚀 Готово!

Favicon добавлен и будет отображаться на всех страницах сайта.
