/**
 * Live Search - живой поиск
 */

let searchTimeout;
let searchInput;
let searchResults;

$(document).ready(function() {
    searchInput = $('#searchInput');
    searchResults = $('#searchResults');
    
    // Событие ввода в поле поиска
    searchInput.on('input', function() {
        const query = $(this).val().trim();
        
        // Очищаем предыдущий таймаут
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.hide().empty();
            return;
        }
        
        // Задержка 300ms перед поиском
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Закрытие результатов при клике вне
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-container').length) {
            searchResults.hide();
        }
    });
    
    // Показать результаты при фокусе (если есть query)
    searchInput.on('focus', function() {
        if ($(this).val().trim().length >= 2 && searchResults.children().length > 0) {
            searchResults.show();
        }
    });
});

// Выполнить поиск
function performSearch(query) {
    $.ajax({
        url: '/catalog/search',
        method: 'GET',
        data: { q: query },
        success: function(response) {
            displaySearchResults(response.results);
        },
        error: function() {
            searchResults.html('<div class="search-error">Ошибка поиска</div>').show();
        }
    });
}

// Отобразить результаты поиска
function displaySearchResults(results) {
    searchResults.empty();
    
    if (results.length === 0) {
        searchResults.html(`
            <div class="search-empty">
                <i class="bi bi-search"></i>
                <p>Ничего не найдено</p>
            </div>
        `).show();
        return;
    }
    
    results.forEach(product => {
        const discountBadge = product.discount > 0 
            ? `<span class="discount-badge">-${product.discount}%</span>` 
            : '';
        
        const oldPrice = product.old_price 
            ? `<span class="old-price">${product.old_price} BYN</span>` 
            : '';
        
        const item = $(`
            <a href="${product.url}" class="search-result-item">
                <div class="search-item-image">
                    <img src="${product.image}" alt="${product.name}">
                    ${discountBadge}
                </div>
                <div class="search-item-info">
                    <div class="search-item-brand">${product.brand}</div>
                    <div class="search-item-name">${product.name}</div>
                    <div class="search-item-price">
                        <span class="price">${product.price} BYN</span>
                        ${oldPrice}
                    </div>
                </div>
            </a>
        `);
        
        searchResults.append(item);
    });
    
    searchResults.show();
}

// Стили для результатов поиска (добавить в head)
const searchStyles = `
<style>
.search-container {
    position: relative;
}

#searchResults {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    max-height: 500px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: 4px;
}

.search-result-item {
    display: flex;
    gap: 1rem;
    padding: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
}

.search-result-item:hover {
    background: #f9fafb;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-item-image {
    position: relative;
    width: 60px;
    height: 60px;
    flex-shrink: 0;
    border-radius: 6px;
    overflow: hidden;
    background: #f9fafb;
}

.search-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.discount-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #ef4444;
    color: #fff;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.625rem;
    font-weight: 700;
}

.search-item-info {
    flex: 1;
    min-width: 0;
}

.search-item-brand {
    font-size: 0.75rem;
    color: #666;
    margin-bottom: 2px;
}

.search-item-name {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.search-item-price {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.search-item-price .price {
    font-weight: 700;
    font-size: 0.9375rem;
}

.search-item-price .old-price {
    font-size: 0.8125rem;
    color: #999;
    text-decoration: line-through;
}

.search-empty, .search-error {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.search-empty i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}
</style>
`;

// Добавить стили при загрузке
$('head').append(searchStyles);
