/**
 * Dark Mode Toggle
 * 
 * Автоматическое переключение на тёмную тему
 */

const THEME_KEY = 'theme';
const DARK_THEME = 'dark';
const LIGHT_THEME = 'light';

/**
 * Инициализация Dark Mode
 */
function initDarkMode() {
    // Проверяем сохранённую тему
    const savedTheme = localStorage.getItem(THEME_KEY);
    
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        // Проверяем системное предпочтение
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDark ? DARK_THEME : LIGHT_THEME);
    }
    
    // Создаём кнопку переключения
    createDarkModeToggle();
    
    // Слушаем изменения системной темы
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem(THEME_KEY)) {
            applyTheme(e.matches ? DARK_THEME : LIGHT_THEME);
        }
    });
}

/**
 * Применить тему
 */
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(THEME_KEY, theme);
    
    // Обновляем иконку кнопки
    const toggleBtn = document.querySelector('.dark-mode-toggle');
    if (toggleBtn) {
        if (theme === DARK_THEME) {
            toggleBtn.innerHTML = '<i class="bi bi-sun icon-sun"></i><i class="bi bi-moon icon-moon"></i>';
        } else {
            toggleBtn.innerHTML = '<i class="bi bi-moon icon-moon"></i><i class="bi bi-sun icon-sun"></i>';
        }
    }
}

/**
 * Переключить тему
 */
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || LIGHT_THEME;
    const newTheme = currentTheme === DARK_THEME ? LIGHT_THEME : DARK_THEME;
    applyTheme(newTheme);
}

/**
 * Создать кнопку переключения
 */
function createDarkModeToggle() {
    // Проверяем, есть ли уже кнопка
    if (document.querySelector('.dark-mode-toggle')) {
        return;
    }
    
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'dark-mode-toggle';
    toggleBtn.setAttribute('aria-label', 'Переключить тёмную тему');
    toggleBtn.setAttribute('title', 'Переключить тему');
    toggleBtn.onclick = toggleTheme;
    
    const currentTheme = document.documentElement.getAttribute('data-theme') || LIGHT_THEME;
    if (currentTheme === DARK_THEME) {
        toggleBtn.innerHTML = '<i class="bi bi-sun icon-sun"></i><i class="bi bi-moon icon-moon"></i>';
    } else {
        toggleBtn.innerHTML = '<i class="bi bi-moon icon-moon"></i><i class="bi bi-sun icon-sun"></i>';
    }
    
    document.body.appendChild(toggleBtn);
}

/**
 * Получить текущую тему
 */
function getCurrentTheme() {
    return document.documentElement.getAttribute('data-theme') || LIGHT_THEME;
}

/**
 * Установить тему программно
 */
function setTheme(theme) {
    if (theme === DARK_THEME || theme === LIGHT_THEME) {
        applyTheme(theme);
    }
}

// Инициализация при загрузке страницы
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDarkMode);
} else {
    initDarkMode();
}

// Экспорт функций для использования в других скриптах
window.DarkMode = {
    toggle: toggleTheme,
    set: setTheme,
    get: getCurrentTheme,
    init: initDarkMode
};
