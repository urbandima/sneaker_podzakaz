<?php

use yii\db\Migration;

/**
 * CMP-470: `AutomationController::save()` присваивал `AutomationTrigger::$conditions`/
 * `$actions` уже готовой `json_encode()`-строкой, а обе колонки в `automation_trigger`
 * объявлены нативным MySQL типом JSON — `yii\db\mysql\ColumnSchema::dbTypecast()`
 * сама оборачивает любое присваиваемое значение в `JsonExpression` и кодирует его
 * ещё раз при записи. Итог: каждая строка, когда-либо сохранённая через админку,
 * получала ДВОЙНОЕ кодирование — `JSON_TYPE(conditions)`/`JSON_TYPE(actions)` были
 * `'STRING'` вместо `'ARRAY'` (значение — JSON-строка, содержащая экранированный
 * JSON, а не сам массив). Живым прогоном подтверждено на всех 6 существующих
 * триггерах + тестовой записи. Приложение не падало только потому, что
 * `AutomationTrigger::getConditionsArray()`/`getActionsArray()` и
 * `AutomationEngine::checkConditions()`/`executeActions()` на всякий случай сами
 * вызывают `json_decode()` при чтении — это компенсировало один уровень двойного
 * кодирования, но данные в БД оставались структурно неверными (не проходят
 * `JSON_EXTRACT`/`JSON_CONTAINS`, не индексируются как массив).
 *
 * Код-фикс — в AutomationController::save() (больше не пред-кодирует) и
 * AutomationTrigger::rules() (conditions/actions больше не 'string'). Эта
 * миграция разово нормализует уже существующие в БД строки.
 */
class m260925_081500_fix_double_encoded_automation_trigger_json extends Migration
{
    public function safeUp()
    {
        $this->execute(
            "UPDATE {{%automation_trigger}} SET conditions = CAST(JSON_UNQUOTE(conditions) AS JSON) " .
            "WHERE JSON_TYPE(conditions) = 'STRING'"
        );
        $this->execute(
            "UPDATE {{%automation_trigger}} SET actions = CAST(JSON_UNQUOTE(actions) AS JSON) " .
            "WHERE JSON_TYPE(actions) = 'STRING'"
        );
    }

    public function safeDown()
    {
        // Не откатываем: восстановление намеренного двойного кодирования
        // не имеет практической ценности (это было чистым багом, а не
        // осознанным форматом хранения).
        echo "m260925_081500_fix_double_encoded_automation_trigger_json: данные не откатываются (баг, не формат).\n";
    }
}
