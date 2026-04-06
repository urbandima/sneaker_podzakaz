/**
 * Admin Settings JS
 * =================
 * JS вынесенный из inline <script> блоков:
 * - settings/index.php
 * - settings/statuses.php
 * - tariff/index.php
 * - review/index.php
 * - statistics/index.php
 * - user/index.php
 * - import/index.php
 * - coupon/index.php
 * - plugin/index.php
 * - seo/* views
 * - loyalty/* views
 */

/* === SETTINGS === */

/* -- settings/index.php -- */
document.addEventListener('DOMContentLoaded', function() {
    // URLs are read from data attributes on <body> or a dedicated config element
    // Fallback to hardcoded paths when data attrs are absent
    function getSettingsUrls() {
        var el = document.getElementById('admin-settings-config');
        return {
            saveCompany:  el ? el.dataset.saveCompanyUrl  : '/admin/settings/save-company',
            testTelegram: el ? el.dataset.testTelegramUrl : '/admin/settings/test-telegram',
            testMoysklad: el ? el.dataset.testMoyskladUrl : '/admin/settings/test-moysklad',
            testAmocrm:   el ? el.dataset.testAmocrmUrl   : '/admin/settings/test-amocrm',
            save:         el ? el.dataset.saveUrl         : '/admin/settings/save',
        };
    }

    window.saveCompany = function(btn) {
        var urls = getSettingsUrls();
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение...';
        var data = {
            name:         document.getElementById('co_name').value,
            unp:          document.getElementById('co_unp').value,
            address:      document.getElementById('co_address').value,
            phone:        document.getElementById('co_phone').value,
            email:        document.getElementById('co_email').value,
            work_time:    document.getElementById('co_work_time').value,
            bank_details: document.getElementById('co_bank_details').value,
        };
        fetch(urls.saveCompany, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
            body: JSON.stringify(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btn.disabled = false;
            if (res.success) {
                btn.innerHTML = '<i class="bi bi-check2-circle"></i> Сохранено';
                btn.classList.replace('admin-btn-primary', 'admin-btn-success');
                setTimeout(function() { btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить'; btn.classList.replace('admin-btn-success', 'admin-btn-primary'); }, 2500);
            } else {
                btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ошибка';
                alert(res.message || 'Ошибка сохранения');
                setTimeout(function() { btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить'; }, 2000);
            }
        })
        .catch(function() { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить'; alert('Ошибка соединения'); });
    };

    window.testTelegram = function(btn) {
        var urls = getSettingsUrls();
        var resultEl = document.getElementById('telegram-test-result');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправка...';
        fetch(urls.testTelegram, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> Тест отправки';
            if (resultEl) {
                resultEl.style.display = 'block';
                resultEl.style.background = data.success ? '#d1fae5' : '#fee2e2';
                resultEl.style.color = data.success ? '#065f46' : '#991b1b';
                resultEl.textContent = data.message || (data.success ? 'Сообщение отправлено' : 'Ошибка');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> Тест отправки';
            if (resultEl) {
                resultEl.style.display = 'block';
                resultEl.style.background = '#fee2e2';
                resultEl.style.color = '#991b1b';
                resultEl.textContent = 'Ошибка соединения';
            }
        });
    };

    window.testIntegration = function(type, btn) {
        var urls = getSettingsUrls();
        var resultEl = document.getElementById(type + '-test-result');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Проверка...';
        var url = type === 'moysklad' ? urls.testMoysklad : urls.testAmocrm;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plug"></i> Проверить подключение';
            if (resultEl) {
                resultEl.style.display = 'block';
                resultEl.style.background = data.success ? '#d1fae5' : '#fee2e2';
                resultEl.style.color = data.success ? '#065f46' : '#991b1b';
                resultEl.textContent = data.message || (data.success ? 'Подключение успешно' : 'Ошибка подключения');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plug"></i> Проверить подключение';
            if (resultEl) {
                resultEl.style.display = 'block';
                resultEl.style.background = '#fee2e2';
                resultEl.style.color = '#991b1b';
                resultEl.textContent = 'Ошибка соединения';
            }
        });
    };

    window.saveSettings = function(btn) {
        var urls = getSettingsUrls();
        var card = btn.closest('.admin-card');
        var inputs = card.querySelectorAll('[data-setting]');
        var data = {};

        inputs.forEach(function(input) {
            var settingKey = input.dataset.setting;
            var parts = settingKey.split('.');
            var section = parts[0];
            var key = parts[1];
            if (!data[section]) {
                data[section] = {};
            }
            data[section][key] = input.value;
        });

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение...';

        fetch(urls.save, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(response) {
            btn.disabled = false;
            if (response.success) {
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранено';
                btn.classList.remove('admin-btn-primary');
                btn.classList.add('admin-btn-success');
                setTimeout(function() {
                    btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить';
                    btn.classList.remove('admin-btn-success');
                    btn.classList.add('admin-btn-primary');
                }, 2000);
            } else {
                btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ошибка';
                alert(response.message || 'Ошибка сохранения');
                setTimeout(function() {
                    btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить';
                }, 2000);
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить';
            alert('Ошибка соединения');
        });
    };
});

/* -- settings/statuses.php -- */
document.addEventListener('DOMContentLoaded', function() {
    window.saveStatuses = function() {
        var statuses = [];
        document.querySelectorAll('.status-config-item').forEach(function(item, index) {
            statuses.push({
                key: item.dataset.status,
                label: item.querySelector('.status-label').value,
                color: item.querySelector('.status-color').value,
                active: item.querySelector('.status-active').checked
            });
        });

        fetch('/admin/settings/save-statuses', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
            },
            body: JSON.stringify({statuses: statuses})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                alert('✅ Статусы сохранены');
                location.reload();
            } else {
                alert('❌ ' + (data.message || 'Ошибка сохранения'));
            }
        });
    };

    window.addStatus = function() {
        var container = document.getElementById('statuses-list');
        if (!container) return;
        var index = container.children.length;

        var newStatus = document.createElement('div');
        newStatus.className = 'status-config-item';
        newStatus.dataset.status = 'new_' + index;
        newStatus.innerHTML = '<div style="display:flex;align-items:center;gap:16px">' +
            '<div class="drag-handle" style="cursor:move;color:var(--admin-text-secondary)">' +
                '<i class="bi bi-grip-vertical"></i>' +
            '</div>' +
            '<div style="flex:1">' +
                '<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">' +
                    '<input type="text" class="admin-form-input status-label" value="" placeholder="Название" style="max-width:200px">' +
                    '<select class="admin-form-select status-color" style="max-width:150px">' +
                        '<option value="info">Синий</option>' +
                        '<option value="success">Зеленый</option>' +
                        '<option value="warning">Желтый</option>' +
                        '<option value="danger">Красный</option>' +
                        '<option value="primary">Фиолетовый</option>' +
                        '<option value="secondary">Серый</option>' +
                    '</select>' +
                    '<label style="display:flex;align-items:center;gap:8px;margin:0">' +
                        '<input type="checkbox" class="status-active" checked>' +
                        '<span style="font-size:14px">Активен</span>' +
                    '</label>' +
                '</div>' +
            '</div>' +
        '</div>';

        container.appendChild(newStatus);
        newStatus.draggable = true;
    };

    // Drag & Drop for status sorting
    var statusDraggedElement = null;
    var list = document.getElementById('statuses-list');

    if (list) {
        list.addEventListener('dragstart', function(e) {
            if (e.target.classList.contains('status-config-item')) {
                statusDraggedElement = e.target;
                e.target.style.opacity = '0.5';
            }
        });

        list.addEventListener('dragend', function(e) {
            if (e.target.classList.contains('status-config-item')) {
                e.target.style.opacity = '1';
            }
        });

        list.addEventListener('dragover', function(e) {
            e.preventDefault();
            var afterElement = getStatusDragAfterElement(list, e.clientY);
            if (afterElement == null) {
                list.appendChild(statusDraggedElement);
            } else {
                list.insertBefore(statusDraggedElement, afterElement);
            }
        });

        document.querySelectorAll('.status-config-item').forEach(function(item) {
            item.draggable = true;
        });
    }

    function getStatusDragAfterElement(container, y) {
        var draggableElements = Array.from(container.querySelectorAll('.status-config-item:not(.dragging)'));
        return draggableElements.reduce(function(closest, child) {
            var box = child.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
});

/* -- settings/email-templates.php -- */
document.addEventListener('DOMContentLoaded', function() {
    window.saveTemplate = function(key) {
        fetch('/admin/settings/save-email-template', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': (document.querySelector('meta[name=csrf-token]') || {}).content || ''
            },
            body: JSON.stringify({
                key: key,
                subject: document.getElementById('subject_' + key).value,
                body: document.getElementById('body_' + key).value
            })
        }).then(function(r) { return r.json(); }).then(function(d) {
            alert(d.success ? 'Сохранено' : 'Ошибка: ' + (d.message || ''));
        });
    };

    window.testEmail = function(key) {
        fetch('/admin/settings/test-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': (document.querySelector('meta[name=csrf-token]') || {}).content || ''
            },
            body: JSON.stringify({key: key})
        }).then(function(r) { return r.json(); }).then(function(d) {
            alert(d.message || 'Отправлено');
        });
    };
});

/* === TARIFF === */

/* -- tariff/index.php -- */
document.addEventListener('DOMContentLoaded', function() {
    window.calculateCost = function() {
        var calcEl = document.getElementById('tariff-calculator');
        var calculateUrl = calcEl ? calcEl.dataset.calculateUrl : '/admin/tariff/calculate';
        var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

        var tariffId  = document.getElementById('calcTariff').value;
        var priceCny  = document.getElementById('calcPrice').value;
        var weightKg  = document.getElementById('calcWeight').value;
        var note      = document.getElementById('calcNote').value;

        fetch(calculateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken
            },
            body: 'tariff_id=' + tariffId + '&price_cny=' + priceCny + '&weight_kg=' + weightKg + '&note=' + encodeURIComponent(note) + '&save_history=1'
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var result    = document.getElementById('calcResult');
                var breakdown = document.getElementById('calcBreakdown');

                var html = '';
                for (var label in data.calculation.breakdown) {
                    if (data.calculation.breakdown.hasOwnProperty(label)) {
                        html += '<div class="result-row">' +
                            '<span class="result-label">' + label + '</span>' +
                            '<span class="result-value">' + data.calculation.breakdown[label] + '</span>' +
                        '</div>';
                    }
                }

                breakdown.innerHTML = html;
                result.classList.add('show');

                if (data.history_id) {
                    showTariffNotification('Расчет сохранен в историю', 'success');
                }
            }
        });
    };

    function showTariffNotification(message, type) {
        type = type || 'info';
        var notification = document.createElement('div');
        notification.className = 'admin-toast' + (type === 'success' ? ' admin-toast--success' : '');
        notification.innerHTML = '<i class="bi bi-check-circle"></i> ' + message;
        document.body.appendChild(notification);

        setTimeout(function() { notification.classList.add('show'); }, 100);
        setTimeout(function() {
            notification.classList.remove('show');
            setTimeout(function() { notification.remove(); }, 300);
        }, 3000);
    }
});

/* === REVIEW === */

/* -- review/index.php -- */
document.addEventListener('DOMContentLoaded', function() {
    var reviewContainer = document.getElementById('review-page-container');
    var reviewReplyUrl = reviewContainer ? reviewContainer.dataset.replyUrl : '/admin/review/respond';
    var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

    window.toggleReplyForm = function(reviewId) {
        var form = document.getElementById('reply-form-' + reviewId);
        if (!form) return;
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        if (form.style.display === 'block') {
            var ta = document.getElementById('reply-text-' + reviewId);
            if (ta) ta.focus();
        }
    };

    window.submitReply = function(reviewId) {
        var textarea = document.getElementById('reply-text-' + reviewId);
        var resultEl = document.getElementById('reply-result-' + reviewId);
        if (!textarea || !textarea.value.trim()) {
            if (resultEl) {
                resultEl.className = 'reply-result error';
                resultEl.textContent = 'Введите текст ответа';
                resultEl.style.display = 'block';
            }
            return;
        }
        var btn = textarea.closest('.reply-form-inner').querySelector('.btn-action.success');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправка...'; }

        fetch(reviewReplyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: 'id=' + encodeURIComponent(reviewId) + '&response=' + encodeURIComponent(textarea.value.trim())
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Отправить ответ'; }
            if (resultEl) {
                resultEl.style.display = 'block';
                if (data.success) {
                    resultEl.className = 'reply-result success';
                    resultEl.textContent = 'Ответ сохранён';
                    setTimeout(function() {
                        var replyForm = document.getElementById('reply-form-' + reviewId);
                        if (replyForm) replyForm.style.display = 'none';
                    }, 1500);
                } else {
                    resultEl.className = 'reply-result error';
                    resultEl.textContent = data.message || 'Ошибка сохранения';
                }
            }
        })
        .catch(function() {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Отправить ответ'; }
            if (resultEl) {
                resultEl.style.display = 'block';
                resultEl.className = 'reply-result error';
                resultEl.textContent = 'Ошибка соединения';
            }
        });
    };
});

/* === STATISTICS === */
/* NOTE: statistics/index.php chart initialization uses PHP-injected JSON data
 * (salesChart, statusStats) and is kept inline in the view — only the <style>
 * block has been extracted to admin-pages.css. */

/* === USER === */

/* -- user/index.php -- */
document.addEventListener('DOMContentLoaded', function() {
    var filtersCard = document.getElementById('usersFiltersCard');
    var toggleBtn   = document.getElementById('usersFiltersToggle');

    var toggleFilters = function() {
        var isOpen = filtersCard.getAttribute('data-open') === 'true';
        filtersCard.setAttribute('data-open', String(!isOpen));
        filtersCard.classList.toggle('is-collapsed', isOpen);
        filtersCard.querySelector('.admin-card-body').style.display = isOpen ? 'none' : 'block';
    };

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleFilters);
    }

    // Hide filter body when no active filters (state is encoded in data attribute by PHP)
    if (filtersCard) {
        var activeFiltersCount = parseInt(filtersCard.dataset.activeFiltersCount || '0', 10);
        if (activeFiltersCount === 0) {
            var body = filtersCard.querySelector('.admin-card-body');
            if (body) body.style.display = 'none';
        } else {
            filtersCard.setAttribute('data-open', 'true');
        }
    }
});

function editUser(userId) {
    window.location.href = '/admin/user/edit?id=' + userId;
}

function toggleUserStatus(userId) {
    if (confirm('Изменить статус пользователя?')) {
        fetch('/admin/user/' + userId + '/toggle', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Ошибка при изменении статуса');
            }
        })
        .catch(function() { alert('Ошибка сети'); });
    }
}

function deleteUser(userId, username) {
    if (confirm('Удалить пользователя «' + username + '»? Это действие нельзя отменить.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/user/delete?id=' + userId;

        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_csrf';
            csrfInput.value = csrfToken.content;
            form.appendChild(csrfInput);
        }

        document.body.appendChild(form);
        form.submit();
    }
}

function bulkExport() {
    window.location.href = '/admin/user/export';
}

function resetPassword(userId, username) {
    if (!confirm('Сбросить пароль пользователя «' + username + '»? Новый пароль будет показан один раз.')) return;
    fetch('/admin/user/reset-password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        },
        body: JSON.stringify({ id: userId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            alert('Новый пароль для «' + username + '»:\n\n' + data.password + '\n\nСкопируйте пароль — он больше не будет показан.');
        } else {
            alert('Ошибка: ' + (data.message || 'Не удалось сбросить пароль'));
        }
    })
    .catch(function() { alert('Ошибка сети'); });
}

function toggleBlock(userId, isCurrentlyActive) {
    var action = isCurrentlyActive ? 'заблокировать' : 'разблокировать';
    if (!confirm('Вы уверены, что хотите ' + action + ' этого пользователя?')) return;
    fetch('/admin/user/toggle-block', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        },
        body: JSON.stringify({ id: userId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Ошибка: ' + (data.message || 'Не удалось изменить статус'));
        }
    })
    .catch(function() { alert('Ошибка сети'); });
}

/* -- sidebar-menu/index.php -- */
document.addEventListener('DOMContentLoaded', function() {
    var tbody = document.getElementById('sortable-list');
    if (!tbody) return;

    var sortUrl = tbody.dataset.sortUrl || '/admin/sidebar-menu/sort';
    var draggedRow = null;

    tbody.querySelectorAll('tr').forEach(function(row) {
        row.draggable = true;

        row.addEventListener('dragstart', function() {
            draggedRow = this;
            this.style.opacity = '0.5';
        });

        row.addEventListener('dragend', function() {
            this.style.opacity = '';
            draggedRow = null;

            var rows = tbody.querySelectorAll('tr');
            var items = Array.from(rows).map(function(r) { return r.dataset.id; });

            fetch(sortUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: 'items=' + JSON.stringify(items)
            });
        });

        row.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (this === draggedRow) return;

            var rect = this.getBoundingClientRect();
            var midpoint = rect.top + rect.height / 2;

            if (e.clientY < midpoint) {
                tbody.insertBefore(draggedRow, this);
            } else {
                tbody.insertBefore(draggedRow, this.nextSibling);
            }
        });
    });
});

/* -- sidebar-menu/_form.php -- */
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('sidebarmenuitem-type');

    window.toggleTypeFields = function(type) {
        var urlFields  = document.getElementById('url-fields');
        var imageField = document.getElementById('image-field');
        var configEl   = document.getElementById('sidebar-form-config');

        var typeDivider = configEl ? configEl.dataset.typeDivider : 'divider';
        var typeHeader  = configEl ? configEl.dataset.typeHeader  : 'header';
        var typeBanner  = configEl ? configEl.dataset.typeBanner  : 'banner';

        if (urlFields) {
            urlFields.style.display = (type === typeDivider || type === typeHeader) ? 'none' : 'flex';
        }
        if (imageField) {
            imageField.style.display = (type === typeBanner) ? 'flex' : 'none';
        }
    };

    if (typeSelect) {
        toggleTypeFields(typeSelect.value);
    }
});

/* === IMPORT === */

/* === COUPON & LOYALTY === */

/* === SEO === */
