# System Audit -- April 2026

**Date:** 2026-04-20
**Scope:** Full codebase audit of the Yii2 sneaker shop admin panel
**Auditor:** Automated code review

---

## Critical Issues (Security, Data Loss Risk)

### C-01. Hardcoded Database Password in Multiple Scripts
- **Files:**
  - `/scripts/import_from_moysklad.php` (line 32)
  - `/scripts/download_ms_images.php` (line 19)
  - `/scripts/verify_ms_import.php` (line 25)
  - `/fix_orphan_orders.php` (line 7)
- **Severity:** CRITICAL
- **Description:** Database password `'secret'` is hardcoded as a PHP constant in four standalone scripts. Even though the main application uses environment variables, these scripts bypass that entirely. If the repository is ever made public or an attacker gains read access to the source, the database is compromised.
- **Suggested Fix:** Load credentials from `.env` or environment variables in all scripts, as already partially done for MoySklad credentials in `import_from_moysklad.php`. Remove all hardcoded `DB_PASS` constants.

### C-02. CSRF Protection Disabled on 10+ Admin AJAX Actions
- **Files:**
  - `/backend/modules/admin/controllers/CustomerController.php` (line 48-49) -- 11 actions exempted
  - `/backend/modules/admin/controllers/SettingsController.php` (line 152) -- 3 actions exempted
  - `/backend/modules/admin/controllers/OrderController.php` (line 66-68) -- upload-file action
  - `/backend/modules/admin/controllers/PluginController.php` (line 14) -- 8 actions exempted
  - `/backend/modules/admin/controllers/TelegramBotController.php` (line 17) -- entire controller
- **Severity:** CRITICAL
- **Description:** CSRF validation is disabled for a large number of admin POST actions. While the TODO comments acknowledge this and suggest sending X-CSRF-Token via JS, it is currently absent. An attacker can craft a malicious page that triggers state-changing operations (toggle customer status, adjust loyalty points, save settings) in the context of an authenticated admin session.
- **Suggested Fix:** Add CSRF token to all fetch() calls via `X-CSRF-Token` header (token available in `Yii::$app->request->csrfToken`). Remove `enableCsrfValidation = false` overrides.

### C-03. Password Returned in JSON Response (Reset Password)
- **File:** `/backend/modules/admin/controllers/CustomerController.php` (line 323-326)
- **Severity:** CRITICAL
- **Description:** `actionResetPassword()` returns the plaintext generated password in the JSON response AND stores it in the `password` field of the response. This is logged by browsers, proxy servers, and potentially JavaScript error trackers. If the admin network is not fully trusted, the password is exposed in transit logs.
- **Suggested Fix:** Send the new password via email/SMS to the customer. Never return plaintext passwords in HTTP responses. Show only a success message to the admin.

### C-04. Webhook Controller Extends Base Controller (Not BaseAdminController) -- No Auth
- **File:** `/backend/modules/admin/controllers/WebhookController.php` (line 15)
- **Severity:** CRITICAL
- **Description:** The admin `WebhookController` extends `yii\web\Controller` instead of `BaseAdminController`. While it disables CSRF (expected for webhooks), it also has NO authentication at all. The `verifySignature()` method silently passes when no webhook secret is configured (line 28: `return true`). This means any unauthenticated request can trigger `actionDobropost()` or `actionMoysklad()` and modify order data.
- **Suggested Fix:** When `$secret` is empty, reject the request rather than allowing it through. Add IP whitelisting for webhook endpoints. At minimum, log a critical warning and return 403 when no secret is configured.

### C-05. Telegram Bot Webhook Has No Authentication
- **File:** `/backend/modules/admin/controllers/TelegramBotController.php` (line 17-36)
- **Severity:** HIGH
- **Description:** The Telegram webhook endpoint has CSRF disabled and no signature verification. Telegram sends a secret token in the `X-Telegram-Bot-Api-Secret-Token` header, but this controller does not verify it. Anyone who discovers the webhook URL can send fake updates.
- **Suggested Fix:** Verify Telegram's `X-Telegram-Bot-Api-Secret-Token` header against the secret set during webhook registration.

### C-06. Demo Mode Bypasses All Access Controls
- **File:** `/backend/modules/admin/controllers/BaseAdminController.php` (lines 73-79)
- **Severity:** HIGH
- **Description:** When `YII_ENV === 'demo'` or `params['demoMode']` is truthy, all access control checks (adminOnly, financeOnly, procureOnly) are completely bypassed. Any authenticated user gains full admin access. If the demo flag is accidentally left enabled in production, the entire admin panel is unprotected.
- **Suggested Fix:** Remove demo mode from access control. If demo mode is needed, implement it as a separate middleware that restricts write operations rather than granting blanket access.

### C-07. Order File Upload Has No File Type Validation
- **File:** `/backend/modules/admin/controllers/OrderController.php` (lines 1680-1730)
- **Severity:** HIGH
- **Description:** `actionUploadFile()` accepts any file type with no validation on extension or MIME type. An attacker with admin access could upload a PHP file and potentially achieve remote code execution if the uploads directory is served by the web server with PHP execution enabled.
- **Suggested Fix:** Validate file extension against an allowlist (e.g., jpg, jpeg, png, gif, webp, pdf). Check MIME type. Ensure the uploads directory has a `.htaccess` or nginx config that prevents PHP execution.

### C-08. Error Messages Leak Internal Details to Client
- **Files:**
  - `/backend/modules/admin/controllers/SettingsController.php` (lines 61, 83, 126, 222, 266, 300, 359, 398)
  - `/backend/modules/admin/controllers/ProductController.php` (lines 396, 656, 840, 1001, 1158)
  - `/backend/modules/admin/controllers/CustomerController.php` (line 468, 615)
  - `/backend/modules/admin/controllers/OrderController.php` (line 378)
  - Multiple other controllers
- **Severity:** HIGH
- **Description:** Exception messages (`$e->getMessage()`) are returned directly in JSON responses to the client. These can contain SQL error details, file paths, class names, and other internal information that aids attackers in reconnaissance.
- **Suggested Fix:** Log the full exception internally. Return a generic error message to the client. In development mode, include details behind a debug flag.

---

## High Priority (Broken Features, Errors Users See)

### H-01. Missing Controller Action: `dp-test` Route
- **Files referencing non-existent action:**
  - `/backend/modules/admin/views/settings/integrations.php` (line 262)
  - `/backend/modules/admin/views/plugin/dobropost.php` (line 248)
- **Controller checked:** `/backend/modules/admin/controllers/OrderController.php` -- no `actionDpTest()` method
- **Severity:** HIGH
- **Description:** Two views call `fetch('/admin/order/dp-test', ...)` but no `actionDpTest()` exists in the OrderController. Clicking "Test connection" for DobroPost integration will result in a 404 error.
- **Suggested Fix:** Implement `actionDpTest()` in OrderController or move the endpoint to PluginController where other integration tests live.

### H-02. `exit` Used Instead of Proper Yii2 Response in Product Export
- **File:** `/backend/modules/admin/controllers/ProductController.php` (lines 714, 777)
- **Severity:** HIGH
- **Description:** The `exportToCsv()` and `exportToExcel()` methods call `exit;` directly, bypassing Yii2's response lifecycle. This means afterAction behaviors, event handlers, session save, and profiling never run. It also makes the code untestable.
- **Suggested Fix:** Use `Yii::$app->end()` or better yet, return a proper `Yii::$app->response->sendFile()` / `sendContentAsFile()` response.

### H-03. AnalyticsController Overrides BaseAdminController Behaviors Without Calling Parent
- **File:** `/backend/modules/admin/controllers/AnalyticsController.php` (lines 40-57)
- **Severity:** HIGH
- **Description:** `AnalyticsController::behaviors()` returns its own access control rules without merging with `parent::behaviors()`. This means the `VerbFilter` from `BaseAdminController` (which restricts delete to POST) is lost. Additionally, if `$user->isAdmin()` or `$user->isManager()` throws an exception (e.g., identity is null), the matchCallback will throw an uncaught error instead of denying access safely.
- **Suggested Fix:** Merge with `parent::behaviors()` using `array_merge()`, or use `BaseAdminController`'s built-in role checks. Wrap the matchCallback in a try/catch.

### H-04. ReviewController Overrides BaseAdminController Behaviors Without Calling Parent
- **File:** `/backend/modules/admin/controllers/ReviewController.php` (lines 40-55)
- **Severity:** HIGH
- **Description:** Same issue as H-03. The ReviewController defines its own `behaviors()` method without calling `parent::behaviors()`, losing the base controller's VerbFilter and other behaviors.
- **Suggested Fix:** Same as H-03.

### H-05. ImportController Overrides BaseAdminController Access Rules -- No Role Checks
- **File:** `/backend/modules/admin/controllers/ImportController.php` (lines 36-57)
- **Severity:** HIGH
- **Description:** `ImportController::behaviors()` replaces the parent's access control with a rule that allows ANY authenticated user (`roles => ['@']`). This means a user with a basic account (not admin, not manager) can access import functionality, run imports, and modify product data.
- **Suggested Fix:** Restore role-based access checks. Set `$this->adminOnly = true` and use `parent::behaviors()`.

### H-06. CustomerController::actionView Fetches All Orders Into Memory for Statistics
- **File:** `/backend/modules/admin/controllers/CustomerController.php` (lines 198-212)
- **Severity:** HIGH
- **Description:** `actionView()` loads ALL orders for a customer into memory (`->all()`) just to compute count, sum, and min/max. For a customer with thousands of orders, this causes excessive memory usage and slow page loads. The same data could be retrieved with a single aggregate query.
- **Suggested Fix:** Replace the `->all()` call with aggregate queries using `->count()`, `->sum('total_amount')`, `->min('created_at')`, `->max('created_at')`.

### H-07. Product Index Makes 5 Separate COUNT Queries for Stats
- **File:** `/backend/modules/admin/controllers/ProductController.php` (lines 162-168)
- **Severity:** MEDIUM
- **Description:** The product index page runs 5 separate `COUNT(*)` queries (`total`, `active`, `inactive`, `inStock`, `outOfStock`) that could be combined into a single query with conditional aggregation, reducing DB round-trips by 80%.
- **Suggested Fix:** Use a single query: `SELECT COUNT(*) total, SUM(is_active) active, SUM(NOT is_active) inactive, ...`

---

## Medium Priority (Code Quality, Performance)

### M-01. N+1 Query Risk in Product Export
- **File:** `/backend/modules/admin/controllers/ProductController.php` (lines 900-925)
- **Severity:** MEDIUM
- **Description:** `actionExportCsv()` calls `Product::find()->with(['brand', 'category'])` but then accesses `$p->brand->name` in the loop. The `with()` is correct here, BUT no `->limit()` is set. For a large catalog (10k+ products), loading all products into memory will cause an OOM error.
- **Suggested Fix:** Add batching (e.g., `each(100)` or `batch(100)`) to process products in chunks.

### M-02. `save(false)` Used Extensively -- Validation Bypassed
- **Files:** 40+ occurrences across backend controllers and services
  - `/backend/modules/admin/controllers/OrderController.php` (lines 620, 854, 943, 1219, 1359, 1622, 1723)
  - `/backend/modules/admin/controllers/CustomerController.php` (lines 281, 299, 322, 701, 720)
  - `/backend/modules/admin/controllers/ProductController.php` (lines 347, 391, 819, 830, 938, 952, 998)
  - `/backend/shared/services/MoySkladService.php` (lines 393, 546, 624)
- **Severity:** MEDIUM
- **Description:** Calling `save(false)` skips model validation, which can lead to invalid data being persisted (empty required fields, invalid formats, constraint violations at DB level). While some cases are justified (e.g., toggling a boolean), many are used as a shortcut.
- **Suggested Fix:** Review each `save(false)` call. Replace with `save()` where possible. Where skipping validation is intentional, add a comment explaining why.

### M-03. Empty Catch Blocks Swallow Errors
- **Files:**
  - `/scripts/import_from_moysklad.php` (line 559) -- `catch (Throwable) {}`
  - `/frontend/controllers/AccountController.php` (line 313) -- `catch (\Exception $e2) {}`
  - `/backend/modules/admin/controllers/CustomerController.php` (line 159) -- `catch (\Exception $e) {}`
  - `/backend/modules/admin/controllers/PluginController.php` (line 126) -- `catch (\Exception $e) {}`
  - `/backend/modules/admin/views/order/create.php` (line 28) -- `catch (\Exception $e) {}`
  - `/backend/modules/admin/views/layouts/admin.php` (line 106) -- `catch (\Exception $e) {}`
  - `/backend/modules/admin/views/layouts/main.php` (line 83) -- `catch (\Exception $e) {}`
- **Severity:** MEDIUM
- **Description:** Exceptions are caught and silently discarded. This makes debugging extremely difficult since errors produce no log entries, no user feedback, and no trace.
- **Suggested Fix:** At minimum, log the exception: `Yii::warning($e->getMessage(), 'category')`. In user-facing contexts, provide a fallback value and log.

### M-04. Duplicate Webhook Controllers
- **Files:**
  - `/api/controllers/WebhookController.php` -- handles DobroPost webhooks via `/api/webhook/dobropost`
  - `/backend/modules/admin/controllers/WebhookController.php` -- ALSO handles DobroPost webhooks
- **Severity:** MEDIUM
- **Description:** Two separate controllers handle the same webhook functionality (DobroPost status updates). The logic is duplicated and could diverge, causing inconsistent behavior depending on which URL is configured at the provider.
- **Suggested Fix:** Consolidate to a single webhook controller. If both URLs must remain active for backwards compatibility, have one redirect to the other.

### M-05. FinanceController Uses Raw Table Names Without Prefix
- **File:** `/backend/modules/admin/controllers/FinanceController.php` (lines 116-120, 164-205, 254-268, 294-308)
- **Severity:** MEDIUM
- **Description:** Queries reference tables as `'expense'`, `'payment'`, `'order_item'`, `'\`order\`'` directly instead of using `{{%table_name}}` table prefix syntax. This will break if the application is configured with a table prefix.
- **Suggested Fix:** Use `{{%expense}}`, `{{%payment}}`, `{{%order_item}}`, `{{%order}}` consistently.

### M-06. Order Controller KPI Queries Are Not Cached
- **File:** `/backend/modules/admin/controllers/OrderController.php` (lines 221-263)
- **Severity:** MEDIUM
- **Description:** The order index page runs 6+ aggregate queries for KPI summary (today's orders, week's orders, month's orders, processed count, shipped count, customs cleared count) plus the duplicate tracking query. These are executed on every page load without caching, adding ~50-100ms per request.
- **Suggested Fix:** Cache these aggregates with a 60-300 second TTL, similar to how statusCounts and monthlySummary are cached.

### M-07. Inconsistent Table Naming Convention
- **Files:** Various model files and migrations
- **Severity:** MEDIUM
- **Description:** Tables use a mix of singular (`product`, `order`, `customer`, `brand`, `category`, `cart`) and plural (`customer_notes`, `customer_tags`) naming. Some use underscores (`product_size`, `order_item`), which is consistent, but the singular/plural mismatch makes the schema less predictable.
- **Suggested Fix:** Adopt a single convention (recommended: singular for entity tables, e.g., `customer_note` instead of `customer_notes`). Document the convention. Apply to new tables going forward.

### M-08. ProductController::actionBulkDelete Loads Each Product Individually
- **File:** `/backend/modules/admin/controllers/ProductController.php` (lines 639-651)
- **Severity:** MEDIUM
- **Description:** `actionBulkDelete()` iterates through IDs, loading each product with `Product::findOne($id)`, deleting related records, and then deleting the product. For 50+ products, this creates 50+ SELECT queries plus 150+ DELETE queries. Could be done with batch operations.
- **Suggested Fix:** Use `ProductImage::deleteAll(['product_id' => $ids])`, `ProductSize::deleteAll(['product_id' => $ids])`, `Product::deleteAll(['id' => $ids])` for a batch approach (note: this skips beforeDelete/afterDelete events, so evaluate if those are needed).

### M-09. DevToolsController Runs `SHOW PROCESSLIST` Without Access Restriction
- **File:** `/backend/modules/admin/controllers/DevToolsController.php` (line 94)
- **Severity:** MEDIUM
- **Description:** The `checkDatabase()` method runs `SHOW PROCESSLIST` which exposes all active database connections, queries, and user info. While the controller inherits `BaseAdminController` auth, `$adminOnly` is NOT set to true, so any authenticated admin user (including managers and logists) can see this sensitive information.
- **Suggested Fix:** Set `protected bool $adminOnly = true;` in DevToolsController.

### M-10. Missing `$adminOnly = true` on Sensitive Controllers
- **Files:**
  - `/backend/modules/admin/controllers/DevToolsController.php` -- system diagnostics
  - `/backend/modules/admin/controllers/SettingsController.php` -- system settings
  - `/backend/modules/admin/controllers/UserController.php` -- user management
- **Severity:** MEDIUM
- **Description:** Several controllers that manage sensitive operations (system diagnostics, settings, user management) do not set `$adminOnly = true`, meaning managers and logists may access them through the base `@` (authenticated) access control.
- **Suggested Fix:** Add `protected bool $adminOnly = true;` to all controllers that should be admin-only.

### M-11. `env()` Function Used Without Definition in Infrastructure Configs
- **Files:**
  - `/infrastructure/logging/structured.php` (lines 13, 18, 19)
  - `/infrastructure/monitoring/metrics.php` (line 7)
  - `/infrastructure/monitoring/health.php` (line 21)
  - `/infrastructure/queue/rabbitmq.php` (lines 8-11)
  - `/infrastructure/features/flags.php` (lines 10, 16, 22, 27, 33)
- **Severity:** MEDIUM
- **Description:** These config files call `env()` which is not a standard PHP function. If `vlucas/phpdotenv` or a custom `env()` helper is not loaded before these configs, they will throw a fatal error.
- **Suggested Fix:** Either ensure `env()` is defined in the bootstrap, or use `getenv()` with a fallback operator: `getenv('KEY') ?: 'default'`.

### M-12. RabbitMQ Config Has Default Guest Credentials
- **File:** `/infrastructure/queue/rabbitmq.php` (lines 10-11)
- **Severity:** MEDIUM
- **Description:** The RabbitMQ configuration defaults to `user: 'guest'` and `password: 'guest'` via `env()` fallback. If the environment variables are not set, the application connects with default credentials, which is a security concern in production.
- **Suggested Fix:** Remove default credentials. Require explicit environment variable configuration and fail with a clear error if not set.

### M-13. Product Export Uses `header()` Directly Instead of Yii Response
- **File:** `/backend/modules/admin/controllers/ProductController.php` (lines 689-690, 771-773)
- **Severity:** MEDIUM
- **Description:** `exportToCsv()` and `exportToExcel()` use raw PHP `header()` calls instead of Yii2's `Yii::$app->response->headers->set()`. This bypasses Yii's response pipeline and can conflict with output buffering.
- **Suggested Fix:** Use `Yii::$app->response->sendContentAsFile()` or `sendFile()` for proper response handling.

### M-14. OrderController::actionBulkAction Performs Mass Status Update Without History Logging
- **File:** `/backend/modules/admin/controllers/OrderController.php` (lines 884-890)
- **Severity:** MEDIUM
- **Description:** `actionBulkAction()` with `change_status` uses `Order::updateAll()` which bypasses model events and does not create OrderHistory entries. The status change of potentially hundreds of orders goes unrecorded.
- **Suggested Fix:** Iterate through orders and use the existing status change logic that logs to OrderHistory, or at least create a single bulk log entry.

### M-15. OrderController::actionBulkUpdateStatus Also Skips History
- **File:** `/backend/modules/admin/controllers/OrderController.php` (lines 1244-1264)
- **Severity:** MEDIUM
- **Description:** Same issue as M-14. A second bulk status update endpoint that uses `updateAll()` without creating history records.
- **Suggested Fix:** Consolidate with actionBulkAction and add history logging.

---

## Low Priority (Cleanup, Cosmetic)

### L-01. Unused Import: `yii\web\Controller` in AnalyticsController
- **File:** `/backend/modules/admin/controllers/AnalyticsController.php` (line 30)
- **Severity:** LOW
- **Description:** Imports `yii\web\Controller` but extends `BaseAdminController`. The import is unused.
- **Suggested Fix:** Remove `use yii\web\Controller;`.

### L-02. Unused Import: `yii\web\Controller` in ReviewController
- **File:** `/backend/modules/admin/controllers/ReviewController.php` (line 29)
- **Severity:** LOW
- **Description:** Same as L-01.
- **Suggested Fix:** Remove the unused import.

### L-03. Duplicate `actionExportCsv` and `exportToCsv` in ProductController
- **File:** `/backend/modules/admin/controllers/ProductController.php` (lines 663-715 and 900-926)
- **Severity:** LOW
- **Description:** Two separate methods handle CSV export with slightly different implementations. `exportToCsv()` (private) uses `fputcsv` and `exit;`, while `actionExportCsv()` (public action) manually builds CSV with semicolons and BOM. The first is called from `actionExport()`, the second is a standalone action.
- **Suggested Fix:** Consolidate into a single CSV export implementation.

### L-04. Commented-Out Code Block in actionCreate (OrderController)
- **File:** `/backend/modules/admin/controllers/OrderController.php` (lines 369-371)
- **Severity:** LOW
- **Description:** Empty block with a comment `// JavaScript очистит localStorage` -- no-op code that should be removed.
- **Suggested Fix:** Remove the dead code block.

### L-05. Mixed Russian/English in Code Comments
- **Files:** Most controllers and views
- **Severity:** LOW
- **Description:** Code comments alternate between Russian and English throughout the codebase. While this is a style issue, it makes the codebase harder to navigate for new developers.
- **Suggested Fix:** Adopt a single language for comments (English recommended for international maintainability).

### L-06. `Brand` and `SizeGrid` Models Imported But May Not Exist
- **File:** `/backend/modules/admin/controllers/ProductController.php` (lines 45-48)
- **Severity:** LOW
- **Description:** `Brand` and `SizeGrid` are imported from `app\backend\modules\catalog\models` but there are no corresponding model files found at the expected paths. These may exist under different namespaces or may cause autoloading failures.
- **Suggested Fix:** Verify the model paths and fix imports if needed.

### L-07. Inconsistent Error Response Formats
- **Files:** Various admin controllers
- **Severity:** LOW
- **Description:** Error responses use inconsistent key names: some use `'message'`, some use `'error'`, some use `'errors'`. This makes client-side error handling unreliable.
- **Suggested Fix:** Standardize on `{success: bool, message: string, errors?: object}` for all JSON responses.

### L-08. `WebhookController` (admin) Uses `file_get_contents('php://input')` Instead of Yii Request
- **File:** `/backend/modules/admin/controllers/WebhookController.php` (lines 52, 150)
- **Severity:** LOW
- **Description:** Uses `file_get_contents('php://input')` instead of `Yii::$app->request->getRawBody()`. While functionally equivalent, the Yii method is preferred as it integrates with the framework's request lifecycle.
- **Suggested Fix:** Replace with `Yii::$app->request->getRawBody()`.

### L-09. `TelegramBotController::actionWebhook()` Returns String Instead of JSON
- **File:** `/backend/modules/admin/controllers/TelegramBotController.php` (line 36)
- **Severity:** LOW
- **Description:** Returns bare string `'OK'` without setting response format. Telegram expects a 200 response regardless of body content, but the inconsistency with other controllers is notable.
- **Suggested Fix:** Set `Yii::$app->response->format = Response::FORMAT_RAW;` and return `'OK'` explicitly.

### L-10. Docker Compose Default Password
- **File:** `/docker-compose.yml` (line 28)
- **Severity:** LOW (development only)
- **Description:** `DB_PASSWORD` defaults to `secret` in docker-compose. While this is standard for development environments, it should be documented that production must override this.
- **Suggested Fix:** Add a comment or `.env.example` documenting required production overrides.

### L-11. Redundant `$layout = 'admin'` in Child Controllers
- **Files:**
  - `/backend/modules/admin/controllers/AnalyticsController.php` (line 38)
  - `/backend/modules/admin/controllers/ReviewController.php` (line 39)
- **Severity:** LOW
- **Description:** These controllers set `$layout = 'admin'` which is already set in `BaseAdminController` (line 48).
- **Suggested Fix:** Remove the redundant `$layout` assignment.

### L-12. `SizeGrid` Model Referenced Without Import Verification
- **File:** `/backend/modules/admin/controllers/ProductController.php` (line 48)
- **Severity:** LOW
- **Description:** The import `use app\backend\modules\catalog\models\SizeGrid;` may be referencing a model that was moved or renamed to `SizeGridItem`.
- **Suggested Fix:** Verify model exists at the imported path.

### L-13. Unreachable Code After `findModel()` in actionUploadFile
- **File:** `/backend/modules/admin/controllers/OrderController.php` (lines 1684-1686)
- **Severity:** LOW
- **Description:** The check `if (!$order)` after `$this->findModel($id)` is unreachable because `findModel()` already throws `NotFoundHttpException` when the model is not found.
- **Suggested Fix:** Remove the redundant null check.

### L-14. Potential Integer Overflow in `actionClone` Slug Generation
- **File:** `/backend/modules/admin/controllers/ProductController.php` (line 814)
- **Severity:** LOW
- **Description:** Uses `time()` in slug generation which works but creates excessively long slugs. Also, there is no uniqueness check on the generated slug.
- **Suggested Fix:** Use a shorter random suffix and check for uniqueness before saving.

### L-15. Missing `AccessControl` Import in FinanceController
- **File:** `/backend/modules/admin/controllers/FinanceController.php`
- **Severity:** LOW
- **Description:** FinanceController relies on `$financeOnly = true` from BaseAdminController but does not import or configure AccessControl itself. The finance access check depends entirely on the base controller working correctly.
- **Suggested Fix:** Verify the `canAccessFinance()` method correctly restricts access to the intended roles.

### L-16. `created_at` Stored as Unix Timestamp in Some Tables, Datetime String in Others
- **Files:** Various models and controllers
  - Orders: `created_at` is a Unix timestamp (used with `FROM_UNIXTIME()`)
  - Payments: `created_at` appears to be a datetime string (filtered with `>=` on date strings)
  - Expenses: `created_at` appears to be a datetime string
- **Severity:** LOW
- **Description:** Inconsistent timestamp formats across tables make joins and queries more complex and error-prone.
- **Suggested Fix:** Standardize on one format (recommended: Unix timestamps for consistency with Yii2's TimestampBehavior).

### L-17. Multiple Fetch Calls Without Error Handling in Views
- **Files:** Multiple view files in `/backend/modules/admin/views/`
  - `plugin/currency.php` (line 66) -- bare `fetch()` with no `.catch()`
  - `order/create-wizard.php` (line 660) -- `.catch(function(){})` empty handler
- **Severity:** LOW
- **Description:** JavaScript fetch calls in views either lack error handling entirely or have empty catch blocks, leaving users with no feedback when network requests fail.
- **Suggested Fix:** Add `.catch()` handlers that show user-friendly error messages.

### L-18. `$this->enableCsrfValidation` Set in `beforeAction` Instead of `behaviors`
- **Files:**
  - `/backend/modules/admin/controllers/OrderController.php` (line 69)
  - `/backend/modules/admin/controllers/CustomerController.php` (line 51)
  - `/backend/modules/admin/controllers/PluginController.php` (line 18)
- **Severity:** LOW
- **Description:** Setting `enableCsrfValidation` in `beforeAction()` works but is fragile. If `beforeAction()` is overridden in a subclass without calling parent, the CSRF exemption may be lost.
- **Suggested Fix:** Consider using a class property override or Yii2's `$csrfParam` configuration for a more robust approach.

### L-19. ProductController Has Redundant `actionToggle` and `actionToggleActive`
- **File:** `/backend/modules/admin/controllers/ProductController.php` (lines 342-353, 943-953)
- **Severity:** LOW
- **Description:** Two different actions toggle the active status of a product. `actionToggle($id)` uses the URL parameter, while `actionToggleActive()` parses JSON body. Both do the same thing with different input mechanisms.
- **Suggested Fix:** Consolidate into one action that handles both input methods.

### L-20. Redundant `actionSaveField` and `actionInlineUpdate` in ProductController
- **File:** `/backend/modules/admin/controllers/ProductController.php` (lines 1013-1066, 1073-1143)
- **Severity:** LOW
- **Description:** `actionSaveField()` and `actionInlineUpdate()` largely duplicate functionality for updating product fields via AJAX. The inline update adds size editing but duplicates the product field validation and saving logic.
- **Suggested Fix:** Have `actionSaveField()` call `actionInlineUpdate()` internally, or consolidate into one endpoint.

---

## Summary

| Severity | Count |
|----------|-------|
| Critical | 8     |
| High     | 7     |
| Medium   | 15    |
| Low      | 20    |
| **Total**| **50**|

### Top 5 Actions to Take Immediately

1. **Remove hardcoded credentials** from all standalone scripts (C-01)
2. **Implement CSRF token in JS fetch calls** and re-enable CSRF validation (C-02)
3. **Add authentication to webhook controllers** -- reject requests when secret is not configured (C-04, C-05)
4. **Stop returning plaintext passwords** in API responses (C-03)
5. **Add file type validation** to file upload endpoint (C-07)

### Recurring Patterns to Address Systemically

- **`save(false)` abuse**: Establish a team guideline -- only use when toggling boolean fields or during batch imports with pre-validated data.
- **Exception messages in responses**: Create a helper method like `jsonError($message, $exception = null)` that logs details internally and returns a safe message.
- **CSRF bypass**: Create a JavaScript helper that automatically includes CSRF tokens in all fetch requests.
- **Missing `parent::behaviors()` calls**: Controllers that override `behaviors()` must merge with parent to preserve VerbFilter and other base behaviors.
