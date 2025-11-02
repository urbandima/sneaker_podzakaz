<?php
$this->title = 'Личный кабинет - СНИКЕРХЭД';
?>

<div class="page-container">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 3rem 1rem;">
        <h1 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 2rem;">👤 Личный кабинет</h1>
        
        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem;">
            <!-- Sidebar -->
            <div>
                <div style="background: #fff; border-radius: 12px; padding: 1.5rem;">
                    <nav style="display: grid; gap: 0.5rem;">
                        <a href="#" style="display: block; padding: 0.75rem; background: #000; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">
                            <i class="bi bi-person"></i> Профиль
                        </a>
                        <a href="#" style="display: block; padding: 0.75rem; color: #000; text-decoration: none; border-radius: 6px; font-weight: 600;">
                            <i class="bi bi-bag"></i> Мои заказы
                        </a>
                        <a href="/catalog/favorites" style="display: block; padding: 0.75rem; color: #000; text-decoration: none; border-radius: 6px; font-weight: 600;">
                            <i class="bi bi-heart"></i> Избранное
                        </a>
                        <a href="#" style="display: block; padding: 0.75rem; color: #000; text-decoration: none; border-radius: 6px; font-weight: 600;">
                            <i class="bi bi-geo-alt"></i> Адреса доставки
                        </a>
                        <a href="#" style="display: block; padding: 0.75rem; color: #ef4444; text-decoration: none; border-radius: 6px; font-weight: 600;">
                            <i class="bi bi-box-arrow-right"></i> Выйти
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Content -->
            <div>
                <!-- Not logged in -->
                <div style="background: #fff; border-radius: 12px; padding: 3rem; text-align: center;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🔐</div>
                    <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">Войдите в аккаунт</h2>
                    <p style="color: #666; margin-bottom: 2rem;">Чтобы просматривать заказы и управлять профилем</p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button style="padding: 1rem 2rem; background: #000; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                            Войти
                        </button>
                        <button style="padding: 1rem 2rem; background: #fff; color: #000; border: 2px solid #e5e7eb; border-radius: 8px; font-weight: 700; cursor: pointer;">
                            Регистрация
                        </button>
                    </div>
                </div>
                
                <!-- Logged in (hidden) -->
                <div style="display: none;">
                    <div style="background: #fff; border-radius: 12px; padding: 2rem;">
                        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">Личная информация</h2>
                        <form style="display: grid; gap: 1rem;">
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Имя</label>
                                <input type="text" value="Иван Иванов" style="width: 100%; padding: 0.875rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Email</label>
                                <input type="email" value="ivan@example.com" style="width: 100%; padding: 0.875rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Телефон</label>
                                <input type="tel" value="+375 29 123-45-67" style="width: 100%; padding: 0.875rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                            </div>
                            <button type="submit" style="padding: 1rem; background: #000; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                                Сохранить изменения
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .page-container > .container > div {
        grid-template-columns: 1fr !important;
    }
}
</style>
