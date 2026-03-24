<?php

namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\infrastructure\services\ElasticsearchService;
use app\backend\modules\catalog\models\Product;

/**
 * ElasticsearchController - Консольные команды для управления Elasticsearch
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * php yii elasticsearch/create-index    - Создать индекс
 * php yii elasticsearch/index-all       - Индексировать все товары
 * php yii elasticsearch/reindex         - Пересоздать индекс и индексировать все товары
 * php yii elasticsearch/index-product 123 - Индексировать товар по ID
 */
class ElasticsearchController extends Controller
{
    /**
     * Создать индекс Elasticsearch
     */
    public function actionCreateIndex()
    {
        $this->stdout("Создание индекса Elasticsearch...\n", \yii\helpers\Console::FG_YELLOW);
        
        $es = new ElasticsearchService();
        
        if ($es->createIndex()) {
            $this->stdout("✅ Индекс создан успешно\n", \yii\helpers\Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stdout("❌ Ошибка создания индекса\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
    
    /**
     * Индексировать все товары
     */
    public function actionIndexAll()
    {
        $this->stdout("Индексация всех товаров...\n", \yii\helpers\Console::FG_YELLOW);
        
        $es = new ElasticsearchService();
        $result = $es->indexAllProducts();
        
        $this->stdout("✅ Индексация завершена\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("   Успешно: {$result['success']}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("   Ошибок: {$result['failed']}\n", \yii\helpers\Console::FG_RED);
        
        return ExitCode::OK;
    }
    
    /**
     * Пересоздать индекс и индексировать все товары
     */
    public function actionReindex()
    {
        $this->stdout("Пересоздание индекса и индексация всех товаров...\n", \yii\helpers\Console::FG_YELLOW);
        
        $es = new ElasticsearchService();
        
        // Удаляем старый индекс
        $this->stdout("Удаление старого индекса...\n", \yii\helpers\Console::FG_YELLOW);
        try {
            $client = new \Elastic\Elasticsearch\ClientBuilder();
            $client = $client::create()->setHosts([env('ELASTICSEARCH_HOST', 'localhost:9200')])->build();
            $client->indices()->delete(['index' => 'products']);
            $this->stdout("✅ Старый индекс удалён\n", \yii\helpers\Console::FG_GREEN);
        } catch (\Exception $e) {
            $this->stdout("⚠️  Старый индекс не найден или уже удалён\n", \yii\helpers\Console::FG_YELLOW);
        }
        
        // Создаём новый индекс
        if (!$es->createIndex()) {
            $this->stdout("❌ Ошибка создания индекса\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        // Индексируем все товары
        $result = $es->indexAllProducts();
        
        $this->stdout("✅ Реиндексация завершена\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("   Успешно: {$result['success']}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("   Ошибок: {$result['failed']}\n", \yii\helpers\Console::FG_RED);
        
        return ExitCode::OK;
    }
    
    /**
     * Индексировать товар по ID
     * 
     * @param int $id ID товара
     */
    public function actionIndexProduct($id)
    {
        $this->stdout("Индексация товара #{$id}...\n", \yii\helpers\Console::FG_YELLOW);
        
        $product = Product::findOne($id);
        
        if (!$product) {
            $this->stdout("❌ Товар #{$id} не найден\n", \yii\helpers\Console::FG_RED);
            return ExitCode::DATAERR;
        }
        
        $es = new ElasticsearchService();
        
        if ($es->indexProduct($product)) {
            $this->stdout("✅ Товар #{$id} индексирован\n", \yii\helpers\Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stdout("❌ Ошибка индексации товара #{$id}\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
