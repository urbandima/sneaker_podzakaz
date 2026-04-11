document.addEventListener('click', (e) => {
  if (!e.target.closest('.nav-item')) {
    document.querySelectorAll('.mega-menu').forEach(menu => {
      menu.style.opacity = '0';
      menu.style.visibility = 'hidden';
    });
  }
});

const headerSearch = document.getElementById('headerSearch');
const searchBox = document.querySelector('.search-box');

headerSearch?.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    const query = e.target.value.trim();
    if (query.length > 0) {
      window.location.href = '/catalog?search=' + encodeURIComponent(query);
    }
  }
});

searchBox?.querySelector('.bi-search')?.addEventListener('click', () => {
  const query = headerSearch?.value?.trim() ?? '';
  if (query.length > 0) {
    window.location.href = '/catalog?search=' + encodeURIComponent(query);
  }
});

const brandsNavBtn = document.getElementById('brandsNavBtn');
let brandsLoaded = false;

brandsNavBtn?.addEventListener('mouseenter', () => {
  if (!brandsLoaded) {
    loadBrands();
    brandsLoaded = true;
  }
});

function loadBrands() {
  fetch('/catalog/get-brands')
    .then(r => r.json())
    .then(brands => {
      const grid = document.getElementById('brandsGrid');
      if (!grid) return;

      if (!brands || brands.length === 0) {
        grid.innerHTML = '<p style="text-align:center;padding:2rem;color:#666">Бренды не найдены</p>';
        return;
      }

      grid.innerHTML = brands.map(brand => `
        <a href="/catalog/brand/${brand.slug}" class="brand-link">
          <span>${brand.name}</span>
          <span class="count">${brand.products_count}</span>
        </a>
      `).join('');
    })
    .catch(err => {
      const grid = document.getElementById('brandsGrid');
      if (grid) {
        grid.innerHTML = '<p style="text-align:center;padding:2rem;color:#ef4444">Ошибка загрузки</p>';
      }
    });
}
