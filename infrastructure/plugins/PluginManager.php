<?php

namespace app\infrastructure\plugins;

use Yii;
use app\infrastructure\plugins\interfaces\PluginInterface;
use app\infrastructure\plugins\interfaces\PaymentGatewayInterface;
use app\infrastructure\plugins\interfaces\ShippingProviderInterface;

class PluginManager
{
    private static $instance = null;
    private $plugins = [];
    private $activePlugins = [];
    
    private function __construct()
    {
        $this->loadPlugins();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Загрузка всех доступных плагинов
     */
    private function loadPlugins(): void
    {
        $pluginClasses = [
            // Payment plugins
            'app\infrastructure\plugins\payment\StripePaymentPlugin',
            'app\infrastructure\plugins\payment\YooKassaPaymentPlugin',
            
            // Shipping plugins
            'app\infrastructure\plugins\shipping\BelpostShippingPlugin',
        ];
        
        foreach ($pluginClasses as $class) {
            if (class_exists($class)) {
                $plugin = new $class();
                $this->plugins[$plugin->getId()] = $plugin;
                
                if ($plugin->isActive()) {
                    $this->activePlugins[$plugin->getId()] = $plugin;
                    $plugin->init();
                }
            }
        }
    }
    
    /**
     * Получить все плагины
     */
    public function getAllPlugins(): array
    {
        return $this->plugins;
    }
    
    /**
     * Получить активные плагины
     */
    public function getActivePlugins(): array
    {
        return $this->activePlugins;
    }
    
    /**
     * Получить плагин по ID
     */
    public function getPlugin(string $id): ?PluginInterface
    {
        return $this->plugins[$id] ?? null;
    }
    
    /**
     * Получить активные платёжные шлюзы
     */
    public function getActivePaymentGateways(): array
    {
        return array_filter($this->activePlugins, function($plugin) {
            return $plugin instanceof PaymentGatewayInterface;
        });
    }
    
    /**
     * Получить активных провайдеров доставки
     */
    public function getActiveShippingProviders(): array
    {
        return array_filter($this->activePlugins, function($plugin) {
            return $plugin instanceof ShippingProviderInterface;
        });
    }
    
    /**
     * Активировать плагин
     */
    public function activatePlugin(string $id): bool
    {
        $plugin = $this->getPlugin($id);
        
        if (!$plugin) {
            return false;
        }
        
        if ($plugin->activate()) {
            $this->activePlugins[$id] = $plugin;
            $plugin->init();
            return true;
        }
        
        return false;
    }
    
    /**
     * Деактивировать плагин
     */
    public function deactivatePlugin(string $id): bool
    {
        $plugin = $this->getPlugin($id);
        
        if (!$plugin) {
            return false;
        }
        
        if ($plugin->deactivate()) {
            unset($this->activePlugins[$id]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Сохранить настройки плагина
     */
    public function savePluginSettings(string $id, array $settings): bool
    {
        $plugin = $this->getPlugin($id);
        
        if (!$plugin) {
            return false;
        }
        
        return $plugin->saveSettings($settings);
    }
    
    /**
     * Получить настройки плагина
     */
    public function getPluginSettings(string $id): array
    {
        $plugin = $this->getPlugin($id);
        
        if (!$plugin) {
            return [];
        }
        
        return $plugin->getSettings();
    }
}
