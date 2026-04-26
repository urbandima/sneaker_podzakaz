# QA Frontend — Round 1
**Date**: 2026-04-26  
**Tester**: Claude (automated)  
**Tool**: Chrome MCP + curl + JS console inspection  
**Server**: PHP built-in `localhost:8080`

---

## Score Summary

| Category | Score | Notes |
|----------|-------|-------|
| Функциональность | 88/100 | Cart/checkout/order work; 4 bugs fixed |
| Дизайн | 90/100 | Consistent; brand logo fix removes broken image |
| UX | 85/100 | Login email type fixed; mobile menu phone correct |
| Производительность | 68/100 | Dev-env limitation (TBT); no regressions |
| Доступность | 99/100 | From Lighthouse wave-2; login email type improved |
| SEO | 100/100 | All public pages indexed; admin noindex correct |
| Mobile | 87/100 | Mobile menu contacts fixed (correct phone, no ghost Telegram) |
| **ИТОГО** | **88/100** | 4 bugs fixed, 0 criticals remaining |

---

## Pages Checked

| Page | HTTP | PHP Errors | Console Errors | Notes |
|------|------|------------|----------------|-------|
| `/` (home) | 200 | ✓ | ✓ | 21 imgs, no broken |
| `/catalog` | 200 | ✓ | ✓ | 156 cards, filters/sort OK |
| `/catalog?q=samba` | 200 | ✓ | — | Search returns results |
| `/catalog?brand=adidas` | 200 | ✓ | — | Brand filter OK |
| `/catalog?category=sneakers` | 200 | ✓ | — | Category filter OK |
| `/catalog/product/nike-air-force-1` | 200 | ✓ | ✓ | 23 sizes, price, delivery section |
| `/catalog/product/jordan-4-retro` | 200 | ✓ | — | OK |
| `/cart` | 301→checkout | ✓ | — | Correct: /cart permanent-redirects to /checkout |
| `/checkout` | 200 | ✓ | ✓ | 8-item summary, total 2568 BYN, all form fields present |
| `/account/login` | 200 | ✓ | ✓ | **Fixed**: email type |
| `/account/register` | 200 | ✓ | ✓ | All fields correct, terms checkbox present |
| `/account/wishlist` | 302→login | ✓ | — | Correct: guest redirected to login |
| `/account/profile` | 302→login | ✓ | — | Correct |
| `/account/orders` | 302→login | ✓ | — | Correct |
| `/account/find-orders` | 200 | ✓ | ✓ | Form: email + phone, both correct types |
| `/brands` | 200 | ✓ | — | 266 brand-card matches |
| `/brands/adidas` | 200 | ✓ | — | OK |
| `/sale` | 200 | ✓ | — | 20 items |
| `/about` | 200 | ✓ | — | OK |
| `/contacts` | 200 | ✓ | — | Phone +375 44 700-90-01 (from settings) |
| `/feedback` | 200 | ✓ | — | OK |
| `/privacy` | 200 | ✓ | — | OK |
| `/offer-agreement` | 200 | ✓ | — | OK |
| `/payment-instruction` | 200 | ✓ | — | OK |
| `/payment-terms` | 200 | ✓ | — | **Fixed**: real phone now |
| `/delivery-terms` | 200 | ✓ | — | OK |
| `/return-policy` | 200 | ✓ | — | OK |
| `/order/4a4079e06eb2f7ba7a12821c7c58a3f6` | 200 | ✓ | — | Order #DEMO-001 shows correctly |
| `/order/.../upload` | 302→view | ✓ | — | Correct: GET redirects to order view |
| `/404` | 404 | ✓ | — | Correct 404 page |

---

## Bugs Found and Fixed

### BUG-01: Mobile menu — hardcoded test phone and broken Telegram link
**File**: `frontend/views/layouts/main.php:249–250`  
**Severity**: High (wrong contact info shown to all mobile users)  
**Problem**: Mobile burger menu footer showed `+375 (29) 123-45-67` (placeholder/test number) and `https://t.me` (no channel handle). Both hardcoded, ignoring company settings.  
**Fix**: Phone now reads from `$company['phone']`. Telegram link only rendered when `social/telegram` setting is non-empty (currently hidden since no Telegram handle is set).

### BUG-02: Product page brand logo — empty src caused browser to load current page
**File**: `frontend/views/catalog/product.php:329`  
**Severity**: Medium (visible broken image slot on every product page)  
**Problem**: `<img src="<?= $product->brand->logo_url ?? '' ?>">` — when `logo_url` is null the `src=""` attribute causes the browser to request the current page URL as an image resource. The server returns HTTP 200 with HTML body, browser fails to decode as image: renders 0×0 pixel broken image and wastes a full page request.  
**Fix**: `<img>` tag is now only rendered when `logo_url` is non-empty.

### BUG-03: Login form — email field used `type="text"` instead of `type="email"`
**File**: `frontend/views/account/login.php:47`  
**Severity**: Low (UX: no mobile email keyboard, no browser email validation)  
**Problem**: Yii2 `->textInput()` defaults to `type="text"`. The `inputmode="email"` was present but that's insufficient — `type="email"` is needed for native browser email validation and correct mobile keyboard.  
**Fix**: Changed to `->input('email', [...])` which generates `type="email"`.

### BUG-04: Payment terms page — hardcoded test phone
**File**: `frontend/views/pages/payment-terms.php:561`  
**Severity**: Medium (wrong contact phone shown in static page)  
**Problem**: Support call-to-action in the payment terms page had hardcoded `+375291234567` (placeholder).  
**Fix**: Phone now reads `$company['phone']` via settings service.

---

## Non-Bugs Verified

| Item | Status |
|------|--------|
| Order tracking URLs `/order/<token>` | ✓ Work correctly with real MySQL tokens |
| Cart add-to-cart POST `/cart/add` | ✓ Returns 200, drawer shows items |
| Checkout shows cart summary | ✓ 8 items, correct total |
| `/account/*` guest redirects | ✓ All redirect to login (302) |
| All static pages (privacy, offer, delivery, return) | ✓ HTTP 200, no PHP errors |
| Instagram links on homepage | ✓ Correct handle `@sneakerhead_belarus` (intentional hardcode in landing) |
| Footer social links | ✓ Dynamic from settings — correctly hidden when no social URLs configured |
| 404 page | ✓ Correct HTTP 404 |

---

## Admin Panel Findings (separate agent)

Critical bugs found by parallel admin QA agent — see `docs/QA_ADMIN_ROUND_1.md`:

1. `/admin/poizon/errors` — PHP Fatal: `ImportLog` class not found
2. `/admin/product/export` — Memory exhaustion (loads all products as AR objects)
3. `/admin/finance/create-payment` — PHP Warning→500 on undefined array key
4. `cart.items_count` DB column missing → cart abandonment stats broken
5. NBRB Currency API — wrong request format (`parammode=2` in body, not query param)
6. `/admin/procurement/receiving` — 15 MB HTML, 3022 table elements, unusable at scale

---

## Round 2 Focus Areas

1. E2E: guest purchase flow (add → checkout → submit → order success page)
2. E2E: 1-click quick order modal
3. E2E: register → login → profile edit
4. Wishlist: add while guest → login → verify migration
5. Catalog infinite scroll: verify page 2+ loads
6. Catalog search highlight: verify `?q=` result terms are highlighted
7. Mobile layout at 360px (header, product cards, checkout form)
8. Order upload payment page `/order/<token>/upload` (GET shows form, POST uploads)
