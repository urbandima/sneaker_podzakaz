# Yii Migrations — Authoring Conventions

Run with `php yii migrate` from the repo root. Connection is read from `infrastructure/config/db.php` (env-driven, defaults to MySQL `sneakerhead`).

## Idempotency is mandatory

**Every new migration MUST be idempotent.** Re-running a migration against a DB where its effect (or part of its effect) is already present must succeed silently — never throw "Duplicate column", "Table already exists", or "Duplicate key name".

Why: production has been hand-patched in the past. When the `migration` table drifts from actual schema state (CMP-58), a non-idempotent migration becomes a hard blocker for every later migration in the chain — including unrelated P0 fixes.

### Required guards

Use Yii's schema introspection (always pass `refresh = true` so a stale schema cache from a prior step in the same run cannot lie):

```php
// Column add
$schema = $this->db->getTableSchema('{{%order}}', true);
if ($schema === null) {
    echo "    > skip: {{%order}} table does not exist yet\n";
    return;
}
if (!in_array('my_col', $schema->columnNames, true)) {
    $this->addColumn('{{%order}}', 'my_col', $this->string(64)->null());
}

// Table create
if ($this->db->getTableSchema('{{%my_table}}', true) === null) {
    $this->createTable('{{%my_table}}', [...]);
}

// Index create — there is no schema helper, query directly
if (!$this->indexExists('order', 'idx_order_my_col')) {
    $this->createIndex('idx_order_my_col', 'order', 'my_col');
}

private function indexExists(string $table, string $indexName): bool
{
    $row = $this->db->createCommand(
        "SHOW INDEX FROM `{$table}` WHERE Key_name = :name",
        [':name' => $indexName]
    )->queryOne();
    return $row !== false;
}
```

### Rules

- **Column adds** — guard with `in_array($col, $schema->columnNames, true)`.
- **Column drops** in `safeDown()` — also guard; never assume the column is present.
- **Table creates** — guard with `getTableSchema(..., true) === null`.
- **Index creates / drops** — guard with `SHOW INDEX FROM ... WHERE Key_name = :name`.
- **Backfill UPDATEs** — add a `WHERE` clause that excludes already-backfilled rows (e.g. `WHERE new_col IS NULL`) so re-runs are no-ops.
- **`{{%table}}` placeholder** — use it for table names so `tablePrefix` is honoured.
- **`safeUp` / `safeDown`** — prefer over `up` / `down`; they wrap the migration in a transaction on engines that support DDL transactions.

### When the schema is already correct but the migration row is missing

If you discover (e.g. via a duplicate-column failure) that a migration's effect is fully present but the `migration` table doesn't list it:

```bash
php yii migrate/mark m260427_xxxxxx_my_migration
```

This records it as applied without re-running. Use this only to reconcile drift — new migrations should always be runnable from a clean DB.

## History

- **CMP-58 (2026-05-03)** — reconciled `migration` table against the live `sneakerhead` DB, made the 10 pending `m260426_*` / `m260427_*` migrations idempotent, established this convention. Trigger: a duplicate-column failure on `m260426_180000_passport_citizenship_fields` blocked the CMP-47 P0 fix from being deployed via `php yii migrate`.
