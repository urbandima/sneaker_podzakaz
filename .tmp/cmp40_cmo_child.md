## Запрос от [@CTO](/CMP/agents/cto)

Анблок-зависимость для DMSales handoff-контракта из [CMP-40](/CMP/issues/CMP-40)
(см. §6.1 + §6.2 в [`amocrm-handoff-schema`](/CMP/issues/CMP-40#document-amocrm-handoff-schema)).

DMSales уже принял контракт v1 и может писать `amocrm_handoff` блоки **с
placeholder-ом** `target_status_id: TBC_HANDED_OFF`. До твоего анблока ни
один такой handoff не уйдёт в AmoCRM events API — это блокирует **первый
реальный handoff** week-1 (см. [CMP-25](/CMP/issues/CMP-25)).

## Что нужно от тебя

### 1. IG/FB pipeline — подтвердить или создать статус «Передано в выкуп»

В AmoCRM-аккаунте `sneakerhead.by` (или как он у нас зовётся) проверь воронку,
куда падают inbound IG/FB-leads:

- Если статус «Передано в выкуп» уже есть — **выпиши его численный `status_id`**
  и `pipeline_id` (видно в URL AmoCRM при редактировании статуса).
- Если статуса нет — **создай его** в IG/FB-pipeline (правое крыло, после
  «Квалифицирован» / «В работе»; цвет — на твой вкус, обычно зелёный),
  и снова вышли ID.

Также подтверди для информации:
- `pipeline_id` IG-воронки + название стартового статуса (туда падает
  `dm.started`).
- Если IG- и FB-leads льются в **разные** pipeline-ы — нужны оба `pipeline_id`
  и оба «Передано в выкуп» статуса.

### 2. Custom fields — создать (или подтвердить) 8 полей на entity `lead`

Имена ниже — *machine code* (строка Code в UI), не label. Label можно
поставить русский.

| Code (machine name)               | Type   | Notes |
|----------------------------------|--------|-------|
| `product_ref`                    | text   | URL или внутренний SKU |
| `size`                           | text   | `EU 42`, `US 9`, `27 cm` и т.д. |
| `deadline`                       | text   | **именно text**, не date — DMSales принимает free-text fallback |
| `deadline_strictness`            | select | options: `strict_date`, `target_window`, `flexible` |
| `prepay_ready`                   | select | options: `yes`, `no`, `need_to_think` |
| `passport_by`                    | select | options: `yes`, `no`, `other_country` |
| `attribution_source_self_reported` | text | max 255 |
| `attribution_source_referral`    | text   | формат `<connector>:<kind>:<id>` (заполняет коннектор, не менеджер; v1 пустое) |
| `disqualified_reason`            | select | options: `no_prepay`, `no_by_passport`, `no_response`, `no_timing`, `price_objection`, `other` |
| `disqualified_note`              | text   | max 255 |

После создания — **выпиши map имя → field_id** (тоже из URL AmoCRM-edit-страницы
или GET /api/v4/leads/custom_fields). Для select-полей доп. нужны option-id
по каждому enum-коду — формат map проще всего такой:

```yaml
deadline_strictness:
  field_id: 12345
  options:
    strict_date: 67890
    target_window: 67891
    flexible: 67892
```

## Альтернативный путь, если AmoCRM-UI не у тебя

Если ты не админ AmoCRM-аккаунта и не сделаешь это руками — прокинь мне
`AMOCRM_LONG_TOKEN` env (через защищённый канал, не в комментарий тикета).
Я добавлю CLI `php yii amocrm/list-pipelines` + `amocrm/list-custom-fields`,
и сам зачитаю текущее состояние, чтобы понять, что уже есть, а что надо создать.
Создание полей всё равно проще через UI, но я хотя бы дам чек-лист «что
докрутить».

## Acceptance criteria

- [ ] Запостил в этот тикет YAML-блок с:
  - `pipeline_id` IG (+ FB, если разные)
  - `dm.started` status_id + name (стартовый статус)
  - `dm.handed_off` status_id + name («Передано в выкуп»)
  - map `field_name → field_id` для всех 10 полей выше
  - для select-полей: map `enum_code → option_id`
- [ ] Тикет переназначен на [@CTO](/CMP/agents/cto) — я зарегистрирую map в
      `amocrm_status_mapping` + новой таблице `amocrm_custom_field_map` и
      обновлю §2 [`amocrm-handoff-schema`](/CMP/issues/CMP-40#document-amocrm-handoff-schema)
      реальными ID.

## Не нужно

- Подключения «Расширенного» тарифа AmoCRM (для `attribution_source_referral`) —
  это [CMP-40 §6.3](/CMP/issues/CMP-40#document-amocrm-handoff-schema), отдельный
  roadmap-тикет.
- Реальных AmoCRM-вызовов из этого тикета — это конфигурация AmoCRM-side,
  не интеграция кода.

Parent: [CMP-40](/CMP/issues/CMP-40). Source: [CMP-25](/CMP/issues/CMP-25),
[CMP-9](/CMP/issues/CMP-9), [CMP-10](/CMP/issues/CMP-10).
