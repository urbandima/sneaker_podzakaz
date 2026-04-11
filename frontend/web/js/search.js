/**
 * Live Search — живой поиск (jQuery version, для страниц с jQuery).
 * Uses SH.* utilities from utils.js
 *
 * NOTE: Основной поиск реализован в app.js (vanilla JS, search modal).
 * Этот файл — для альтернативного инлайн-поиска на страницах с jQuery.
 */

(function () {
    'use strict';

    if (typeof $ === 'undefined') return;

    var searchTimeout;
    var searchInput;
    var searchResults;

    $(document).ready(function () {
        searchInput = $('#searchInput');
        searchResults = $('#searchResults');
        if (!searchInput.length || !searchResults.length) return;

        searchInput.on('input', SH.debounce(function () {
            var query = $(this).val().trim();
            if (query.length < 2) {
                searchResults.hide().empty();
                return;
            }
            performSearch(query);
        }, 300));

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.search-container').length) {
                searchResults.hide();
            }
        });

        searchInput.on('focus', function () {
            if ($(this).val().trim().length >= 2 && searchResults.children().length > 0) {
                searchResults.show();
            }
        });
    });

    function performSearch(query) {
        SH.fetch('/catalog/search?q=' + encodeURIComponent(query))
            .then(function (response) {
                displaySearchResults(response.results || []);
            })
            .catch(function () {
                searchResults.html('<div class="search-empty"><p>Ошибка поиска</p></div>').show();
            });
    }

    function displaySearchResults(results) {
        searchResults.empty();

        if (results.length === 0) {
            searchResults.html(
                '<div class="search-empty"><i class="bi bi-search"></i><p>Ничего не найдено</p></div>'
            ).show();
            return;
        }

        results.forEach(function (product) {
            var discountBadge = product.discount > 0
                ? '<span class="discount-badge">-' + product.discount + '%</span>'
                : '';
            var oldPrice = product.old_price
                ? '<span class="old-price">' + product.old_price + ' BYN</span>'
                : '';

            var item = $(
                '<a href="' + product.url + '" class="search-result-item">' +
                    '<div class="search-item-image">' +
                        '<img src="' + product.image + '" alt="' + product.name + '">' +
                        discountBadge +
                    '</div>' +
                    '<div class="search-item-info">' +
                        '<div class="search-item-brand">' + product.brand + '</div>' +
                        '<div class="search-item-name">' + product.name + '</div>' +
                        '<div class="search-item-price">' +
                            '<span class="price">' + product.price + ' BYN</span>' +
                            oldPrice +
                        '</div>' +
                    '</div>' +
                '</a>'
            );

            searchResults.append(item);
        });

        searchResults.show();
    }
})();
