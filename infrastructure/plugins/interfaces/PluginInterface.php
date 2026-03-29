<?php

namespace app\infrastructure\plugins\interfaces;

interface PluginInterface
{
    /**
     * Уникальный идентификатор плагина
     */
    public function getId(): string;
    
    /**
     * Название плагина
     */
    public function getName(): string;
    
    /**
     * Описание плагина
     */
    public function getDescription(): string;
    
    /**
     * Версия плагина
     */
    public function getVersion(): string;
    
    /**
     * Автор плагина
     */
    public function getAuthor(): string;
    
    /**
     * Инициализация плагина
     */
    public function init(): void;
    
    /**
     * Активация плагина
     */
    public function activate(): bool;
    
    /**
     * Деактивация плагина
     */
    public function deactivate(): bool;
    
    /**
     * Проверка активности плагина
     */
    public function isActive(): bool;
    
    /**
     * Получение настроек плагина
     */
    public function getSettings(): array;
    
    /**
     * Сохранение настроек плагина
     */
    public function saveSettings(array $settings): bool;
}
