# 🚀 Руководство после исправления проблем

**Для команды разработки**  
**Дата:** 25 октября 2025

---

## 📋 Что изменилось

### ✅ Исправлено 10 критических проблем
- 3 критические уязвимости безопасности
- 3 высокоприоритетные проблемы
- 4 проблемы среднего приоритета

### 📁 Изменено 8 файлов
- 2 контроллера
- 2 модели  
- 3 представления
- 3 документа

---

## 🎓 Обучение команды

### Для разработчиков

#### 1. Новые методы в OrderController

**Валидация файлов:**
```php
// Использование:
$errors = $this->validateUploadedFile($file);
if (!empty($errors)) {
    // Обработка ошибок
}

// Проверяет:
// - Размер (макс 5 МБ)
// - Расширение (jpg, png, pdf, gif, webp)
// - MIME-тип
// - Magic bytes (реальный тип файла)
```

**Rate Limiting:**
```php
// Использование:
$this->checkRateLimit($token);

// Ограничения:
// - 5 попыток на токен
// - Период: 15 минут
// - Автоматический сброс
```

#### 2. Изменения в Order::generateOrderNumber()

**Было (НЕПРАВИЛЬНО):**
```php
// ❌ Race condition возможен
$lastOrder = self::find()->orderBy(['id' => SORT_DESC])->one();
$newNumber = $lastNumber + 1;
return sprintf('%s-%05d', $year, $newNumber);
```

**Стало (ПРАВИЛЬНО):**
```php
// ✅ Атомарная генерация с retry
$transaction = Yii::$app->db->beginTransaction();
try {
    $lastOrder = self::find()->queryOne();
    $orderNumber = sprintf('%s-%05d', $year, $newNumber);
    
    if (!self::find()->where(['order_number' => $orderNumber])->exists()) {
        $transaction->commit();
        return $orderNumber;
    }
    
    $transaction->rollBack();
    // Retry с задержкой
    usleep(rand(10000, 50000));
} catch (\Exception $e) {
    $transaction->rollBack();
}
```

#### 3. Работа со статусами

**Было (УСТАРЕЛО):**
```php
// ❌ Статусы из params
Yii::$app->params['orderStatuses']
Yii::$app->params['logistStatuses']
```

**Стало (АКТУАЛЬНО):**
```php
// ✅ Единый источник через Settings
Yii::$app->settings->getStatuses()
Yii::$app->settings->getLogistStatuses()
```

### Для тестировщиков

#### Новые сценарии для проверки

**1. Защита от повторной загрузки**
```
Шаги:
1. Загрузить подтверждение оплаты
2. Попытаться загрузить снова
Ожидается: Ошибка "Подтверждение оплаты уже загружено"
```

**2. Rate Limiting**
```
Шаги:
1. Сделать 5 попыток загрузки файла за 5 минут
2. Попытаться загрузить 6-й раз
Ожидается: Ошибка "Превышено количество попыток. Попробуйте через 15 минут"
```

**3. Валидация файлов**
```
Тест A: Большой файл (>5 МБ)
Ожидается: Ошибка "Размер файла не должен превышать 5 МБ"

Тест B: Неправильный тип (.exe, .php)
Ожидается: Ошибка "Допустимые форматы: JPG, PNG, PDF, GIF, WEBP"

Тест C: Переименованный файл (virus.exe → virus.jpg)
Ожидается: Ошибка "Файл не соответствует заявленному типу"
```

**4. Параллельное создание заказов**
```
Шаги:
1. Открыть 3 браузера одновременно
2. Создать заказ в каждом в одно время
3. Проверить номера заказов
Ожидается: Все номера уникальны (2025-00001, 2025-00002, 2025-00003)
```

**5. Транзакции**
```
Шаги:
1. Загрузить файл
2. Искусственно вызвать ошибку в процессе
3. Проверить, что файл удален с диска
Ожидается: Файла нет, запись в БД не создана
```

### Для DevOps

#### 1. Настройка логирования

**config/web.php:**
```php
'log' => [
    'traceLevel' => YII_DEBUG ? 3 : 0,
    'targets' => [
        // Security логи
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['error', 'warning'],
            'categories' => ['security'],
            'logFile' => '@runtime/logs/security.log',
            'maxFileSize' => 10240, // 10MB
            'maxLogFiles' => 30,
            'logVars' => [],
        ],
        // Order логи
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['info', 'error', 'warning'],
            'categories' => ['order'],
            'logFile' => '@runtime/logs/orders.log',
            'maxFileSize' => 10240,
            'maxLogFiles' => 30,
            'logVars' => [],
        ],
        // Общие логи
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['error', 'warning'],
            'logFile' => '@runtime/logs/app.log',
            'maxFileSize' => 10240,
            'maxLogFiles' => 30,
        ],
    ],
],
```

#### 2. Мониторинг алертов

**Настроить в Grafana/Prometheus:**

```yaml
alerts:
  - name: RateLimitExceeded
    query: count(grep "Превышен лимит" security.log) > 10 in 1h
    action: notify #security
    
  - name: UnauthorizedAccess
    query: grep "Попытка доступа к чужому" security.log
    action: notify #security immediately
    
  - name: EmailFailures
    query: count(grep "Ошибка отправки email" orders.log) > 5 in 1h
    action: notify #dev-ops
    
  - name: FileUploadErrors
    query: count(grep "Ошибка загрузки" orders.log) > 10 in 1h
    action: check disk space + notify
```

#### 3. Backup стратегия

```bash
#!/bin/bash
# /opt/backup/daily_backup.sh

# Backup базы данных
mysqldump -u user -p order_management > /backup/db_$(date +%Y%m%d).sql

# Backup файлов подтверждений
tar -czf /backup/payments_$(date +%Y%m%d).tar.gz /path/to/web/uploads/payments/

# Backup логов
tar -czf /backup/logs_$(date +%Y%m%d).tar.gz /path/to/runtime/logs/

# Удалить старые backup (>30 дней)
find /backup -name "*.sql" -mtime +30 -delete
find /backup -name "*.tar.gz" -mtime +30 -delete

# Отправить на S3
aws s3 sync /backup s3://company-backups/order-system/
```

**Добавить в crontab:**
```cron
0 3 * * * /opt/backup/daily_backup.sh
```

#### 4. Health check эндпоинт

**Создать controllers/HealthController.php:**
```php
<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;

class HealthController extends Controller
{
    public $enableCsrfValidation = false;
    
    public function actionIndex()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'disk_space' => $this->checkDiskSpace(),
            'logs' => $this->checkLogs(),
        ];
        
        $healthy = !in_array(false, $checks);
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->statusCode = $healthy ? 200 : 503;
        
        return [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => time(),
        ];
    }
    
    private function checkDatabase()
    {
        try {
            Yii::$app->db->createCommand('SELECT 1')->queryScalar();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function checkDiskSpace()
    {
        $uploadPath = Yii::getAlias('@app/web/uploads/');
        $free = disk_free_space($uploadPath);
        $total = disk_total_space($uploadPath);
        $percent = ($free / $total) * 100;
        
        return $percent > 10; // Минимум 10% свободного места
    }
    
    private function checkLogs()
    {
        $logPath = Yii::getAlias('@runtime/logs/');
        return is_writable($logPath);
    }
}
```

**Настроить проверку:**
```bash
# Добавить в monitoring
curl http://your-domain.com/health
```

---

## 🧪 Тестирование перед деплоем

### 1. Unit тесты (если есть)
```bash
vendor/bin/codecept run unit
```

### 2. Функциональные тесты

**Чек-лист:**
- [ ] Создание заказа работает
- [ ] Публичная ссылка открывается
- [ ] Загрузка файла работает (валидный)
- [ ] Загрузка файла блокируется (невалидный)
- [ ] Rate limiting срабатывает после 5 попыток
- [ ] Повторная загрузка блокируется
- [ ] Email отправляются
- [ ] Логи создаются
- [ ] Статусы меняются корректно
- [ ] Логисты видят только свои заказы

### 3. Нагрузочное тестирование

```bash
# Apache Bench
ab -n 1000 -c 10 http://localhost:8080/

# Проверка параллельных запросов
for i in {1..10}; do
  curl -X POST http://localhost:8080/admin/create-order &
done
wait

# Проверить уникальность номеров
mysql -e "SELECT order_number, COUNT(*) FROM \`order\` GROUP BY order_number HAVING COUNT(*) > 1"
# Должно быть пусто!
```

---

## 📦 Процедура деплоя

### Staging

```bash
# 1. Backup
mysqldump -u user -p order_management_staging > backup_staging.sql

# 2. Pull изменений
cd /var/www/staging
git pull origin develop

# 3. Composer (если нужно)
composer install --no-dev --optimize-autoloader

# 4. Миграции (если есть новые)
php yii migrate

# 5. Очистить кеш
php yii cache/flush-all

# 6. Проверить права
chmod -R 755 web/uploads/
chmod -R 777 runtime/

# 7. Restart PHP-FPM
sudo systemctl restart php8.4-fpm

# 8. Тест
curl http://staging.domain.com/health
```

### Production

```bash
# 1. ОБЯЗАТЕЛЬНО: Backup
mysqldump -u user -p order_management > backup_prod_$(date +%Y%m%d_%H%M%S).sql
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz web/uploads/

# 2. Maintenance mode
echo "🚧 Обновление системы. Вернемся через 5 минут." > web/maintenance.html

# 3. Pull изменений
cd /var/www/production
git pull origin main

# 4. Dependencies
composer install --no-dev --optimize-autoloader

# 5. Миграции
php yii migrate --interactive=0

# 6. Clear cache
php yii cache/flush-all
rm -rf runtime/cache/*

# 7. Permissions
chmod -R 755 web/uploads/
chmod -R 777 runtime/

# 8. Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx

# 9. Remove maintenance
rm web/maintenance.html

# 10. Health check
curl http://production.domain.com/health

# 11. Smoke test
curl -I http://production.domain.com/
curl -I http://production.domain.com/order/view?token=test

# 12. Monitor logs
tail -f runtime/logs/app.log
```

---

## 🔍 Проверка после деплоя

### Immediate (в течение 5 минут)

```bash
# 1. Проверить health
curl http://domain.com/health | jq

# 2. Проверить логи на ошибки
tail -100 runtime/logs/app.log | grep error

# 3. Проверить основные страницы
curl -I http://domain.com/
curl -I http://domain.com/site/login

# 4. Тестовая загрузка файла (staging)
# Вручную через браузер
```

### Short-term (в течение часа)

- Мониторить dashboards
- Проверять rate limiting работает
- Проверять логи security.log
- Тестировать реальный use case

### Long-term (первый день)

- Анализ логов каждые 2 часа
- Проверка метрик производительности
- Сбор обратной связи от пользователей
- Мониторинг ошибок в Sentry/Rollbar

---

## 📚 Документация для команды

### Где что находится

| Документ | Назначение |
|----------|------------|
| `SECURITY_FIX_REPORT.md` | Полный технический отчет |
| `РЕШЕНИЕ_ПРОБЛЕМ_ИТОГ.md` | Итоговая сводка |
| `QUICK_FIXES.md` | Быстрая справка |
| `SECURITY_ARCHITECTURE.md` | Архитектура безопасности |
| `AFTER_FIX_GUIDE.md` | Этот документ |
| `PROJECT_TASKS.md` | Список задач |

### Обучающие материалы

**Для новых разработчиков:**
1. Прочитать `SECURITY_ARCHITECTURE.md`
2. Изучить код валидации в `OrderController::validateUploadedFile()`
3. Понять работу транзакций в `OrderController::actionUploadPayment()`
4. Изучить race-safe генерацию в `Order::generateOrderNumber()`

**Для тестировщиков:**
1. Прочитать раздел "Для тестировщиков" в этом документе
2. Запустить все сценарии проверки
3. Создать отчет о тестировании

**Для DevOps:**
1. Настроить логирование (раздел выше)
2. Настроить алерты
3. Настроить backup
4. Создать health check эндпоинт

---

## 🆘 Troubleshooting

### Проблема: Rate limiting блокирует всех

**Причина:** Все запросы идут с одного IP (reverse proxy)

**Решение:**
```php
// В checkRateLimit() использовать настоящий IP
$key = 'upload_attempts_' . $token . '_' . Yii::$app->request->userIP;
```

### Проблема: Файлы не загружаются

**Проверить:**
1. Права на `/web/uploads/payments/` (должно быть 755 или 777)
2. Размер в `php.ini` (`upload_max_filesize`, `post_max_size`)
3. Логи в `runtime/logs/orders.log`

### Проблема: Дублируются номера заказов

**Проверить:**
1. MySQL поддерживает транзакции (InnoDB, не MyISAM)
2. Логи на наличие deadlock'ов
3. Увеличить `$maxRetries` в `generateOrderNumber()`

### Проблема: Email не отправляются

**Проверить:**
1. Настройки `config/web.php` → `mailer`
2. `useFileTransport = false` в продакшн
3. Логи в `runtime/logs/orders.log`
4. SMTP credentials

---

## 📞 Контакты

**При критических проблемах:**
- Slack: #dev-emergency
- Дежурный: +375 29 XXX-XX-XX
- Email: dev-team@sneakerculture.by

**При вопросах:**
- Slack: #dev-support
- Wiki: confluence.company.com/orders

**Escalation path:**
1. Junior Dev → Senior Dev (15 min)
2. Senior Dev → Tech Lead (30 min)
3. Tech Lead → CTO (1 hour)

---

## ✅ Финальный чек-лист

Перед закрытием задачи убедитесь:

- [x] Все 10 проблем исправлены
- [x] Код прошел review
- [ ] Unit тесты пройдены
- [ ] Функциональные тесты пройдены
- [ ] Staging развернут
- [ ] Staging протестирован
- [ ] Production развернут
- [ ] Production протестирован
- [ ] Команда обучена
- [ ] Документация обновлена
- [ ] Мониторинг настроен
- [ ] Backup настроен
- [ ] Stakeholders уведомлены

---

**Документ подготовлен:** 25 октября 2025  
**Версия:** 1.0  
**Следующий review:** через 1 месяц

Успешного деплоя! 🚀
