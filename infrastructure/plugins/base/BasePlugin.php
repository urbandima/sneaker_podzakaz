<?php

namespace app\infrastructure\plugins\base;

use Yii;
use app\infrastructure\plugins\interfaces\PluginInterface;

abstract class BasePlugin implements PluginInterface
{
    protected $id;
    protected $name;
    protected $description;
    protected $version;
    protected $author;
    protected $settings = [];
    protected $isActive = false;
    
    public function __construct()
    {
        $this->loadSettings();
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getDescription(): string
    {
        return $this->description;
    }
    
    public function getVersion(): string
    {
        return $this->version;
    }
    
    public function getAuthor(): string
    {
        return $this->author;
    }
    
    public function init(): void
    {
        // Переопределяется в дочерних классах
    }
    
    public function activate(): bool
    {
        $this->isActive = true;
        return $this->saveSettings(['is_active' => true]);
    }
    
    public function deactivate(): bool
    {
        $this->isActive = false;
        return $this->saveSettings(['is_active' => false]);
    }
    
    public function isActive(): bool
    {
        return $this->isActive;
    }
    
    public function getSettings(): array
    {
        return $this->settings;
    }
    
    public function saveSettings(array $settings): bool
    {
        $this->settings = array_merge($this->settings, $settings);
        
        $cacheKey = 'plugin_settings_' . $this->id;
        Yii::$app->cache->set($cacheKey, $this->settings, 3600 * 24);
        
        return true;
    }
    
    protected function loadSettings(): void
    {
        $cacheKey = 'plugin_settings_' . $this->id;
        $settings = Yii::$app->cache->get($cacheKey);
        
        if ($settings !== false) {
            $this->settings = $settings;
            $this->isActive = $settings['is_active'] ?? false;
        }
    }
    
    protected function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }
}
