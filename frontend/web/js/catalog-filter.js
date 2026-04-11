/**
 * Catalog Filter — управление фильтрами в сайдбаре каталога.
 * - Открытие/закрытие мобильного сайдбара
 * - Сворачивание/разворачивание групп фильтров
 * Uses SH.* utilities from utils.js
 */
document.addEventListener('DOMContentLoaded', function () {
    var filterBtn = document.querySelector('.btn-filter');
    var sidebar = document.querySelector('.sidebar');
    var closeBtn = document.querySelector('.sidebar .close-btn');

    // Мобильный сайдбар
    if (filterBtn && sidebar) {
        filterBtn.addEventListener('click', function () {
            SH.openModal(sidebar);
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', function () {
            SH.closeModal(sidebar);
        });
    }

    document.addEventListener('click', function (e) {
        if (sidebar && sidebar.classList.contains('active') &&
            !sidebar.contains(e.target) &&
            filterBtn && e.target !== filterBtn && !filterBtn.contains(e.target)) {
            SH.closeModal(sidebar);
        }
    });

    // Сворачивание групп фильтров по клику на заголовок
    var filterTitles = document.querySelectorAll('.filter-title');
    filterTitles.forEach(function (title) {
        title.addEventListener('click', function () {
            var group = this.closest('.filter-group');
            if (group) {
                group.classList.toggle('collapsed');
            }
        });
    });
});
