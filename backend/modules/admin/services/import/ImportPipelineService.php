<?php

namespace app\backend\modules\admin\services\import;

/**
 * ImportPipelineService — сервис импорта с очередью
 * 
 * Рекомендация #24: Product Import Pipeline
 * 
 * Статусы: pending, processing, completed, failed
 */
class ImportPipelineService
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    /**
     * Создать задачу импорта
     */
    public function createTask(int $sourceId, string $filePath, array $options = []): int
    {
        $task = new \app\backend\modules\admin\models\import\ImportTask();
        $task->source_id = $sourceId;
        $task->file_path = $filePath;
        $task->status = self::STATUS_PENDING;
        $task->options = json_encode($options);
        $task->created_at = time();
        $task->created_by = \Yii::$app->user->id ?? null;
        $task->save();
        
        return $task->id;
    }
    
    /**
     * Запустить обработку
     */
    public function process(int $taskId): void
    {
        $task = \app\backend\modules\admin\models\import\ImportTask::findOne($taskId);
        if (!$task) {
            throw new \Exception("Task not found: {$taskId}");
        }
        
        // Обновляем статус
        $task->status = self::STATUS_PROCESSING;
        $task->started_at = time();
        $task->save();
        
        try {
            // Получаем парсер для источника
            $parser = $this->getParser($task->source);
            
            // Парсим файл
            $data = $parser->parse($task->file_path);
            
            // Валидируем
            $validated = $this->validate($data);
            
            // Импортируем
            $result = $this->import($validated, $task->source);
            
            // Успех
            $task->status = self::STATUS_COMPLETED;
            $task->completed_at = time();
            $task->result = json_encode([
                'total' => count($data),
                'success' => $result['success'],
                'failed' => $result['failed'],
                'errors' => $result['errors'] ?? [],
            ]);
            
        } catch (\Exception $e) {
            $task->status = self::STATUS_FAILED;
            $task->error_message = $e->getMessage();
        }
        
        $task->save();
    }
    
    /**
     * Предпросмотр импорта (рекомендация #47)
     */
    public function preview(int $taskId): array
    {
        $task = \app\backend\modules\admin\models\import\ImportTask::findOne($taskId);
        if (!$task) {
            throw new \Exception("Task not found");
        }
        
        $parser = $this->getParser($task->source);
        $data = $parser->parse($task->file_path);
        
        // Показываем первые 10 товаров
        $preview = array_slice($data, 0, 10);
        
        // Проверяем конфликты
        $conflicts = $this->checkConflicts($data);
        
        return [
            'total' => count($data),
            'preview' => $preview,
            'conflicts' => $conflicts,
            'will_create' => count(array_filter($data, fn($item) => !$item['exists'])),
            'will_update' => count(array_filter($data, fn($item) => $item['exists'])),
        ];
    }
    
    /**
     * Получить парсер
     */
    private function getParser($source)
    {
        $parsers = [
            'poizon' => PoizonParser::class,
            'lamoda' => LamodaParser::class,
            'csv' => CsvParser::class,
        ];
        
        $class = $parsers[$source->type] ?? CsvParser::class;
        return new $class();
    }
    
    /**
     * Валидация данных
     */
    private function validate(array $data): array
    {
        $validated = [];
        foreach ($data as $item) {
            // Проверка обязательных полей
            if (empty($item['sku']) || empty($item['name'])) {
                continue;
            }
            $validated[] = $item;
        }
        return $validated;
    }
    
    /**
     * Импорт данных
     */
    private function import(array $data, $source): array
    {
        $success = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($data as $item) {
            try {
                $this->importProduct($item, $source);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'sku' => $item['sku'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return compact('success', 'failed', 'errors');
    }
    
    /**
     * Импорт одного товара
     */
    private function importProduct(array $data, $source): void
    {
        $product = \app\backend\modules\catalog\models\Product::find()
            ->where(['sku' => $data['sku']])
            ->one();
        
        if (!$product) {
            $product = new \app\backend\modules\catalog\models\Product();
            $product->sku = $data['sku'];
            $product->created_at = time();
        }
        
        $product->name = $data['name'];
        $product->price = $data['price'];
        $product->brand_id = $this->resolveBrand($data['brand'] ?? null);
        $product->category_id = $this->resolveCategory($data['category'] ?? null);
        $product->updated_at = time();
        
        if (!$product->save()) {
            throw new \Exception('Failed to save product: ' . implode(', ', $product->getErrorSummary(true)));
        }
    }
    
    /**
     * Проверка конфликтов
     */
    private function checkConflicts(array $data): array
    {
        $conflicts = [];
        $skus = array_column($data, 'sku');
        
        $existing = \app\backend\modules\catalog\models\Product::find()
            ->where(['sku' => $skus])
            ->indexBy('sku')
            ->all();
        
        foreach ($data as $item) {
            if (isset($existing[$item['sku']])) {
                $oldPrice = $existing[$item['sku']]->price;
                $newPrice = $item['price'];
                
                if ($oldPrice != $newPrice) {
                    $conflicts[] = [
                        'sku' => $item['sku'],
                        'type' => 'price_change',
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                    ];
                }
            }
        }
        
        return $conflicts;
    }
    
    private function resolveBrand(?string $name): ?int
    {
        if (!$name) return null;
        // Логика поиска/создания бренда
        return null;
    }
    
    private function resolveCategory(?string $name): ?int
    {
        if (!$name) return null;
        // Логика поиска/создания категории
        return null;
    }
}
