/**
 * Dashboard JS — Графики и интерактивность дашборда
 * B3.2: Chart.js для графика продаж
 */

// initSalesChart superseded by initOrdersChart — removed to avoid double Chart init on same canvas

function initSalesChart() {
    const canvas = document.getElementById('salesChart');
    if (!canvas || typeof Chart === 'undefined' || !window.chartData) return;

    const ctx = canvas.getContext('2d');
    const data = window.chartData;

    // Градиент для столбцов
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0, 128, 96, 0.8)');
    gradient.addColorStop(1, 'rgba(0, 128, 96, 0.2)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => {
                const days = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
                const date = new Date(d.date);
                return days[date.getDay()] || d.day;
            }),
            datasets: [{
                label: 'Заказы',
                data: data.map(d => d.orders),
                backgroundColor: gradient,
                borderColor: '#008060',
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
            }, {
                label: 'Выручка (BYN)',
                data: data.map(d => d.amount),
                type: 'line',
                borderColor: '#0078d4',
                backgroundColor: 'rgba(0, 120, 212, 0.1)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#0078d4',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 12,
                            family: "'Inter', -apple-system, sans-serif"
                        }
                    }
                },
                tooltip: {
                    backgroundColor: '#202223',
                    padding: 12,
                    titleFont: { size: 13 },
                    bodyFont: { size: 12 },
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.type === 'line') {
                                label += context.parsed.y.toFixed(2) + ' BYN';
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        color: '#6d7175'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#e1e3e5',
                        drawBorder: false
                    },
                    ticks: {
                        font: { size: 11 },
                        color: '#6d7175',
                        stepSize: 1
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        font: { size: 11 },
                        color: '#0078d4',
                        callback: function(value) {
                            return value >= 1000 ? (value/1000).toFixed(1) + 'K' : value;
                        }
                    }
                }
            }
        }
    });
}

/* -- dashboard/index.php (B3.2 chart, B3.4 CNY) -- */

/* B3.2 — Инициализация графика заказов с двумя линиями (30 дней + прошлый период) */
function initOrdersChart() {
    var ctx = document.getElementById('salesChart');
    if (!ctx || typeof Chart === 'undefined' || !window.chartData || !window.chartData.length) return;

    var existing = Chart.getChart(ctx);
    if (existing) existing.destroy();

    var labels     = window.chartData.map(function(d) { return d.day || d.date; });
    var curOrders  = window.chartData.map(function(d) { return d.orders || 0; });
    var prevOrders = window.chartData.map(function(d) { return d.prev_orders || 0; });

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Заказы (текущий период)',
                    data: curOrders,
                    borderColor: 'var(--admin-accent, #008060)',
                    backgroundColor: 'rgba(0,128,96,0.08)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                },
                {
                    label: 'Заказы (пред. период)',
                    data: prevOrders,
                    borderColor: '#94a3b8',
                    backgroundColor: 'rgba(148,163,184,0.05)',
                    fill: false,
                    tension: 0.35,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    borderWidth: 1.5,
                    borderDash: [5, 3],
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': ' + ctx.parsed.y + ' заказов';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { maxTicksLimit: 10, font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { font: { size: 11 }, precision: 0 }
                }
            }
        }
    });
}

/* B3.4 — Обновление курса CNY */
function updateCnyRate(btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation:spin 1s linear infinite"></i> Обновляем...';
    }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/dashboard/update-cny-rate', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('_csrf=' + encodeURIComponent(SH.getCsrfToken()));
    xhr.onload = function() {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обновить';
        }
        try {
            var data = JSON.parse(xhr.responseText);
            if (data.success && data.rate) {
                var rateEl = document.querySelector('.dash-cny-rate-main');
                if (rateEl) rateEl.textContent = '1 CNY = ' + parseFloat(data.rate).toFixed(4) + ' BYN';
                var metaEl = document.querySelector('.dash-cny-rate-meta');
                if (metaEl && data.updated_at) {
                    metaEl.innerHTML = 'Обновлено: ' + data.updated_at + ' &nbsp;·&nbsp; Источник: ' + (data.source || 'nbrb');
                }
                localStorage.removeItem('cny_rate');
                localStorage.removeItem('cny_rate_time');
            } else {
                alert('Ошибка обновления курса: ' + (data.message || 'неизвестная ошибка'));
            }
        } catch(e) { /* ignore */ }
    };
    xhr.onerror = function() {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обновить'; }
        alert('Сетевая ошибка при обновлении курса');
    };
}

document.addEventListener('DOMContentLoaded', function() {
    initOrdersChart();
});
