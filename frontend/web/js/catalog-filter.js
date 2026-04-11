/**
 * Catalog Filter — управление открытием/закрытием сайдбара с фильтрами.
 * Uses SH.* utilities from utils.js for scroll lock.
 */
document.addEventListener('DOMContentLoaded', function () {
    var filterBtn = document.querySelector('.btn-filter');
    var sidebar = document.querySelector('.sidebar');
    var closeBtn = document.querySelector('.sidebar .close-btn');

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

    // Закрытие при клике вне сайдбара
    document.addEventListener('click', function (e) {
        if (sidebar && sidebar.classList.contains('active') &&
            !sidebar.contains(e.target) &&
            e.target !== filterBtn && !filterBtn.contains(e.target)) {
            SH.closeModal(sidebar);
        }
    });
});
