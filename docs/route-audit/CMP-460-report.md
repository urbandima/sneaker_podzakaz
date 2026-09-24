# CMP-460 — Сплошной прогон мутирующих действий, волны 2а/2б

## Волна 2а: Приоритет 2 (данные покупателя)

Продолжение методологии CMP-456: живой HTTP-запрос с правдоподобным payload (реальные
CSRF/сессия/cookies, кириллица в текстовых полях) + сверка фактической строки в MySQL
до/после, вердикт по эффекту в БД, а не по HTTP-коду.

## Границы охвата этой под-волны

Только **Приоритет 2 — данные покупателя**: `backend/modules/account/controllers/AccountController.php`
(единственный реальный контроллер личного кабинета — `frontend/controllers/AccountController.php`
существует только в паперклип-worktree-мусоре, в main repo его нет).

Отзыв (`catalog/review/create`) и избранное (`product_favorite`) закрыты раньше —
CMP-454 и CMP-446, здесь не дублируются. Отдельной формы «заявки/обращения» вне отзыва
в кодовой базе не найдено: `PageController::actionContacts` и `actionReturnPolicy` —
статические страницы без мутирующего действия, реального contact/callback-эндпоинта нет.

## Реестр

| Действие | Маршрут | Payload (кратко) | HTTP | Факт в БД | Вердикт |
|---|---|---|---|---|---|
| `actionRegister` | `POST /account/register` | ФИО кириллица, email, телефон, пароль | 302 | строка `customer` создана, поля с кириллицей целы, автологин срабатывает | **ok** |
| `actionLogin` | `POST /account/login` (`CustomerLoginForm`) | email/пароль зарегистрированного покупателя | 302 | сессия/identity-cookie выставляются, `/account/profile` доступен без повторного входа | **ok** |
| `actionProfile` (сохранение) | `POST /account/profile` | ФИО кириллица, телефон | 200/302 | поля `customer` обновились, кириллица цела | **ok** |
| `actionSettings` (смена пароля) | `POST /account/settings` | текущий + новый пароль | 200 | `password_hash` и `auth_key` реально изменились (не совпадают со старыми) | **ok** |
| `actionForgotPassword` | `POST /account/forgot-password` | email существующего покупателя | 200 | `password_reset_token` пишется в БД, но письмо **не отправлялось** — приёмного эндпоинта для токена не существовало вовсе | **no_effect → исправлено** |
| `actionResetPassword` (новый) | `GET/POST /account/reset-password?token=...` | новый пароль + подтверждение | 200 | `password_hash`+`auth_key` меняются, `password_reset_token` очищается; повторное использование того же токена корректно отклоняется (редирект на forgot-password) | **ok (после фикса)** |

## Найденный дефект и фикс

`actionForgotPassword` (`backend/modules/account/controllers/AccountController.php:412`) генерировал
`password_reset_token`, сохранял его в БД и сразу показывал пользователю сообщение об успехе —
но:

1. письмо с ссылкой на самом деле никогда не отправлялось (не было вызова `Yii::$app->mailer`);
2. даже если бы письмо ушло, принять токен и установить новый пароль было физически
   невозможно — маршрута `account/reset-password` не существовало.

Итог до фикса: пользователь получает «письмо отправлено», реально ничего не приходит,
и даже вручную подставленный токен никуда не ведёт. Полный тупик восстановления пароля
для customer-аккаунтов (не путать с admin-панелью, там свой механизм).

Исправлено (некоммичено на момент этого отчёта, коммит будет сразу после):

- `Customer::findByPasswordResetToken()` — поиск по токену с проверкой TTL (1 час,
  токен формата `<randomString>_<unixTimestamp>`, как и генерируется в
  `generatePasswordResetToken()`).
- `AccountController::actionForgotPassword` — реальная отправка письма
  (`backend/shared/mail/password-reset.php`) со ссылкой на `account/reset-password`;
  ошибка отправки логируется, но не блокирует ответ пользователю (чтобы не палить
  существование email — та же защита от enumeration, что уже была).
- `AccountController::actionResetPassword` (новый экшен) + вид
  `frontend/views/account/reset-password.php` — принимает токен, валидирует TTL,
  устанавливает новый пароль, инвалидирует `auth_key` (разлогинивает все старые сессии/
  remember-me куки) и очищает токен.
- Маршрут `account/reset-password` добавлен в `infrastructure/config/web.php`.

Проверено живым прогоном:
`forgot-password` → токен в БД → `reset-password` с этим токеном → `password_hash`/`auth_key`
в БД реально изменились, `password_reset_token` очищен → повторный запрос с тем же токеном
корректно отклонён (токен уже `NULL`, редирект на `forgot-password`).

## Итого волны 2а

- **6 мутирующих действий** Приоритета 2 проверено живым запросом с подтверждением
  эффекта в БД.
- **1 реальный дефект** найден и исправлен — полностью нерабочее восстановление пароля
  для customer-аккаунтов (класс «200 без эффекта» + отсутствующий эндпоинт).
- Тестовые данные (customer id 249, `cmp460.test.*@example.com`) удалены, подтверждено
  проверочным `SELECT` после очистки.

## Честная граница охвата волны 2а

Эта под-волна закрывает только **Приоритет 2**. Попытка вынести Приоритет 3/4 в дочерние
карточки CMP-461/CMP-462 в конце волны 2а провалилась с ошибкой API `delegation_cycle`
(нельзя создать child, назначенный на себя же, когда сам являешься автором цепочки
CMP-460) — **эти карточки не существуют**, несмотря на упоминание в предыдущей версии
этого отчёта. Остаток продолжен прямо в CMP-460, волна 2б ниже.

## Волна 2б: Приоритет 3 (каталог) — базовый CRUD

Метод тот же: логин `admin/admin123` в `/admin/login`, живой POST с CSRF из
`<meta name="csrf-token">`, сверка строки в MySQL (`cmp410_e2e_clean`) до/после.

Важный контекст: `admin/product/*` (create/edit/toggle/add-size/delete-size/add-image/
delete-image/delete) и `admin/category/*`, `admin/brand/*` CRUD **уже** живым прогоном
проверены в CMP-417 (`docs/route-audit/CMP-417-products-report.md`,
`CMP-417-category-brand-report.md`) — здесь не дублируется, кроме случаев, где найден
новый дефект.

| Действие | Маршрут | Факт в БД | Вердикт |
|---|---|---|---|
| Brand create/update/delete | `admin/brand/create,update,delete` | строка создана/обновлена/удалена | **ok** (повторная проверка вслед за CMP-417) |
| Category create/update/delete | `admin/category/create,update,delete` | строка создана/обновлена/удалена | **ok** (повторная проверка вслед за CMP-417) |
| Product create (кириллица без цифр) | `admin/product/create` | slug записан как пустая строка / равен числовому id вместо транслитерации | **wrong_data → исправлено** |
| Product edit/toggle/delete | `admin/product/edit,toggle,delete` | поля обновляются, `is_active` переключается, строка удаляется | **ok** |

### Найденный дефект и фикс

`Product` и `ProductTag` (`backend/modules/catalog/models/Product.php`,
`.../ProductTag.php`) использовали `SluggableBehavior` с `'attribute' => 'name'` напрямую.
Тот же класс бага, что CMP-417 уже нашёл и исправил в `Category`/`Brand`: без
php-intl `yii\helpers\Inflector::transliterate()` не переводит кириллицу, а
`Inflector::slug()` вырезает кириллические буквы регэкспом — товар/тег с чисто
кириллическим названием получал `slug = ''` и падал на unique-валидации при повторе
(второй такой же товар не сохранялся; тестовый товар без транслитерации получил
`slug = '460'`, подставив числовой id вместо текста). Фикс по образцу CMP-417:
`getSlugSource()` транслитерирует кириллицу вручную (карта символов, идентичная
Category/Brand) перед тем, как `SluggableBehavior` прогонит `Inflector::slug()`.

Проверено живым прогоном: товар «Летние кроссовки» (id 394) получил корректный
`slug = 'letnie-krossovki'`. Toggle (`is_active` 1→0) и delete подтверждены отдельными
`SELECT` до/после.

## Итого волны 2б

- **Brand/Category CRUD** — переподтверждён живым прогоном, дефектов нет.
- **Product CRUD (create/edit/toggle/delete)** — 1 новый дефект найден и исправлен
  (slug для кириллических названий), остальное — **ok**.
- Тестовые товары id 393 («Тестовые кроссовки 460», воспроизведение бага) и id 394
  («Летние кроссовки», проверка фикса) удалены через живой `admin/product/delete`,
  подтверждено `SELECT` после очистки (0 строк).

## Честная граница охвата волны 2б

CMP-417 уже покрыл `admin/product/*` (sizes, images) и `admin/category/*`,
`admin/brand/*` CRUD подробным живым прогоном — здесь не повторялось. **Не проверено**
(остаток Приоритета 3 + весь Приоритет 4, перенесено на следующую волну CMP-460):

- `ProductController`: `actionBulkUpdate`, `actionBulkDelete`, `actionBulkPrice`,
  `actionBulkUpdatePrice`, `actionExport`, `actionExportCsv`, `actionClone`,
  `actionSyncPoizon`, `actionUpdatePrice`, `actionUpdateField`, `actionToggleActive`,
  `actionSaveSizesData`, `actionUpdateSizePrice`, `actionSaveField`, `actionInlineUpdate`.
- Приоритет 4 целиком: настройки, пользователи/роли, импорт/экспорт, интеграции
  (вне каталога и данных покупателя).

Дочерние карточки создать штатно не удалось (см. выше про `delegation_cycle`) —
продолжение зафиксировано текстовым TODO в комментарии на CMP-460, а не в отдельных
issue.
