/**
 * Dashboard JavaScript
 * Charts, theme toggle, keyboard shortcuts
 */

(function() {
    'use strict';
    
    // === Sales Chart ===
    function initSalesChart() {
        const chartData = window.chartData || [];
        const labels = chartData.map(d => d.day);
        const orders = chartData.map(d => parseInt(d.orders) || 0);
        const amounts = chartData.map(d => parseFloat(d.amount) || 0);

        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded');
            return;
        }

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
        const textColor = isDark ? '#94a3b8' : '#64748b';

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Заказы',
                        data: orders,
                        backgroundColor: 'rgba(59,130,246,0.7)',
                        borderRadius: 6,
                        yAxisID: 'y',
                        order: 2
                    },
                    {
                        label: 'Выручка (BYN)',
                        data: amounts,
                        type: 'line',
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#10b981',
                        yAxisID: 'y1',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { 
                        position: 'top', 
                        labels: { 
                            color: textColor, 
                            usePointStyle: true, 
                            padding: 16, 
                            font: { size: 12 } 
                        } 
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false }, 
                        ticks: { color: textColor } 
                    },
                    y: { 
                        position: 'left', 
                        grid: { color: gridColor }, 
                        ticks: { color: textColor, stepSize: 1 }, 
                        title: { 
                            display: true, 
                            text: 'Заказы', 
                            color: textColor 
                        } 
                    },
                    y1: { 
                        position: 'right', 
                        grid: { display: false }, 
                        ticks: { color: textColor }, 
                        title: { 
                            display: true, 
                            text: 'BYN', 
                            color: textColor 
                        } 
                    }
                }
            }
        });
    }

    // === Theme Toggle ===
    function initThemeToggle() {
        const btn = document.getElementById('theme-toggle');
        const icon = document.getElementById('theme-icon');
        const html = document.documentElement;

        if (!btn || !icon) return;

        function applyTheme(t) {
            html.setAttribute('data-theme', t);
            localStorage.setItem('admin-theme', t);
            icon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }

        const saved = localStorage.getItem('admin-theme') || 'light';
        applyTheme(saved);

        btn.addEventListener('click', function() {
            applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        });
    }

    // === Keyboard shortcuts ===
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl+D - toggle theme
            if (e.ctrlKey && e.key === 'd') { 
                e.preventDefault(); 
                const themeToggle = document.getElementById('theme-toggle');
                if (themeToggle) themeToggle.click();
            }
            // Ctrl+K - focus first action card
            if (e.ctrlKey && e.key === 'k') { 
                e.preventDefault(); 
                const firstCard = document.querySelector('.dash-action-card');
                if (firstCard) firstCard.focus();
            }
            // Ctrl+R - reload page
            if (e.ctrlKey && e.key === 'r') { 
                e.preventDefault(); 
                location.reload(); 
            }
        });
    }

    // === Initialize everything ===
    document.addEventListener('DOMContentLoaded', function() {
        initSalesChart();
        initThemeToggle();
        initKeyboardShortcuts();
    });

})();
