<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\helpers\VarDumper;
use app\models\Characteristic;
use app\models\CharacteristicValue;
use app\models\Product;
use app\models\ProductCharacteristicValue;

/**
 * Консольные команды для работы с характеристиками
 */
class CharacteristicController extends Controller
{
    /**
     * Импорт характеристик и значений из CSV файла
     *
     * Пример:
     * php yii characteristic/import --file=@app/data/characteristics.csv
     */
    public function actionImport(string $file): int
    {
        $path = Yii::getAlias($file);
        if (!file_exists($path)) {
            $this->stderr("Файл {$path} не найден\n", Console::FG_RED);
            return ExitCode::NOINPUT;
        }

        $rows = array_map('str_getcsv', file($path));
        if (!$rows) {
            $this->stderr("Файл пуст\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $header = array_map('trim', array_shift($rows));
        $required = ['key', 'name', 'type', 'value'];
        foreach ($required as $column) {
            if (!in_array($column, $header, true)) {
                $this->stderr("Отсутствует колонка {$column}\n", Console::FG_RED);
                return ExitCode::DATAERR;
            }
        }

        $count = 0;
        foreach ($rows as $row) {
            if (count($row) !== count($header)) {
                continue;
            }
            $data = array_combine($header, $row);

            $characteristic = Characteristic::findOne(['key' => $data['key']]);
            if (!$characteristic) {
                $characteristic = new Characteristic([
                    'key' => $data['key'],
                    'name' => $data['name'],
                    'type' => $data['type'] ?? Characteristic::TYPE_SELECT,
                    'is_filter' => 1,
                    'is_active' => 1,
                ]);
                if (!$characteristic->save()) {
                    $this->stderr("Не удалось сохранить характеристику {$data['key']}\n", Console::FG_RED);
                    $this->stderr(VarDumper::dumpAsString($characteristic->getErrors()) . "\n", Console::FG_RED);
                    continue;
                }
            }

            $value = CharacteristicValue::findOne([
                'characteristic_id' => $characteristic->id,
                'slug' => $data['slug'] ?? null,
            ]);

            if (!$value) {
                $value = new CharacteristicValue([
                    'characteristic_id' => $characteristic->id,
                ]);
            }

            $value->value = $data['value'];
            if (!empty($data['slug'])) {
                $value->slug = $data['slug'];
            }
            $value->sort_order = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;
            $value->is_active = 1;

            if ($value->save()) {
                $count++;
            } else {
                $this->stderr("Не удалось сохранить значение {$data['value']}\n", Console::FG_RED);
                $this->stderr(VarDumper::dumpAsString($value->getErrors()) . "\n", Console::FG_RED);
            }
        }

        $this->stdout("Импортировано {$count} значений\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Массовое назначение значения характеристик товарам по фильтрам
     *
     * Пример:
     * php yii characteristic/bulk-assign --char=material --value=leather --brand=5
     */
    public function actionBulkAssign(string $char, string $value, int $brand = null, int $category = null, float $priceFrom = null, float $priceTo = null): int
    {
        $characteristic = Characteristic::findOne(['key' => $char]);
        if (!$characteristic) {
            $this->stderr("Характеристика {$char} не найдена\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $valueModel = CharacteristicValue::findOne([
            'characteristic_id' => $characteristic->id,
            'slug' => $value,
        ]);

        if (!$valueModel) {
            $this->stderr("Значение {$value} не найдено\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $query = Product::find()->where(['is_active' => 1]);
        if ($brand) {
            $query->andWhere(['brand_id' => $brand]);
        }
        if ($category) {
            $query->andWhere(['category_id' => $category]);
        }
        if ($priceFrom) {
            $query->andWhere(['>=', 'price', $priceFrom]);
        }
        if ($priceTo) {
            $query->andWhere(['<=', 'price', $priceTo]);
        }

        $productIds = $query->select('id')->column();
        if (!$productIds) {
            $this->stdout("Товары по заданным критериям не найдены\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $batch = [];
        $now = date('Y-m-d H:i:s');
        foreach ($productIds as $productId) {
            $batch[] = [$productId, $characteristic->id, $valueModel->id, $now];
        }

        Yii::$app->db->createCommand()->batchInsert(
            ProductCharacteristicValue::tableName(),
            ['product_id', 'characteristic_id', 'characteristic_value_id', 'created_at'],
            $batch
        )->execute();

        $this->stdout("Назначено {$valueModel->value} ({$valueModel->id}) товарам: " . count($batch) . " шт.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
