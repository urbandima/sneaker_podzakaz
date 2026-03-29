document.addEventListener('DOMContentLoaded', function() {
    // Управление открытием сайдбара с фильтрами
    const filterBtn = document.querySelector('.btn-filter');
    const sidebar = document.querySelector('.sidebar');
    const closeBtn = document.querySelector('.sidebar .close-btn');

    if (filterBtn && sidebar) {
        filterBtn.addEventListener('click', () => {
            sidebar.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // Закрытие при клике вне сайдбара
    document.addEventListener('click', (e) => {
        if (sidebar && sidebar.classList.contains('active') && !sidebar.contains(e.target) && !document.addEventListener('DOMContentLoaded', function() {
    // Управлени?     // Управление открытием сайдб
});
