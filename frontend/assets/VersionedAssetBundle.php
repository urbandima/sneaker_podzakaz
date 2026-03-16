<?php

namespace app\frontend\assets;

use yii\web\AssetBundle;

/**
 * Базовый класс для asset bundles с поддержкой версионирования
 */
class VersionedAssetBundle extends AssetBundle
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        // Добавляем timestamp к файлам для кеширования
        if ($this->baseUrl && ($this->basePath || $this->sourcePath)) {
            $this->appendTimestamp();
        }
    }
    
    /**
     * Добавляет timestamp к URL файлов
     */
    protected function appendTimestamp()
    {
        $basePath = \Yii::getAlias($this->basePath ?: $this->sourcePath);
        
        foreach (['css', 'js'] as $type) {
            if (!empty($this->$type)) {
                foreach ($this->$type as $key => $file) {
                    $filePath = $basePath . '/' . $file;
                    if (file_exists($filePath)) {
                        $timestamp = filemtime($filePath);
                        $this->$type[$key] = $file . '?v=' . $timestamp;
                    }
                }
            }
        }
    }
}
