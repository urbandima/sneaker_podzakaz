/**
 * Dashboard JS — Графики и интерактивность дашборда
 * B3.2: Chart.js для графика продаж
 */

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация графика продаж
    initSalesChart();
});

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
