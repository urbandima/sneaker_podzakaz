# Admin Panel UI Audit — Exhaustive Click-Through Report

**Generated:** 2026-04-23 04:40
**Duration:** 82.6s | **Pages:** 44 | **Total findings:** 59

## Summary

| Severity | Count |
|---|---|
| 🔴 critical | 1 |
| 🟠 high | 0 |
| 🟡 medium | 10 |
| 🔵 low | 48 |

## Admin Panel Route Map

| URL | Label | Elements | Bugs |
|---|---|---|---|
| /admin | Dashboard | btn:23 lnk:66 form:0 inp:14 modal:0 tbl:0 | 🟡 1 |
| /admin/product | Products list | btn:29 lnk:164 form:1 inp:29 modal:0 tbl:1 | 🟡 1 |
| /admin/product/create | Product create | btn:24 lnk:58 form:1 inp:29 modal:0 tbl:0 | 🔵 2 |
| /admin/catalog | Catalog | btn:29 lnk:164 form:1 inp:29 modal:0 tbl:1 | 🔵 1 |
| /admin/characteristic | Characteristics | btn:22 lnk:61 form:0 inp:14 modal:0 tbl:1 | 🔵 1 |
| /admin/characteristic/create | Characteristic create | btn:0 lnk:0 form:0 inp:0 modal:0 tbl:0 | 🔴 1 |
| /admin/product-tag | Product tags | btn:22 lnk:60 form:0 inp:14 modal:0 tbl:0 | 🔵 2 |
| /admin/order | Orders list | btn:35 lnk:131 form:1 inp:64 modal:0 tbl:1 | 🟡 1 |
| /admin/order/create | Order create | btn:26 lnk:57 form:1 inp:58 modal:0 tbl:1 | 🔵 1 |
| /admin/customer | Customers list | btn:26 lnk:112 form:1 inp:22 modal:0 tbl:1 | 🔵 1 |
| /admin/shipping | Shipping orders | btn:22 lnk:66 form:0 inp:14 modal:0 tbl:1 | 🔵 1 |
| /admin/shipping/dispatch | Shipping dispatch | btn:22 lnk:61 form:0 inp:15 modal:0 tbl:0 | 🔵 1 |
| /admin/return | Returns | btn:23 lnk:63 form:1 inp:15 modal:0 tbl:1 | 🔵 1 |
| /admin/coupon | Coupons list | btn:23 lnk:72 form:1 inp:17 modal:0 tbl:1 | 🔵 1 |
| /admin/coupon/create | Coupon create | btn:24 lnk:59 form:1 inp:29 modal:0 tbl:0 | 🔵 1 |
| /admin/coupon/statistics | Coupon statistics | btn:22 lnk:57 form:0 inp:14 modal:0 tbl:2 | 🔵 1 |
| /admin/tariff | Tariffs | btn:23 lnk:67 form:0 inp:18 modal:0 tbl:0 | 🔵 1 |
| /admin/analytics | Analytics | btn:23 lnk:68 form:0 inp:14 modal:0 tbl:2 | 🔵 1 |
| /admin/analytics/rfm | Analytics RFM | btn:9129 lnk:57 form:0 inp:14 modal:0 tbl:1 | 🔵 1 |
| /admin/marketing | Marketing | btn:23 lnk:61 form:0 inp:14 modal:0 tbl:0 | 🔵 1 |
| /admin/poizon | Poizon import | btn:22 lnk:66 form:0 inp:14 modal:0 tbl:1 | 🔵 2 |
| /admin/poizon/run | Poizon run | btn:25 lnk:60 form:3 inp:17 modal:0 tbl:0 | 🔵 2 |
| /admin/import | Import dashboard | btn:23 lnk:75 form:0 inp:14 modal:0 tbl:3 | 🔵 1 |
| /admin/import/upload | Import upload | btn:25 lnk:59 form:1 inp:17 modal:0 tbl:0 | 🔵 1 |
| /admin/plugin | Plugins | btn:24 lnk:66 form:0 inp:14 modal:0 tbl:0 | 🔵 1 |
| /admin/plugin/amocrm | Plugin AmoCRM | btn:29 lnk:58 form:0 inp:21 modal:0 tbl:0 | 🔵 1 |
| /admin/plugin/lamoda | Plugin Lamoda | btn:25 lnk:58 form:0 inp:17 modal:0 tbl:0 | 🔵 1 |
| /admin/plugin/moysklad | Plugin MoySklad | btn:49 lnk:58 form:0 inp:118 modal:0 tbl:3 | 🔵 1 |
| /admin/plugin/telegram | Plugin Telegram | btn:25 lnk:59 form:0 inp:21 modal:0 tbl:0 | 🔵 1 |
| /admin/settings | Settings | btn:26 lnk:58 form:0 inp:30 modal:0 tbl:2 | 🟡 1 |
| /admin/settings/shipping | Settings shipping | btn:32 lnk:57 form:0 inp:54 modal:0 tbl:0 | 🟡 2 |
| /admin/settings/payment | Settings payment | btn:26 lnk:58 form:0 inp:26 modal:0 tbl:0 | 🟡 2 |
| /admin/settings/statuses | Settings statuses | btn:31 lnk:57 form:0 inp:64 modal:0 tbl:1 | 🟡 2 |
| /admin/user | Users list | btn:38 lnk:60 form:0 inp:17 modal:0 tbl:1 | 🟡 2 |
| /admin/user/create | User create | btn:23 lnk:59 form:1 inp:18 modal:0 tbl:0 | 🔵 2 |
| /admin/activity-log | Activity log | btn:23 lnk:58 form:1 inp:18 modal:0 tbl:0 | 🔵 1 |
| /admin/review | Reviews | btn:22 lnk:67 form:0 inp:14 modal:0 tbl:0 | 🔵 1 |
| /admin/finance/payments | Finance payments | btn:30 lnk:75 form:1 inp:30 modal:1 tbl:1 | 🔵 1 |
| /admin/finance/expenses | Finance expenses | btn:30 lnk:58 form:1 inp:30 modal:1 tbl:1 | 🔵 1 |
| /admin/finance/pnl | Finance P&L | btn:22 lnk:57 form:1 inp:15 modal:0 tbl:1 | 🔵 1 |
| /admin/finance/margin | Finance margin | btn:23 lnk:60 form:1 inp:16 modal:0 tbl:1 | 🔵 1 |
| /admin/procurement | Procurement | btn:226 lnk:459 form:1 inp:22 modal:0 tbl:1 | 🔵 1 |
| /admin/dev-tools | Dev tools | btn:27 lnk:59 form:0 inp:14 modal:0 tbl:0 | 🔵 1 |
| /admin/moysklad | MoySklad | btn:49 lnk:58 form:0 inp:118 modal:0 tbl:3 | 🔵 1 |

## Findings

### STYLE (50 issues)

| ID | Severity | Page | Element | Expected | Actual |
|---|---|---|---|---|---|
| ADM-001 | low | /admin | Inline styles | <30 inline | 71 elements with inline style |
| ADM-002 | low | /admin/product | Inline styles | <30 inline | 793 elements with inline style |
| ADM-003 | low | /admin/product/create | Images | All load | 1 broken: create |
| ADM-004 | low | /admin/product/create | Inline styles | <30 inline | 85 elements with inline style |
| ADM-005 | low | /admin/catalog | Inline styles | <30 inline | 793 elements with inline style |
| ADM-006 | low | /admin/characteristic | Inline styles | <30 inline | 72 elements with inline style |
| ADM-009 | low | /admin/product-tag | Inline styles | <30 inline | 64 elements with inline style |
| ADM-010 | low | /admin/order | Inline styles | <30 inline | 614 elements with inline style |
| ADM-011 | low | /admin/order/create | Inline styles | <30 inline | 83 elements with inline style |
| ADM-012 | low | /admin/customer | Inline styles | <30 inline | 740 elements with inline style |
| ADM-013 | low | /admin/shipping | Inline styles | <30 inline | 66 elements with inline style |
| ADM-014 | low | /admin/shipping/dispatch | Inline styles | <30 inline | 75 elements with inline style |
| ADM-015 | low | /admin/return | Inline styles | <30 inline | 74 elements with inline style |
| ADM-016 | low | /admin/coupon | Inline styles | <30 inline | 86 elements with inline style |
| ADM-017 | low | /admin/coupon/create | Inline styles | <30 inline | 93 elements with inline style |
| ADM-018 | low | /admin/coupon/statistics | Inline styles | <30 inline | 68 elements with inline style |
| ADM-019 | low | /admin/tariff | Inline styles | <30 inline | 67 elements with inline style |
| ADM-020 | low | /admin/analytics | Inline styles | <30 inline | 131 elements with inline style |
| ADM-021 | low | /admin/analytics/rfm | Inline styles | <30 inline | 42653 elements with inline style |
| ADM-022 | low | /admin/marketing | Inline styles | <30 inline | 71 elements with inline style |
| ADM-024 | low | /admin/poizon | Inline styles | <30 inline | 64 elements with inline style |
| ADM-026 | low | /admin/poizon/run | Inline styles | <30 inline | 64 elements with inline style |
| ADM-027 | low | /admin/import | Inline styles | <30 inline | 93 elements with inline style |
| ADM-028 | low | /admin/import/upload | Inline styles | <30 inline | 79 elements with inline style |
| ADM-029 | low | /admin/plugin | Inline styles | <30 inline | 79 elements with inline style |
| ADM-030 | low | /admin/plugin/amocrm | Inline styles | <30 inline | 91 elements with inline style |
| ADM-031 | low | /admin/plugin/lamoda | Inline styles | <30 inline | 84 elements with inline style |
| ADM-032 | low | /admin/plugin/moysklad | Inline styles | <30 inline | 242 elements with inline style |
| ADM-033 | low | /admin/plugin/telegram | Inline styles | <30 inline | 88 elements with inline style |
| ADM-034 | low | /admin/settings | Inline styles | <30 inline | 89 elements with inline style |
| ADM-036 | low | /admin/settings/shipping | Inline styles | <30 inline | 95 elements with inline style |
| ADM-038 | low | /admin/settings/payment | Inline styles | <30 inline | 76 elements with inline style |
| ADM-040 | low | /admin/settings/statuses | Inline styles | <30 inline | 150 elements with inline style |
| ADM-042 | low | /admin/user | Inline styles | <30 inline | 78 elements with inline style |
| ADM-044 | low | /admin/user/create | Inline styles | <30 inline | 64 elements with inline style |
| ADM-045 | low | /admin/activity-log | Inline styles | <30 inline | 75 elements with inline style |
| ADM-046 | low | /admin/review | Inline styles | <30 inline | 68 elements with inline style |
| ADM-047 | low | /admin/finance/payments | Inline styles | <30 inline | 1902 elements with inline style |
| ADM-048 | low | /admin/finance/expenses | Inline styles | <30 inline | 105 elements with inline style |
| ADM-049 | low | /admin/finance/pnl | Inline styles | <30 inline | 260 elements with inline style |
| ADM-050 | low | /admin/finance/margin | Inline styles | <30 inline | 625 elements with inline style |
| ADM-051 | low | /admin/procurement | Inline styles | <30 inline | 3685 elements with inline style |
| ADM-052 | low | /admin/dev-tools | Inline styles | <30 inline | 64 elements with inline style |
| ADM-053 | low | /admin/moysklad | Inline styles | <30 inline | 242 elements with inline style |
| ADM-054 | medium | /admin/order | @1440px | No overflow | Horizontal scroll |
| ADM-055 | medium | /admin/order | @1280px | No overflow | Horizontal scroll |
| ADM-056 | medium | /admin | @1024px | No overflow | Horizontal scroll |
| ADM-057 | medium | /admin/product | @1024px | No overflow | Horizontal scroll |
| ADM-058 | medium | /admin/order | @1024px | No overflow | Horizontal scroll |
| ADM-059 | medium | /admin/settings | @1024px | No overflow | Horizontal scroll |

### FUNCTIONAL (1 issues)

| ID | Severity | Page | Element | Expected | Actual |
|---|---|---|---|---|---|
| ADM-007 | critical | /admin/characteristic/create | Page load | 2xx | HTTP 500 |

### CONSISTENCY (4 issues)

| ID | Severity | Page | Element | Expected | Actual |
|---|---|---|---|---|---|
| ADM-008 | low | /admin/product-tag | Button classes | Single design system | Mixed: 3 Bootstrap + 1 admin-btn |
| ADM-023 | low | /admin/poizon | Button classes | Single design system | Mixed: 3 Bootstrap + 1 admin-btn |
| ADM-025 | low | /admin/poizon/run | Button classes | Single design system | Mixed: 3 Bootstrap + 1 admin-btn |
| ADM-043 | low | /admin/user/create | Button classes | Single design system | Mixed: 1 Bootstrap + 1 admin-btn |

### UX (4 issues)

| ID | Severity | Page | Element | Expected | Actual |
|---|---|---|---|---|---|
| ADM-035 | medium | /admin/settings/shipping | Delete "Удалить" | JS confirm | No data-confirm/onclick confirm |
| ADM-037 | medium | /admin/settings/payment | Delete "Удалить" | JS confirm | No data-confirm/onclick confirm |
| ADM-039 | medium | /admin/settings/statuses | Delete "Удалить" | JS confirm | No data-confirm/onclick confirm |
| ADM-041 | medium | /admin/user | Delete "Удалить" | JS confirm | No data-confirm/onclick confirm |

