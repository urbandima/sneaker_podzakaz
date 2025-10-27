# ⚡ Быстрый старт с GitHub

## 🎯 Краткая инструкция для начала работы

### 1. Инициализация Git (одноразово)

```bash
cd /Users/user/CascadeProjects/splitwise

# Инициализировать репозиторий
git init

# Добавить все файлы
git add .

# Первый коммит
git commit -m "Initial commit: Splitwise project"
```

---

### 2. Создать репозиторий на GitHub

1. Перейти на https://github.com/new
2. Название: `splitwise`
3. Приватность: **Private**
4. Создать без README, .gitignore, license

---

### 3. Подключить к GitHub

```bash
# Заменить YOUR_USERNAME на ваш username GitHub
git remote add origin https://github.com/YOUR_USERNAME/splitwise.git

# Отправить код
git branch -M main
git push -u origin main
```

---

### 4. Настроить секреты GitHub (для автодеплоя)

1. Перейти в репозиторий на GitHub
2. Settings → Secrets and variables → Actions → New repository secret

Добавить 4 секрета:

| Имя секрета | Значение | Пример |
|-------------|----------|--------|
| `SSH_HOST` | Адрес хостинга | `your-server.com` |
| `SSH_USERNAME` | Имя пользователя SSH | `root` или `username` |
| `SSH_PASSWORD` | Пароль SSH | `your_password` |
| `SSH_PATH` | Путь к директории | `/var/www/html` |

---

### 5. Готово! 🎉

Теперь при каждом `git push` код автоматически выгрузится на хостинг.

---

## 📝 Ежедневная работа

```bash
# 1. Вносите изменения в код
# 2. Проверяете статус
git status

# 3. Добавляете измененные файлы
git add .

# 4. Коммитите
git commit -m "Описание изменений"

# 5. Отправляете на GitHub (и автоматически на хостинг)
git push
```

---

## 🆘 Если что-то пошло не так

### Забыли username/password для GitHub?

Используйте Personal Access Token:
1. GitHub → Settings → Developer settings → Personal access tokens → Generate new token
2. Выберите срок действия и права (repo)
3. Скопируйте token
4. Используйте как пароль при git push

### Ошибка: remote origin already exists

```bash
git remote remove origin
git remote add origin https://github.com/YOUR_USERNAME/splitwise.git
```

### Ошибка: Permission denied (publickey)

```bash
# Использовать HTTPS вместо SSH
git remote set-url origin https://github.com/YOUR_USERNAME/splitwise.git
```

---

## 📚 Полная документация

Для детальной информации смотрите **GITHUB_DEPLOY_GUIDE.md**

**Дата:** 27 октября 2025  
**Статус:** Готово к использованию ✅
