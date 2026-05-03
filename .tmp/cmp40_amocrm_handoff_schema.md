# AmoCRM Handoff Schema — DMSales contract (spec v1, pre-integration)

> **Scope.** Contract spec для DMSales' `amocrm_handoff` / `amocrm_disqualify`
> комментариев. Ниже — авторитетные имена полей, enum-ы и валидационные правила.
> Численные `field_id` / `status_id` AmoCRM **не подтверждены** и блокируют
> first-real-handoff — см. §6. Связан с [`playbook` v2](/CMP/issues/CMP-9#document-playbook) §5
> и feasibility-вердиктом в [CMP-10](/CMP/issues/CMP-10).

## 0. Что в этом документе авторитетно (и что нет)

| Раздел | Статус | Можно ли действовать сегодня? |
|---|---|---|
| §1 Поля + enum-ы | ✅ авторитетно (контракт) | да — DMSales может писать `amocrm_handoff` блоки уже сейчас |
| §2 `status_id` enum | ⚠️ имена + значение enum-кодов авторитетны; **численные `status_id` — placeholder** до §6.1 | да, но `target_status_id` будет `TBC_HANDED_OFF` до анблока |
| §3 Attribution | ✅ авторитетно | да |
| §4 Validation | ✅ авторитетно | да |
| §5 Шаблон комментария | ✅ авторитетно | да |
| §6 Открытые вопросы / блокеры | — | требует CMO/менеджера AmoCRM |

Существующая интеграция (`backend/shared/components/AmocrmClient.php`,
`backend/modules/admin/services/AmocrmStatusMapper.php`) знает только
**post-handoff** статусы (`new`, `paid`, `bought_at_source`, …). DM/qualification-pipeline
(IG/FB) **не зарегистрирован** в `amocrm_status_mapping`. Это и есть причина блокера §6.1.

---

## 1. Lead-level fields (custom + native)

**Конвенции.**
- `field_name` ниже — *каноническое контрактное имя*, которое DMSales использует
  в YAML. AmoCRM-side machine code custom field-а должен совпадать (CMO/менеджер
  при создании поля в UI указывает Code = ровно такое имя без префикса `lead.`).
- AmoCRM `select`-поля в API оперируют численными option-id; перечисленные
  ниже строковые `enum value`-коды — стабильные контрактные коды, которые
  будут смаплены 1:1 на option-id при регистрации (см. §6.2).
- Все строки UTF-8, trim-аются по краям. Пустая строка ≠ `null`; для опциональных
  полей `null`/отсутствие ключа — норма, пустая строка — ошибка.

| field_name | type | required at handoff | enum / format | source | example |
|---|---|---|---|---|---|
| `lead.product_ref` | text | **Y** | URL ИЛИ внутренний SKU вида `XYZ-01` | Q1 | `https://nike.com/.../air-jordan-1` |
| `lead.size` | text | **Y** | префикс из `EU `, `US `, `UK `, `RU ` или суффикс ` cm`; нормализованный формат: `EU 42`, `US 9`, `UK 8`, `27 cm` | Q2 | `EU 42` |
| `lead.deadline` | text | **Y** | ISO `YYYY-MM-DD` ЕСЛИ дата известна; иначе короткий free-text (`к НГ`, `когда будет`, `urgent — gift 20 мая`); **пустая строка запрещена** | Q3 | `2026-05-20` |
| `lead.deadline_strictness` | select | N | `strict_date` / `target_window` / `flexible` (derived от тон-формулировки в Q3) | Q3 | `strict_date` |
| `lead.prepay_ready` | select | **Y** | `yes` / `no` / `need_to_think` | Q4 | `yes` |
| `lead.passport_by` | select | **Y** | `yes` / `no` / `other_country` | Q5 | `yes` |
| `lead.attribution_source_self_reported` | text | N | free-text, max 255 chars; controlled vocab НЕ вводим v1 (учимся long-tail) | bonus §3 | `Reels @sneaker_head_by` |
| `lead.attribution_source_referral` | text | N (**имя зарезервировано**) | формат `<connector>:<kind>:<id>`, e.g. `ig:reel:8723xxx`, `fb:post:9921xxx`; v1 DMSales **не** заполняет вручную — ждёт IG-коннектор + AmoCRM «Расширенный» | future / playbook §3 | `ig:reel:8723xxx` |
| `lead.disqualified_reason` | select | conditional (req если `dm.disqualified`) | `no_prepay` / `no_by_passport` / `no_response` / `no_timing` / `price_objection` / `other` | Template 6 trigger | `no_by_passport` |
| `lead.disqualified_note` | text | N | free-text 0–255, для нюансов («ждёт скидки 20%», «РФ-доставка просит») | Template 6 nuance | `ждёт скидки 20%` |

**Решения по открытым вопросам в твоём запросе:**

- **`deadline` — `text`, не `date`.** Причина: gap §1 в [CMP-25](/CMP/issues/CMP-25#comment-060b8b6b-573c-492e-b880-130feafcdfac)
  (urgent gift / «когда будет в наличии») — DMSales должен уметь зафиксировать
  intent даже без календарного значения. Добавлено sibling-поле
  `deadline_strictness` (`strict_date` / `target_window` / `flexible`), чтобы
  логистика могла отделять «должно быть к 20 мая» от «до конца лета». Когда
  `deadline_strictness == strict_date`, parser **обязан** найти ISO-дату
  в `deadline` (см. §4 `high_confidence` правило).
- **`no_timing` добавлен** в `disqualified_reason` под gap §1. Заодно добавил
  `price_objection` как отдельный код (был бы прятан в `other` иначе — теряем
  сигнал для CMO о ценовой эластичности).

---

## 2. Stage / `status_id` enum

> Кодовая база на сегодня: `amocrm_status_mapping` table содержит только
> post-handoff статусы (Order: `new`, `paid`, `confirmed_and_paid`, `ordered`,
> `bought_at_source`, `in_transit`, `at_warehouse`, `shipped`, `delivered`,
> `cancelled`, `refunded`). DM/IG/FB-pipeline (где живёт `dm.handed_off`)
> **не замаплен** — добавим строки `(track='dm', our_status='handed_off' | 'disqualified')`
> сразу после анблока §6.1.

| event | `status_id` | название в AmoCRM | trigger | manual/auto |
|---|---|---|---|---|
| `dm.started` | **TBC** — стартовый статус IG/FB pipeline (auto-created коннектором) | вероятно «Первичный контакт» / «Новое обращение» (точное имя задаст менеджер) | `lead.add` от IG/FB-коннектора | auto |
| `dm.qualified` | **derived, без отдельного status_id** | — | Q5 закрыт положительно: lead остаётся в `dm.started` стадии | manual marker only (только в DMSales-комментарии) |
| `dm.handed_off` | **TBC «Передано в выкуп»** — *блокер*, см. §6.1 | «Передано в выкуп» | DMSales отправил Template 5 → manual transition в AmoCRM UI силами менеджера, либо через events API после §6.2 | manual v1, semi-auto v2 |
| `dm.disqualified` | `143` — AmoCRM **системный** status «Закрыто и не реализовано» (одинаков для всех pipeline-ов AmoCRM) | «Закрыто и не реализовано» | DMSales отправил Template 6 → manual transition + `disqualified_reason` filled | manual |

**Заметка по `143`:** это закреплённый системой AmoCRM ID, можно использовать
без дополнительного подтверждения. Для всех остальных строк — численные ID
будут вписаны после §6.1.

---

## 3. Attribution / source tags

- `source_id` — **controlled enum**, авторитетный сегодня:
  - `instagram` — lead создан AmoCRM IG-коннектором.
  - `facebook` — lead создан AmoCRM FB-коннектором (Messenger / Lead Ads).
  - `direct_link` — клиент пришёл по deep-link (utm от рассылки / витрины).
  - `manual_input` — менеджер создал lead вручную (кросс-канальная заявка,
    телефонный звонок, etc.).
  - **зарезервировано на будущее:** `tiktok`, `whatsapp` — добавляем коды,
    как только подключим соответствующий коннектор.
- `lead.attribution_source_self_reported` — free-text, max 255. **Без**
  controlled vocab v1: цель — собрать длинный хвост («Reels @brand», «друг
  посоветовал», «гугл», «реклама в LinkedIn»), потом по логам подобрать
  enum для v2.
- `lead.attribution_source_referral` — имя поля **зарезервировано прямо сейчас**,
  отдельная колонка от `_self_reported`. Формат: `<connector>:<kind>:<id>`,
  пример `ig:reel:8723xxx`, `fb:post:9921xxx`. v1 DMSales **не заполняет**
  это поле руками — оно набивается коннектором, как только AmoCRM-тариф
  «Расширенный» откроет `messaging_referrals`. До этого момента поле
  существует, но всегда `null`.

---

## 4. Validation rules

**handoff-ready (минимум для перевода в «Передано в выкуп»):**

```text
handoff_ready := (
  lead.product_ref      != ""             AND
  lead.size             != ""             AND
  lead.deadline         != ""             AND
  lead.prepay_ready     == "yes"          AND
  lead.passport_by      == "yes"          AND
  target_status_id      == handed_off_status_id   /* §2, §6.1 */
)
```

**Confidence-уровни в DMSales-комментарии:**

```text
high_confidence := handoff_ready AND (
     lead.deadline matches /^\d{4}-\d{2}-\d{2}$/                       /* ISO date */
  OR lead.deadline_strictness IN { "target_window", "flexible" }       /* free-text приемлем */
)

medium_confidence := handoff_ready AND NOT high_confidence
                     /* частный кейс: deadline = free-text но strictness = strict_date
                      * → flag для логистики на verify */

low_confidence    := NOT handoff_ready
                     /* НЕ handoff — это очередной DM round */
```

В `confidence:` поле комментария допустимы только `high` или `medium`.
`low` ⇒ **не пиши `amocrm_handoff` блок вообще**, продолжай DM-цикл.

**disqualify-ready (минимум для перевода в «Закрыто и не реализовано»):**

```text
disqualify_ready := (
  target_status_id          == 143                                          AND
  lead.disqualified_reason  IN { "no_prepay", "no_by_passport", "no_response",
                                 "no_timing", "price_objection", "other" }
)
```

`disqualified_note` опционален, но рекомендуется всегда, особенно при `other`.

---

## 5. Шаблоны DMSales-комментариев

### 5.1 Handoff (success path)

```yaml
amocrm_handoff:
  source_id: instagram               # см. §3 enum
  lead:
    product_ref: "https://nike.com/.../air-jordan-1"
    size: "EU 42"
    deadline: "2026-05-20"           # ISO если известно; free-text иначе
    deadline_strictness: strict_date # strict_date | target_window | flexible
    prepay_ready: yes                # yes | no | need_to_think
    passport_by: yes                 # yes | no | other_country
    attribution_source_self_reported: "Reels @sneaker_head_by"
    # attribution_source_referral заполняется только коннектором, не DMSales
  target_status_id: TBC_HANDED_OFF   # placeholder до анблока §6.1
  confidence: high                   # high | medium (low → не handoff)
```

### 5.2 Disqualify

```yaml
amocrm_disqualify:
  source_id: instagram
  lead:
    disqualified_reason: no_by_passport
    disqualified_note: "клиент из РФ, паспорт РБ нет"
  target_status_id: 143              # AmoCRM системный «Закрыто и не реализовано»
```

### 5.3 Что DMSales *не* делает в комментарии

- Не вызывает AmoCRM API напрямую — это спека, не интеграция (см. «Не нужно» в issue description).
- Не выдумывает `target_status_id` для handoff — пишет `TBC_HANDED_OFF`, пока §6.1 открыт.
- Не пишет `attribution_source_referral` руками — это server-side поле.
- Не использует PII / реальных lead-данных в issue-комментариях (синтетика only).

---

## 6. Открытые вопросы / блокеры (и кто анблокирует)

| # | item | блокер для … | unblock owner | unblock action |
|---|---|---|---|---|
| **6.1** | численный `status_id` для «Передано в выкуп» в IG/FB-pipeline | первого реального handoff | CMO (account owner) **или** менеджер с AmoCRM admin-доступом | Один из путей: **(a)** проверить/создать статус «Передано в выкуп» в IG/FB pipeline через AmoCRM UI и запостить ID обратно в parent-issue (CMP-?? — см. ниже); **(b)** прокинуть `AMOCRM_LONG_TOKEN` env CTO, чтобы я запустил `php yii amocrm/list-pipelines` (CLI добавлю по этому маршруту). |
| **6.2** | численные `field_id` для каждого custom field из §1 | первого реального handoff (без них events API не примет update) | CMO **или** менеджер | Создать в AmoCRM UI custom-fields с *machine code* = именам из §1 (без префикса `lead.`), запостить map (name → field_id) в child-issue из §6.1; CTO зарегистрирует map в новой таблице `amocrm_custom_field_map` (миграция — отдельный тикет). |
| **6.3** | AmoCRM «Расширенный» тариф → `messaging_referrals` для `attribution_source_referral` | roadmap-фича, **не** v1 launch | CEO (budget) → CMO (configure) | не блокирует v1; имя поля уже зарезервировано (§1, §3). |
| **6.4** | Должен ли `dm.started` быть отдельным statusом или просто маркером создания lead? | — | — (решено) | `dm.started` = lead-creation marker, отдельный status_id не нужен. Lead остаётся в стартовом статусе IG/FB-pipeline до handoff/disqualify. |

---

## 7. Что дальше

- **Сейчас, в этом тикете:** DMSales принимает спеку как контракт v1 и закрывает CMP-40.
- **Следом, отдельный child-issue для CMO** ([CTO→CMO] AmoCRM IG/FB pipeline +
  custom-field IDs для DM handoff) — анблок §6.1 + §6.2.
- **После §6.1+§6.2 закрыты:** CTO обновляет §2 этого документа реальными
  численными `status_id` и регистрирует field-map в БД; DMSales перестаёт
  писать `TBC_HANDED_OFF` и подставляет реальный ID.
- **Roadmap (после launch):** §6.3 — `attribution_source_referral` оживает,
  как только подключим «Расширенный» AmoCRM-тариф.
