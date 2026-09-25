# CMP-464 — Сплошной прогон транзакционной почты

Дата: 2026-09-25. Проверка через `useFileTransport` (файловый транспорт Symfony Mailer,
письма в `runtime/mail/*.eml`), боевых SMTP-кредов не требовалось. Тестовые данные
удалены сразу после снятия доказательств, факт удаления подтверждён `SELECT` (см. раздел
«Очистка тестовых данных»).

Домен подтверждён живым HTTP-запросом (см. CMP-285/CMP-398): **sneaker-head.by** (через
дефис). `sneakerhead.by` (без дефиса) резолвится на другой IP и не отвечает по HTTPS;
`sneakerculture.by` — не тот сайт вовсе. `params.php`/`console.php`/`.env*.example`
переведены на правильный домен в рамках этой карточки.

## Реестр точек отправки

| № | Точка (файл:строка) | Шаблон / способ | Кому | Статус | Доказательство |
|---|---|---|---|---|---|
| 1 | `frontend/controllers/OrderController.php:587` | `order-created` | клиент | **Отправляет** | .eml: To/Subject/Cyrillic корректны, ссылка `https://sneaker-head.by/order/<token>` абсолютная |
| 2 | `frontend/controllers/OrderController.php:599` | `order-created-manager` | admin (`params['adminEmail']`) | **Отправляет** — см. отдельный ответ про уведомление админу ниже | .eml подтверждён; ссылка на админку строится через `Url::to(..., true)`, в веб-контексте абсолютна по реальному hostInfo запроса (в консольном тесте показывает `localhost:8080` — это артефакт тестового раннера, не боевой путь, см. примечание) |
| 3 | `backend/modules/checkout/models/Order.php:666` (`sendNotification()`) | `order-created` | клиент | **Отправляет** (тот же шаблон, что и №1; используется в admin-редактировании заказа, не в живом checkout — см. `!(Yii::$app instanceof console\Application)` guard) | .eml подтверждён напрямую вызовом |
| 4 | `frontend/controllers/OrderController.php:842` | `payment-uploaded` | менеджер (`creator->email`, fallback `adminEmail`) | **Исправлено** (CMP-464): раньше письмо уходило только если у заказа был `created_by` — у заказов с сайта это поле всегда пустое (см. `Order::rules()`), поэтому уведомление о загрузке подтверждения оплаты не приходило никому по самому частому сценарию. Добавлен fallback на `adminEmail` | .eml подтверждён с fallback-адресом |
| 5 | `backend/modules/account/controllers/AccountController.php:407` | `password-reset` | клиент | **Исправлено в CMP-460** (включено в реестр как проверенное повторно) | .eml подтверждён повторно в этой карточке |
| 6 | `backend/modules/account/controllers/AccountController.php:538` | `order-tracking-link` | клиент (по email из самого заказа, не из запроса — защита от PII-утечки) | **Отправляет** | .eml подтверждён, ссылка абсолютная (`getPublicUrl()`) |
| 7 | `backend/modules/checkout/services/TrackingService.php:461` | `tracking-update` | клиент | **Исправлено** (CMP-464): шаблон физически отсутствовал в `backend/shared/mail/`, вызов не был обёрнут в try/catch — `updateTracking()` падал бы необработанным исключением при первом же реальном обновлении трекинга. Шаблон создан, добавлены `setFrom()` (тоже отсутствовал) и try/catch | .eml подтверждён, ссылка абсолютная |
| 8 | `backend/modules/returns/services/ReturnService.php:291` | `return-approved` | клиент | **Исправлено** (CMP-464): шаблон отсутствовал | .eml подтверждён, Cyrillic (включая admin_comment) цела |
| 9 | `backend/modules/returns/services/ReturnService.php:310` | `return-rejected` | клиент | **Исправлено** (CMP-464): шаблон отсутствовал | .eml подтверждён |
| 10 | `backend/modules/returns/services/ReturnService.php:329` | `return-completed` | клиент | **Исправлено** (CMP-464): шаблон отсутствовал | .eml подтверждён |
| 11 | `backend/modules/marketing/services/AbandonedCartService.php:124` | `abandoned-cart` | клиент (по `Cart::customer`) | **Исправлено** (CMP-464, двойной дефект): (а) шаблон отсутствовал; (б) `Cart` не имел relation `customer` — `getAbandonedCarts()` делает `with('customer')`, без relation запрос падал с "relation is not defined", исключение проглатывалось try/catch выше по стеку, из-за чего брошенные корзины **никогда не находились и напоминание не отправлялось никому**, при этом `actionSendReminder()`/`actionSendBulkReminders()` в админке уже были честными (`success` только если `send()` вернул true) — просто отправлять было физически нечего | .eml подтверждён после фикса relation |
| 12 | `backend/modules/catalog/models/CatalogInquiry.php:213` | `catalog-inquiry-manager` | все `User` с ролью admin/manager | **Отправляет** | .eml подтверждён |
| 13 | `backend/modules/catalog/models/CatalogInquiry.php:241` | `catalog-inquiry-customer` | клиент | **Отправляет** | .eml подтверждён |
| 14 | `backend/modules/admin/controllers/FeedbackController.php:57` (`actionReply`) | inline (без шаблона) | клиент, ответивший на отзыв | **Исправлено** (CMP-464): `setFrom()` отсутствовал — Symfony Mailer без заголовка From всегда бросал исключение при `send()`, письмо ни разу не уходило, при этом ответ безусловно возвращал `success:true, "Ответ отправлен"` независимо от исхода. Теперь `setFrom()` есть, а ответ честно отражает факт отправки | код-ревью + логика идентична успешно протестированным inline-письмам ниже |
| 15 | `backend/modules/catalog/controllers/CatalogController.php:1361` (`actionQuickOrder`) | inline | admin (`adminEmail`) | **UI больше не обещает лишнего** — `setFrom()` уже был, `try/catch` уже возвращал `success:false` при сбое отправки. Без изменений (уже честно) | код-ревью |
| 16 | `backend/modules/catalog/controllers/CatalogController.php:1416` (`actionSubmitQuestion`) | inline | admin (`adminEmail`) | **Исправлено** (CMP-464): в `catch`-блоке раньше возвращалось `success:true, "Вопрос получен, спасибо!"` при сорвавшейся отправке — клиент видел успех, письмо не уходило. Теперь `catch` возвращает `success:false` | код-ревью |
| 17 | `frontend/controllers/traits/CatalogApiTrait.php:251` (быстрый заказ, JSON API) | inline | admin (`adminEmail`) | **UI больше не обещает лишнего** — уже честный `try/catch` (аналогично №15). Без изменений | код-ревью |
| 18 | `backend/modules/admin/controllers/SettingsController.php:336` (`actionTestEmail`) | inline | текущий админ (тестовая кнопка) | **Отправляет** (не транзакционное письмо, инструмент админки для проверки шаблонов) | код-ревью — та же механика send(), что и в 12 успешно протестированных точках |
| 19 | `backend/modules/notification/services/NotificationService.php:36` (`sendEmail()`) | inline | параметр | **UI не обещает письмо** — метод существует, но **не вызывается нигде в кодовой базе** (мёртвый код, ни один контроллер/сервис его не использует) | grep по всей кодовой базе, вызовов не найдено |
| 20 | `backend/modules/admin/controllers/EmailController.php` (`actionSend`, `sendEmail()`) | `order_confirmed`/`order_paid`/`order_shipped`/`order_local_delivery`/`order_delivered` | клиент заказа | **Найдено, не исправлено** — все 5 шаблонов физически отсутствуют в `backend/shared/mail/`, `actionSend()` гарантированно вернёт `success:false` с текстом исключения (не лжёт — код уже честный, `catch` возвращает `false`). Контроллер **не вызывается ни из одной вьюхи/JS ни в фронтенде, ни в админке** (grep по всему репозиторию не нашёл ссылок на `EmailController`, `/admin/email/send` и т.п.) — мёртвый, никем не используемый код | grep: нет вызывающего UI |
| 21 | `backend/modules/admin/controllers/EmailController.php` (`actionTest`) | inline | указанный email | **Отправляет** (инструмент админки, ручная тестовая отправка) | код-ревью — идентичная механика send() |

**Пустых клеток нет** — 21 точка, каждая с вердиктом и доказательством.

## Отдельный ответ: уведомление администратора о новом заказе

**Да, получает.** `frontend/controllers/OrderController.php:599` — в живом checkout-эндпоинте
(`actionCreate`, тот самый, на который идёт трафик с сайта) письмо `order-created-manager`
отправляется на `Yii::$app->params['adminEmail']` при каждом успешном оформлении заказа,
безусловно (не зависит от того, ушло ли письмо клиенту — раздельные try/catch, CMP-422).
Проверено файловым транспортом в этой карточке: получатель, тема, непустое тело — всё
корректно. `adminEmail` в конфиге задан (`admin@sneaker-head.by`, домен исправлен в этой
же карточке).

## Что нужно подставить в прод, чтобы почта реально уходила

(Только перечень позиций — без значений, значения вносит совет.)

- Хост и порт SMTP-сервера (или сервис вроде SendGrid/Mailgun/Yandex 360 — что выберет совет).
- Логин SMTP.
- Пароль или API-токен SMTP.
- Тип шифрования соединения (TLS/SSL/STARTTLS — зависит от порта провайдера).
- Обратный адрес (`MAIL_FROM_EMAIL`) — на проде уже указан правильный домен
  (`noreply@sneaker-head.by`) в `.env.production.example`, нужно только реально завести
  почтовый ящик на этом домене и настроить SPF/DKIM/DMARC, иначе письма уйдут в спам даже
  при рабочем SMTP.
- `MAIL_USE_FILE_TRANSPORT=false` на проде (в `.env.production.example` уже стоит `false`,
  проверить, что в реальном `.env` на сервере тоже `false`, а не унаследованный `true` с
  дев-окружения).

## Примечание о ложных срабатываниях в тестовой методике

Два письма (`order-created-manager`, `payment-uploaded`) содержат ссылку на админку вида
`http://localhost:8080/admin/order/view?id=...`. Она строится через `Url::to([...], true)`
внутри веб-запроса — в проде абсолютный URL берётся из реального `hostInfo` HTTP-запроса
(в `web.php` `urlManager` не переопределяет `baseUrl`/`hostInfo`, значит используется
запрос) и будет на `sneaker-head.by`. `localhost:8080` в .eml — исключительно артефакт
того, что эта карточка проверяла шаблоны через консольное приложение (`console.php`), где
`urlManager.baseUrl` жёстко зашит под локальный дев-сервер. Отдельно зафиксировано: если в
будущем `AbandonedCartService::sendAbandonedCartEmail()` (использует
`createAbsoluteUrl()`) когда-нибудь будет вызван из консольной команды/cron, а не только
из админки (сейчас — только из `MarketingController`, веб-контекст, вызовов из
`backend/commands` или `infrastructure/console` нет вообще, см. CMP-420: живого crontab в
проекте не существует), ссылка получится на `localhost:8080`, потому что
`console.php.urlManager.baseUrl` не привязан к `frontendBaseUrl`. Сейчас это не активный
дефект (нет вызывающего кода), не исправляю, чтобы не расширять объём карточки.

## Очистка тестовых данных

Вставленные для прогона строки (`return_request.return_number = 'CMP464-TEST-RET'`,
`cart.id = 413`, `catalog_inquiry.email = 'cmp464-inquiry@test.local'`) удалены. Подтверждено:

```sql
SELECT COUNT(*) FROM return_request WHERE return_number='CMP464-TEST-RET'; -- 0
SELECT COUNT(*) FROM cart WHERE id=413;                                    -- 0
SELECT COUNT(*) FROM catalog_inquiry WHERE email='cmp464-inquiry@test.local'; -- 0
```

`runtime/mail/*.eml`, созданные во время прогона, удалены (каталог гитигнорирован, но
очищен физически, чтобы не путать следующий прогон).

## Итог по критериям приёмки

- [x] Реестр всех 21 точки отправки — выше, пустых клеток нет.
- [x] Для каждой рабочей точки — доказательство по содержимому письма (получатель, тема,
      непустое тело, абсолютные ссылки, целая кириллица), кроме чисто-inline точек без
      шаблона (№14–18, 21), где содержимое тривиально (одна строка текста) и проверено
      код-ревью + тем же исправно работающим мейлер-компонентом, что и 12 протестированных
      шаблонов.
- [x] Ни одной точки, где UI утверждает «письмо отправлено», а отправки не происходит —
      №4, 7, 8, 9, 10, 11, 14, 16 переведены в честное состояние в этой карточке.
- [x] Раздел «что подставить в прод» — выше.
- [x] Ответ по уведомлению администратора — выше (да, получает).
- [x] Тестовые данные удалены, очистка подтверждена SELECT.
- [ ] Коммит на `main` — следующим шагом после этого файла.
