/**
 * Scroll Reveal — плавное появление элементов при скролле.
 * Использует IntersectionObserver для производительности.
 * Добавьте class="reveal" к элементу для появления при скролле.
 * Добавьте class="reveal-stagger" к контейнеру для каскадного появления.
 */
(function () {
    'use strict';

    if (typeof IntersectionObserver === 'undefined') return;

    document.addEventListener('DOMContentLoaded', function () {
        var elements = document.querySelectorAll('.reveal, .reveal-stagger');
        if (elements.length === 0) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15,
            rootMargin: '0px 0px -40px 0px'
        });

        elements.forEach(function (el) {
            observer.observe(el);
        });
    });
})();
