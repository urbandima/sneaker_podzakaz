# 🔧 Заметки для разработчика

## Архитектура проекта

### MVC структура (Yii2)
- **Models** (`models/`) - бизнес-логика и работа с БД
- **Views** (`views/`) - представления (HTML + PHP)
- **Controllers** (`controllers/`) - обработка запросов

### Основные компоненты

#### Модели
- `User` - пользователи с ролями (admin, manager, logist)
- `Order` - заказы с токенами и публичными ссылками
- `OrderItem` - позиции заказов (товары)
- `OrderHistory` - история изменений статусов
- `LoginForm` - форма входа

#### Контроллеры
- `SiteController` - вход/выход, главная страница
- `AdminController` - админ-панель (CRUD заказов, статистика, управление)
- `OrderController` - публичная часть (просмотр заказа, загрузка файла)

#### Представления
- `layouts/main.php` - основной layout для авторизованных
- `layouts/public.php` - layout для публичной части
- `admin/*` - админ-панель
- `order/view.php` - публичная страница заказа
- `site/login.php` - страница входа

---

## База данных

### Схема таблиц

```sql
user
├── id (PK)
├── username (unique)
├── email (unique)
├── password_hash
├── auth_key
├── role (admin|manager|logist)
├── status
├── created_at
└── updated_at

order
├── id (PK)
├── order_number (unique, формат: YYYY-00001)
├── token (unique, для публичной ссылки)
├── client_name
├── client_phone
├── client_email
├── total_amount
├── status
├── delivery_date
├── payment_proof (путь к файлу)
├── payment_uploaded_at
├── offer_accepted
├── offer_accepted_at
├── created_by (FK -> user.id)
├── assigned_logist (FK -> user.id, nullable)
├── created_at
└── updated_at

order_item
├── id (PK)
├── order_id (FK -> order.id)
├── product_name
├── quantity
├── price
├── total
└── created_at

order_history
├── id (PK)
├── order_id (FK -> order.id)
├── old_status
├── new_status
├── comment
├── changed_by (FK -> user.id, nullable)
└── created_at

auth_* (RBAC таблицы)
```

---

## Консольные команды

### Миграции
```bash
# Применить все миграции
php yii migrate

# Откатить последнюю миграцию
php yii migrate/down

# Создать новую миграцию
php yii migrate/create migration_name

# История миграций
php yii migrate/history
```

### Кеш
```bash
# Очистить кеш
php yii cache/flush-all
```

### Генерация CRUD (Gii)
```bash
# Открыть Gii в браузере
http://localhost:8080/gii

# Или через консоль
php yii gii/model --tableName=table_name
```

---

## Конфигурация

### Подключение к БД (`config/db.php`)
```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=order_management',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

### Email (`config/web.php`)
```php
'mailer' => [
    'class' => 'yii\symfonymailer\Mailer',
    'useFileTransport' => true, // false для реальной отправки
],
```

### URL правила (`config/web.php`)
```php
'urlManager' => [
    'enablePrettyUrl' => true,
    'showScriptName' => false,
    'rules' => [
        'order/<token>' => 'order/view',
        'admin/orders' => 'admin/orders',
        // ...
    ],
],
```

---

## Работа с моделями

### Создание заказа
```php
$order = new Order();
$order->client_name = 'Иванов Иван';
$order->created_by = Yii::$app->user->id;
$order->save(); // Автогенерация order_number и token
```

### Добавление товаров
```php
$item = new OrderItem();
$item->order_id = $order->id;
$item->product_name = 'Кроссовки';
$item->quantity = 1;
$item->price = 300.00;
$item->save(); // Автоматически рассчитается total
```

### Поиск заказа по токену
```php
$order = Order::findOne(['token' => $token]);
```

### Изменение статуса
```php
$order->status = 'paid';
$order->save(); // Автоматически создастся запись в order_history
```

---

## Права доступа

### Проверка роли в контроллере
```php
$user = Yii::$app->user->identity;

if ($user->isAdmin()) {
    // Действия только для администратора
}

if ($user->isLogist()) {
    // Действия только для логиста
}
```

### Ограничение доступа через behaviors
```php
public function behaviors()
{
    return [
        'access' => [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'], // Только авторизованные
                ],
            ],
        ],
    ];
}
```

---

## События и хуки

### beforeSave (Order модель)
- Генерация номера заказа
- Генерация токена
- Установка начального статуса

### afterSave (Order модель)
- Логирование изменений статуса
- Отправка уведомлений

### beforeSave (OrderItem модель)
- Автоматический расчет итоговой суммы (`total = quantity * price`)

---

## Загрузка файлов

### Путь к файлам
```
/web/uploads/payments/
```

### Обработка загрузки
```php
$file = UploadedFile::getInstanceByName('payment_proof');
$fileName = $order->id . '_' . time() . '.' . $file->extension;
$file->saveAs($uploadPath . $fileName);
```

---

## Email шаблоны

### Расположение
```
/mail/order-created.php
/mail/payment-uploaded.php
```

### Отправка
```php
Yii::$app->mailer->compose('order-created', ['order' => $model])
    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
    ->setTo($model->client_email)
    ->setSubject('Заказ создан')
    ->send();
```

---

## Отладка

### Включение Debug панели
Уже включено в `dev` режиме: http://localhost:8080/debug

### Логи
```
runtime/logs/app.log
```

### Просмотр SQL запросов
```php
Yii::$app->db->enableLogging = true;
Yii::$app->db->enableProfiling = true;
```

---

## Тестирование

### Создание тестового заказа через консоль
```php
// Создайте команду в commands/TestController.php
public function actionCreateOrder()
{
    $order = new Order();
    $order->client_name = 'Test Client';
    $order->client_email = 'test@example.com';
    $order->created_by = 1;
    $order->save();
    
    echo "Заказ создан: " . $order->order_number . "\n";
    echo "Ссылка: " . $order->getPublicUrl() . "\n";
}

// Запуск
php yii test/create-order
```

---

## Безопасность

### CSRF токен
Автоматически добавляется в формы через `ActiveForm`

### SQL инъекции
Yii2 автоматически экранирует параметры в запросах

### XSS
Используйте `Html::encode()` для вывода пользовательских данных

### Валидация
```php
public function rules()
{
    return [
        [['client_name', 'client_email'], 'required'],
        ['client_email', 'email'],
        ['total_amount', 'number', 'min' => 0],
    ];
}
```

---

## Производительность

### Eager Loading
```php
// Вместо N+1 запросов
Order::find()->with(['creator', 'logist', 'orderItems'])->all();
```

### Кеширование
```php
$data = Yii::$app->cache->get('key');
if ($data === false) {
    $data = // получить данные
    Yii::$app->cache->set('key', $data, 3600);
}
```

---

## Расширение функционала

### Добавление нового статуса
1. Добавить в `config/params.php`:
```php
'orderStatuses' => [
    'new_status' => 'Название статуса',
],
```

2. Обновить проверки в `Order::canChangeStatus()`

### Добавление новой роли
1. Добавить константу в `User` модель
2. Добавить проверочный метод (`isNewRole()`)
3. Обновить `behaviors()` в контроллерах

### Добавление нового поля в заказ
1. Создать миграцию:
```bash
php yii migrate/create add_field_to_order
```

2. Добавить в `rules()` и `attributeLabels()` модели

3. Добавить в представления

---

## Полезные ссылки

- [Yii2 Docs](https://www.yiiframework.com/doc/guide/2.0/ru)
- [Yii2 API](https://www.yiiframework.com/doc/api/2.0)
- [Bootstrap 5](https://getbootstrap.com/docs/5.0)
- [Bootstrap Icons](https://icons.getbootstrap.com/)

---

## Известные проблемы и решения

### Ошибка прав доступа к runtime
```bash
chmod -R 777 runtime web/assets web/uploads
```

### Не работает pretty URL
Убедитесь, что `.htaccess` в `web/` и mod_rewrite включен

### Email не отправляются
В dev режиме они сохраняются в `runtime/mail/`. Для реальной отправки:
```php
'mailer' => [
    'useFileTransport' => false,
    'transport' => [
        'dsn' => 'smtp://user:pass@smtp.example.com:587',
    ],
],
```
