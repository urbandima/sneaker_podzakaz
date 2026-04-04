<?php

use yii\helpers\Html;
use app\backend\modules\admin\models\SidebarMenuItem;

// Получаем пункты меню из БД
$menuItems = SidebarMenuItem::getActiveItems();

?>

<!-- Header -->
<header class="main-header">
    <div class="container">
        <div class="header-content">
            <!-- Sidebar Menu Toggle (слева) -->
            <button class="sidebar-menu-toggle" onclick="toggleSidebarMenu()" aria-label="Открыть меню" aria-expanded="false" aria-controls="sidebarMenu">
                <i class="bi bi-list"></i>
            </button>
            
            <!-- Logo -->
            <a href="/" class="logo">
                <img src="/images/logo.png" alt="<?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>">
            </a>
            
            <!-- Navigation -->
            <nav class="main-nav">
                <ul class="nav-menu">
                    <li><a href="/catalog">Каталог</a></li>
                    <li><a href="/brands">Бренды</a></li>
                    <li><a href="/sale">Скидки</a></li>
                    <li><a href="/about">О нас</a></li>
                    <li><a href="/contacts">Контакты</a></li>
                </ul>
            </nav>
            
            <!-- Actions -->
            <div class="header-actions">
                <button class="btn-search" onclick="openSearch()" aria-label="Поиск товаров">
                    <i class="bi bi-search"></i>
                </button>
                
                <a href="/account/wishlist" class="btn-wishlist" aria-label="Избранное">
                    <i class="bi bi-heart"></i>
                    <span class="wishlist-counter sr-only">0 товаров в избранном</span>
                </a>
                
                <a href="/cart" class="btn-cart" aria-label="Корзина">
                    <i class="bi bi-cart3"></i>
                    <span class="cart-counter sr-only">0 товаров в корзине</span>
                </a>
                
                <a href="/account" class="btn-account" aria-label="Личный кабинет">
                    <i class="bi bi-person"></i>
                </a>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Открыть меню">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>
</header>

<!-- Sidebar Menu Overlay -->
<div class="sidebar-menu-overlay" id="sidebarMenuOverlay" onclick="toggleSidebarMenu()"></div>

<!-- Sidebar Menu -->
<aside class="sidebar-menu" id="sidebarMenu" aria-hidden="true">
    <div class="sidebar-menu-header">
        <a href="/" class="sidebar-logo">
            <img src="/images/logo.png" alt="<?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>">
        </a>
        <button class="sidebar-close" onclick="toggleSidebarMenu()" aria-label="Закрыть меню">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <?php foreach ($menuItems as $item): ?>
            <?php if ($item->type === SidebarMenuItem::TYPE_DIVIDER): ?>
                <hr class="sidebar-divider">
            <?php elseif ($item->type === SidebarMenuItem::TYPE_HEADER): ?>
                <div class="sidebar-header"><?= Html::encode($item->title) ?></div>
            <?php elseif ($item->type === SidebarMenuItem::TYPE_BANNER): ?>
                <a href="<?= Html::encode($item->getLinkUrl() ?: '#') ?>" class="sidebar-banner" <?= $item->target_blank ? 'target="_blank"' : '' ?>>
                    <?php if ($item->image): ?>
                        <img src="<?= Html::encode($item->image) ?>" alt="<?= Html::encode($item->title) ?>">
                    <?php endif; ?>
                    <span><?= Html::encode($item->title) ?></span>
                </a>
            <?php else: ?>
                <a href="<?= Html::encode($item->getLinkUrl() ?: '#') ?>" 
                   class="sidebar-link <?= $item->css_class ?>" 
                   <?= $item->target_blank ? 'target="_blank"' : '' ?>>
                    <?php if ($item->icon): ?>
                        <i class="<?= Html::encode($item->getIconClass()) ?>"></i>
                    <?php endif; ?>
                    <span><?= Html::encode($item->title) ?></span>
                    <?php if ($item->children): ?>
                        <i class="bi bi-chevron-right sidebar-arrow"></i>
                    <?php endif; ?>
                </a>
                
                <?php if ($item->children): ?>
                    <div class="sidebar-submenu">
                        <?php foreach ($item->children as $child): ?>
                            <?php if ($child->is_active && $child->is_visible): ?>
                                <a href="<?= Html::encode($child->getLinkUrl() ?: '#') ?>" 
                                   class="sidebar-sublink <?= $child->css_class ?>" 
                                   <?= $child->target_blank ? 'target="_blank"' : '' ?>>
                                    <?php if ($child->icon): ?>
                                        <i class="<?= Html::encode($child->getIconClass()) ?>"></i>
                                    <?php endif; ?>
                                    <span><?= Html::encode($child->title) ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-contacts">
            <a href="tel:+375291234567" class="sidebar-phone">
                <i class="bi bi-telephone"></i>
                <span>+375 (29) 123-45-67</span>
            </a>
        </div>
        <div class="sidebar-social">
            <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
            <a href="#" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
            <a href="#" aria-label="Viber"><i class="bi bi-chat-dots"></i></a>
        </div>
    </div>
</aside>

