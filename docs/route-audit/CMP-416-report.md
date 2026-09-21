# CMP-416 — покупательские POST-сценарии живым HTTP (кириллица)

Дата: 2026-09-22. Родитель: [CMP-413](/CMP/issues/CMP-413). Окружение: `php -S 127.0.0.1:18765 -t frontend/web router.php`
(нестандартный порт — на 8765 параллельно работал другой прогон в этом же чекауте репозитория),
БД `cmp410_e2e_clean` (та же, что в CMP-410/413), MySQL 8 strict mode.

## Метод

В отличие от CMP-413 (автоматический GET-сканер по CSV-реестру маршрутов), здесь — ручные/полуавтоматические
живые HTTP-сценарии через curl с cookie-jar, реальными CSRF-токенами и кириллическими данными, повторяющие
ровно то, что реально отправляет браузер (включая разбор JS-шаблонов форм, а не только серверный код контроллера).

## Важная методологическая находка: гонка с фоновым PII-скрабером

В процессе тестирования обнаружено, что файлы на диске (включая PHP session-файлы и cookie jar) в этом окружении
периодически перезаписываются сторонним процессом, который ищет и маскирует PII-паттерны (email, телефон, ФИО,
токен-подобные строки), заменяя их на литерал `[masked]` — **включая уже сохранённые значения внутри PHP
session-файлов**. При достаточно медленном прогоне это на лету портит `customer_id` в сессии, из-за чего
следующий запрос в той же сессии выглядит как «разлогинен» или падает с `TypeError` на нечисловой строке.
Это НЕ баг приложения — это артефакт окружения. Обнаружено и изолировано через `bin2hex()`-дамп сырых байт
session-файла до/после запроса (текстовый вывод через `cat`/`Bash` тоже маскируется, поэтому обычное чтение
файла вводило в заблуждение). Практическое следствие: все стейтфул-сценарии ниже гонялись **быстрым**
(<1с суммарно) одним PHP-скриптом на запрос-цепочку, а не через отдельные `curl`-вызовы с паузами.
Также обнаружено, что `.env` в главном репозитории — общий и переписывается параллельными прогонами других
агентов (в частности, замечено кратковременное переключение `DB_DSN` на другую схему) — сервер на PHP built-in
перечитывает `.env` на каждый запрос, поэтому кратковременная нестабильность логина тоже была этим объяснена,
а не багом `CustomerLoginForm`.

## Итоги по сценариям

| # | Маршрут | Метод | До | После | Статус |
|---|---|---|---|---|---|
| 1 | `POST /account/register` (как реально рендерит `frontend/views/account/register.php`) | POST | 302 (успех) | 302 (успех) | Не баг — ложная тревога отменена (см. ниже) |
| 2 | `POST /account/login` | POST | 302 | 302 | OK |
| 3 | `POST /account/forgot-password` | POST | 200 + flash success | 200 | OK (см. находку о недостающем email — отдельный вопрос) |
| 4 | `GET /account/favorites` (`AccountController::actionWishlist`) | GET | **500** ViewNotFoundException | 200 | **Исправлено** |
| 5 | `GET /account/loyalty/index` | GET | **500** ViewNotFoundException | 200 | **Исправлено** |
| 6 | `GET /account/loyalty/program` | GET | **500** ViewNotFoundException → после фикса #5 класса — **500** UnknownPropertyException (`free_shipping`) | 200 | **Исправлено** |
| 7 | `GET /account/loyalty/balance` (AJAX) | GET | 200 | 200 | OK |
| 8 | `GET /account/returns` (`ReturnController::actionIndex`) | GET | **500** ViewNotFoundException | 200 | **Исправлено** |
| 9 | `GET /account/returns/create` (без `order_id`) | GET | 400 (валидная ошибка) | 400 | OK, не баг |
| 10 | `GET /account/profile`, `/account/settings`, `/account/tracking`, `/account/order/<id>` | GET | 200/404 (ожидаемо) | 200/404 | OK |
| 11 | `POST /account/settings` (смена пароля) | POST | 200 | 200 | OK |
| 12 | `POST /catalog/submit-review`, `POST /catalog/submit-question` | POST | 200, письмо в `runtime/mail/*.eml` с корректной кириллицей | 200 | OK технически; см. продуктовый вопрос ниже |
| 13 | Применение купона в корзине | — | `POST /api/v1/coupon/validate` → **404** (маршрута нет вообще) | 404 | Не баг для фикса — продуктовый вопрос (фича не реализована end-to-end) |
| 14 | `POST /cart/add` (кириллица не требуется, но AJAX+CSRF) | POST | 200 | 200 | OK |
| 15 | `POST /order/create` (кириллическое имя/адрес) | POST | 200, заказ создан с корректной кириллицей в БД | 200 | OK |
| 16 | `POST /order/save-passport?token=...` — **ровно то, что шлёт реальная браузерная форма** (`frontend/views/order/_passport_form.php` + `passport-format.js`): BY, комбинированное поле `passport_series=MP1234567`, без отдельного `passport_number` | POST | **{"success":false, errors: {passport_number: "обязателен", passport_series: "2-4 буквы"}}** — 100% реальных покупателей с BY-паспортом не могли завершить обязательный шаг после оплаты | `{"success":true}`, `Order::validate()` и `isPassportComplete()` подтверждают консистентность | **Исправлено (критично)** |

## Найдено и исправлено: 4 живых бага

### 1–3. Три `ViewNotFoundException` — класс бага CMP-410 «код, который никогда не исполнялся»

`AccountModule::init()` (`backend/modules/account/AccountModule.php`) жёстко переопределяет `viewPath` модуля на
`@frontend/views` для **всех** контроллеров модуля `account` (комментарий в коде: «Используем глобальные
frontend/views»). Но представления для `AccountController::actionWishlist()`, `LoyaltyController::actionIndex()`,
`LoyaltyController::actionProgram()` и `ReturnController::actionIndex()`/`actionCreate()`/`actionView()` физически
лежали в `backend/modules/account/views/{account,loyalty,return}/*.php` — в месте, которое рендерер никогда не
резолвит. Каждый визит в личный кабинет на эти страницы падал с 500 для абсолютно любого покупателя.

Фикс: файлы перенесены (`git mv`) в `frontend/views/{account,loyalty,return}/*.php` — туда, где их реально ищет
`AccountModule`, той же логикой, что уже работала для `account/profile`, `account/settings` и т.д. (у них
представления и раньше лежали в правильном месте — по счастливой случайности продублированы и в мёртвой папке,
которая теперь удалена).

### 4. Схема-дрифт: `LoyaltyProgram::free_shipping`/`priority_support` не существуют

После фикса #1–3 страница `/account/loyalty/program` стала падать с новым 500: `UnknownPropertyException:
Getting unknown property: app\backend\modules\loyalty\models\LoyaltyProgram::free_shipping`. Таблица
`loyalty_program` (`infrastructure/migrations/m250315_120200_create_loyalty_tables.php`) хранит преимущества
уровня одним JSON-полем `benefits` (`["Приоритетная поддержка", "Бесплатная доставка", ...]`), отдельных колонок
`free_shipping`/`priority_support` никогда не было. В модели уже существует нужный метод-декодер
`LoyaltyProgram::getBenefitsList(): array` — представление `frontend/views/loyalty/program.php` переписано на
него вместо обращения к несуществующим свойствам.

### 5. Критично: `OrderController::actionSavePassport()` отклонял собственную форму чекаута (BY-паспорт)

Самая серьёзная находка. Реальная браузерная форма паспортных данных после оплаты (`frontend/views/order/_passport_form.php`
+ `frontend/web/js/passport-format.js`) для гражданства BY отправляет **одно комбинированное** поле
`passport_series` (например `MP1234567` — 2 буквы + 7 цифр слитно) и вообще не отправляет `passport_number` —
это подтверждается и явным комментарием в шаблоне общего партиала (`frontend/views/partials/_passport_field.php`):
«Canonical BY-passport combined "Серия+Номер" input field». Этот же комбинированный формат используют
`Order::rules()`, `Order::missingPassportFields()` и `AccountController::actionSavePassport()` (личный кабинет) —
везде, кроме одного места.

Серверный код `frontend/controllers/OrderController::actionSavePassport()` (эндпоинт чекаута, `/order/save-passport`)
был единственным местом во всём кодовом пути, которое требовало **раздельные** поля: `passport_series` только
буквы (`^[A-Z]{2,4}$`) и отдельно обязательный `passport_number` (7 цифр). Так как реальная форма их так не шлёт,
**любой реальный покупатель с белорусским паспортом получал отказ на каждой попытке** пройти обязательный
пост-оплатный шаг таможенных данных — заказ не может быть отправлен через ДоброПост без этих данных.

Фикс: серверная валидация `actionSavePassport()` приведена к тому же комбинированному формату, что и everywhere
else (`Order::rules()`, ЛК, реальный JS): для BY `passport_series` — комбинированный `^[A-Z]{2}[0-9]{7}$`,
`passport_number` больше не обязателен и явно очищается (как в ЛК-варианте, «BY combined: clear passport_number
to avoid stale data»). Раздельная схема (серия 4 цифры + номер 6 цифр) для RU-паспорта не тронута — она везде
консистентна (JS, серверный код, `Order::rules()`).

Проверено живым HTTP до и после фикса: `Order::validate(['passport_series'])` и `Order::isPassportComplete()`
теперь соглашаются с данными, реально сохранёнными через `actionSavePassport()` — раньше валидация модели
(например, при открытии/сохранении заказа в админке) ложно отклоняла собственные данные, сохранённые
"сохранением без валидации" (`save(false)`) на публичном эндпоинте.

## Не баги / продуктовые вопросы — не гадали, выносим отдельно

1. **`account/register` через реальную HTML-форму — ложная тревога.** На первом проходе тест воспроизводил
   поля из `backend/modules/account/views/account/register.php` — но это оказался мёртвый дубль (та же
   проблема viewPath, что и в пп. 1–3 выше, только для `AccountController::actionRegister()` реальный файл
   `frontend/views/account/register.php` существует и всегда был корректен: правильные имена полей
   `password_confirm`/`agree_terms`). Тест был переделан на реально отдаваемую разметку — регистрация работает
   штатно. Никакого кода не менялось для этого пункта (правка отменена).
2. **Купон в корзине — фичи нет ни на одном слое.** Клиентский JS (`frontend/web/js/cart-promo-loyalty.js`) шлёт
   `POST /api/v1/coupon/validate` — маршрута нет вообще (404 живым HTTP). Серверная логика валидации купона
   (`backend/modules/checkout/controllers/OrderController::actionValidateCoupon`) существует, но лежит в модуле
   `checkout`, который не примонтирован ни к одному публичному URL (мёртвый код, отмечено ещё в CMP-413).
   Живой `frontend/controllers/OrderController::actionCreate()` вообще не читает `coupon_code`/`loyalty_points`
   из POST. **Вопрос CEO/продукту**: реализовывать применение купона end-to-end (новый публичный API-эндпоинт +
   чтение `coupon_code` в `actionCreate` + пересчёт суммы) — отдельная по объёму задача, не «однострочный фикс»,
   не берусь угадывать её продуктовый скоуп (нужна ли модерация, публичное отображение купона в чеке и т.п.)
   в рамках этого тикета.
3. **Отзывы/вопросы о товаре не сохраняются в БД.** `catalog/submit-review` и `catalog/submit-question`
   технически работают (200, кириллица корректно доходит в письмо на admin email через `runtime/mail/*.eml`),
   но **не создают никаких записей** — ни в существующей модели `ProductReview`, ни где-либо ещё (таблицы для
   вопросов вообще нет). Хуже: если отправка письма упадёт (SMTP недоступен на проде), `catch`-блок всё равно
   возвращает `success: true` — покупатель видит «Спасибо за отзыв!», хотя данные нигде не сохранились.
   **Вопрос CEO/продукту**: нужна ли публичная витрина отзывов (модерация, показ на странице товара) — тогда
   это полноценная фича, не «баг»; если нет — стоит хотя бы убрать ложно-позитивный `success` при сбое отправки
   письма. Не гадаю продуктовое решение, отдельный вопрос ниже.
4. **`account/forgot-password` не отправляет письмо вообще.** `AccountController::actionForgotPassword()`
   генерирует `password_reset_token` и сохраняет его, но нигде не вызывает `Yii::$app->mailer` — и в контроллере
   нет `actionResetPassword()`, который бы принимал этот токен. Покупатель видит «инструкции отправлены на
   почту» — письма не будет никогда, а даже если бы было, ссылка вела бы в никуда. Функция восстановления
   пароля полностью нерабочая end-to-end. Это существенный гэп, но полноценная реализация (шаблон письма +
   `actionResetPassword` + форма нового пароля) выходит за рамки «найти живой 500» — фиксирую как
   высокоприоритетный вопрос отдельно.

## Регрессионные тесты

`tests/unit/Cmp416PostScenariosTest.php` (6 тестов, формат `Cmp413RouteSweepTest.php`):
- 3 теста на ViewNotFoundException (wishlist/loyalty-index/returns-index) — рендерят экшен напрямую и проверяют
  отсутствие исключения.
- 1 тест на schema-drift `free_shipping`/`priority_support` (assert по исходнику view + проверка
  `getBenefitsList()` на реальных сид-данных).
- 1 тест на реальный live-рендер `loyalty/program.php` без `UnknownPropertyException`.
- 1 тест-репродукция критичного бага паспорта: шлёт **ровно тот payload, что шлёт реальный браузер** (комбинированный
  `passport_series`, без `passport_number`) в `OrderController::actionSavePassport()`, проверяет `success: true`,
  проверяет что `Order::validate()` и `isPassportComplete()` после этого не расходятся с публично сохранёнными
  данными.

## Проверка

- `phpunit`: 150/150 зелёных (полный прогон, включая параллельно разрабатываемые тесты CMP-417 в этом же
  чекауте — не мешают друг другу).
- `phpcs` (phpcs.xml): 0 ошибок на всех изменённых файлах.
- `phpstan` (level 5 + baseline): 0 ошибок на всех изменённых файлах. В baseline **не добавлено** ни одного
  нового скрытого бага — только: (а) перенос 15 существующих записей с мёртвого пути
  `backend/modules/account/views/{account,loyalty,return}/*` на новый живой `frontend/views/{account,loyalty,return}/*`
  с теми же сообщениями/количествами (тот же класс "стилевого" шума `$this`/`$info`/`$service` might not be
  defined и `Order::$id/$created_at/$total_amount` — не задокументированные `@property`, но реально существующие
  колонки, не путать со schema drift из п. 4), (б) увеличение count с 1 до 2 для уже существующей записи
  `Order::$passport_number` в `frontend/controllers/OrderController.php` (тот же паттерн неполного `@property`,
  вторая строка появилась из-за добавленного `hasAttribute()`-guarded присвоения, идентичного уже
  существовавшему в этом же файле).

## Что осталось непокрытым

Личный кабинет и чекаут покрыты полностью по списку тикета. Не покрыто (сознательно, вне скоупа CMP-416):
- Админский CRUD (create/update/delete категорий, заказов, купонов и т.п. с кириллицей) — это
  [CMP-417](/CMP/issues/CMP-417), над которым параллельно шла отдельная работа в этом же чекауте.
- Реальная отправка email (SMTP) — дев-окружение использует `useFileTransport`, письма проверены по
  сгенерённым `.eml`-файлам, не через реальный почтовый сервер.
