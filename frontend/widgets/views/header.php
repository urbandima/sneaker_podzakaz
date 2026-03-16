<?php

use yii\helpers\Html;

?>

<!-- Header -->
<header class="main-header">
    <div class="container">
        <div class="header-content">
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
