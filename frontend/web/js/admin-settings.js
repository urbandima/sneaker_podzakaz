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

/* === SIZE GRID pages === */

/* -- size-grid/index.php -- */
document.addEventListener('DOMContentLoaded', function() {
    window.applyBrandTemplate = function(btn) {
        var brand = btn.dataset.brand;
        var sizes = JSON.parse(btn.dataset.sizes);
        document.getElementById('templateModalTitle').textContent = 'Шаблон: ' + brand;
        var tbody = document.getElementById('templateSizesBody');
        tbody.innerHTML = '';
        sizes.forEach(function(s) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>US ' + s.us + '</td><td>EU ' + s.eu + '</td>';
            tbody.appendChild(tr);
        });
        document.getElementById('templatePreviewModal').style.display = 'flex';
    };

    window.closeTemplateModal = function() {
        document.getElementById('templatePreviewModal').style.display = 'none';
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') window.closeTemplateModal();
    });

    var modal = document.getElementById('templatePreviewModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) window.closeTemplateModal();
        });
    }
});

/* === IMPORT pages === */

/* -- import/index.php -- */
document.addEventListener('DOMContentLoaded', function() {
    // Running task progress polling
    function updateRunningTasks() {
        document.querySelectorAll('.running-task').forEach(function(row) {
            var taskId = row.dataset.taskId;
            if (!taskId) return;
            fetch('/admin/import-ajax/progress?taskId=' + taskId)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && !data.is_running) {
                        location.reload();
                    }
                });
        });
    }

    if (document.querySelectorAll('.running-task').length > 0) {
        setInterval(updateRunningTasks, 3000);
    }

    // Stop task button
    document.querySelectorAll('.btn-stop-task').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var taskId = this.dataset.taskId;
            if (!confirm('Остановить задачу #' + taskId + '?')) return;
            fetch('/admin/import-ajax/stop', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                },
                body: 'taskId=' + taskId
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error);
                }
            });
        });
    });
});

/* -- import/upload.php -- */
function downloadTemplate(format) {
    var content, filename, mimeType;

    if (format === 'json') {
        content = JSON.stringify([
            {
                name: 'Nike Air Max 90',
                sku: 'NM-90-001',
                description: 'Классические кроссовки Nike',
                price: 299.99,
                brand_name: 'Nike',
                is_active: true
            },
            {
                name: 'Adidas Ultraboost',
                sku: 'AD-UB-001',
                description: 'Беговые кроссовки Adidas',
                price: 349.99,
                brand_name: 'Adidas',
                is_active: true
            }
        ], null, 2);
        filename = 'import-template.json';
        mimeType = 'application/json';
    } else if (format === 'csv') {
        content = 'name;sku;description;price;brand_name;is_active\n' +
            '"Nike Air Max 90";"NM-90-001";"Классические кроссовки Nike";299.99;"Nike";1\n' +
            '"Adidas Ultraboost";"AD-UB-001";"Беговые кроссовки Adidas";349.99;"Adidas";1';
        filename = 'import-template.csv';
        mimeType = 'text/csv';
    }

    var blob = new Blob([content], {type: mimeType});
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}

/* -- layouts/_import_notifications.php (jQuery-based, kept compatible) -- */
document.addEventListener('DOMContentLoaded', function() {
    // Notification read — jQuery version kept inline; vanilla fallback here
    document.querySelectorAll('.notification-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.dataset.id;
            if (typeof $ !== 'undefined') {
                $.post('/admin/import-ajax/mark-notification-read', {id: id}, function(data) {
                    if (data.success) location.reload();
                });
            }
        });
    });

    var markAllBtn = document.getElementById('mark-all-read');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof $ !== 'undefined') {
                $.post('/admin/import-ajax/mark-all-notifications-read', function(data) {
                    if (data.success) location.reload();
                });
            }
        });
    }
});

/* === COUPON pages === */

/* -- coupon/analytics.php -- */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-period').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-period').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var period = this.dataset.period;
            fetch('/admin/coupon/analytics?period=' + period)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    updateCouponDashboard(data);
                });
        });
    });

    function updateCouponDashboard(data) {
        // KPI and chart update placeholder — extend as needed
    }
});

/* -- coupon/_form.php and coupon/create.php -- */
function generateCouponCode() {
    var btn = document.querySelector('[data-generate-url]');
    var generateUrl = btn ? btn.dataset.generateUrl : '/admin/coupon/generate-code';

    fetch(generateUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        },
        body: 'prefix=&length=8'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var codeInput = document.querySelector('#coupon-code');
        if (codeInput) codeInput.value = data.code;
    });
}

function updateDiscountFields(type) {
    var valueField = document.querySelector('#value-field');
    var valueInput = document.querySelector('#coupon-value');

    if (!valueField) return;

    // free_shipping is the static string used in create.php;
    // coupon/_form.php uses TYPE_FREE_SHIPPING constant rendered server-side.
    // The data-free-shipping attribute carries that value.
    var freeShippingValue = (document.getElementById('coupon-type') || {}).dataset
        ? (document.getElementById('coupon-type').dataset.freeShippingValue || 'free_shipping')
        : 'free_shipping';

    if (type === freeShippingValue || type === 'free_shipping') {
        valueField.style.display = 'none';
        if (valueInput) valueInput.value = 0;
    } else {
        valueField.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.querySelector('#coupon-type');
    if (typeSelect && typeSelect.value) {
        updateDiscountFields(typeSelect.value);
    }
});

/* === LOYALTY pages === */

/* -- loyalty/index.php -- */
function saveLoyaltySettings() {
    var btn = document.getElementById('saveLoyaltyBtn');
    var saveUrl = (btn && btn.dataset.saveUrl) ? btn.dataset.saveUrl : '/admin/settings/save-loyalty';

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение...';

    var data = {
        loyalty: {
            enabled:       document.getElementById('loyaltyEnabled').checked ? 1 : 0,
            public_page:   document.getElementById('publicPageEnabled').checked ? 1 : 0,
            bronze_min:    document.getElementById('level_bronze_min').value,
            bronze_mult:   document.getElementById('level_bronze_mult').value,
            silver_min:    document.getElementById('level_silver_min').value,
            silver_mult:   document.getElementById('level_silver_mult').value,
            gold_min:      document.getElementById('level_gold_min').value,
            gold_mult:     document.getElementById('level_gold_mult').value,
            platinum_min:  document.getElementById('level_platinum_min').value,
            platinum_mult: document.getElementById('level_platinum_mult').value
        }
    };

    fetch(saveUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        },
        body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        btn.disabled = false;
        if (res.success) {
            btn.innerHTML = '<i class="bi bi-check2-circle"></i> Сохранено!';
            btn.classList.replace('admin-btn-primary', 'admin-btn-success');
            setTimeout(function() {
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить настройки';
                btn.classList.replace('admin-btn-success', 'admin-btn-primary');
            }, 2500);
        } else {
            btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ошибка';
            alert(res.message || 'Ошибка сохранения');
            setTimeout(function() { btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить настройки'; }, 2000);
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить настройки';
        alert('Ошибка соединения');
    });
}

/* === RETURN pages === */

/* -- return/view.php -- */
function markStepDone(returnId, stepKey, btn) {
    var updateUrl = (btn && btn.dataset.updateUrl) ? btn.dataset.updateUrl : '/admin/return/update-step';

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';

    fetch(updateUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        },
        body: JSON.stringify({id: returnId, step: stepKey})
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            var stepEl = document.getElementById('step-' + stepKey);
            stepEl.classList.add('checklist-step--done');
            var iconEl = stepEl.querySelector('.checklist-step-icon i');
            if (iconEl) iconEl.className = 'bi bi-check-circle-fill';
            var numEl = stepEl.querySelector('.checklist-step-number');
            if (numEl) numEl.style.background = '#22c55e';
            var actionEl = stepEl.querySelector('.checklist-step-action');
            actionEl.innerHTML = '<span class="admin-badge admin-badge-success"><i class="bi bi-check2-circle"></i> Готово</span>';
            var contentEl = stepEl.querySelector('.checklist-step-content');
            var meta = document.createElement('div');
            meta.className = 'checklist-step-meta';
            meta.innerHTML = '<i class="bi bi-calendar3"></i> ' + (res.date || 'только что') +
                (res.author ? ' &bull; <i class="bi bi-person"></i> ' + res.author : '');
            contentEl.appendChild(meta);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2"></i> Выполнено';
            alert(res.message || 'Ошибка обновления шага');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2"></i> Выполнено';
        alert('Ошибка соединения');
    });
}

/* === PLUGIN pages === */

/* -- plugin/index.php -- */
function togglePlugin(id, action) {
    if (!confirm('Вы уверены?')) return;

    var pluginPage = document.getElementById('plugin-page');
    var toggleUrl = (pluginPage && pluginPage.dataset.toggleUrl) ? pluginPage.dataset.toggleUrl : '/admin/plugin/toggle';

    fetch(toggleUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        },
        body: 'id=' + id + '&action=' + action
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

/* === SEO pages === */

/* -- seo/bulk-meta.php -- */
function updateProductMeta(id, field, value) {
    fetch('/admin/seo/update-product-meta', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': (typeof yii !== 'undefined' ? yii.getCsrfToken() : ((document.querySelector('meta[name="csrf-token"]') || {}).content || ''))
        },
        body: 'id=' + id + '&field=' + field + '&value=' + encodeURIComponent(value)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showAdminNotification('Сохранено!', 'success');
        } else {
            showAdminNotification('Ошибка сохранения', 'error');
        }
    });
}

function applyTemplate(type, template) {
    if (type === 'title') {
        document.getElementById('meta_title_pattern').value = template;
    } else if (type === 'desc') {
        document.getElementById('meta_description_pattern').value = template;
    } else if (type === 'keywords') {
        document.getElementById('meta_keywords_pattern').value = template;
    }
    showAdminNotification('Шаблон применён!', 'success');
}

/* -- seo/alt-texts.php -- */
function updateImageAlt(id, value) {
    fetch('/admin/seo/update-image-alt', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': (typeof yii !== 'undefined' ? yii.getCsrfToken() : ((document.querySelector('meta[name="csrf-token"]') || {}).content || ''))
        },
        body: 'id=' + id + '&alt_text=' + encodeURIComponent(value)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showAdminNotification('ALT текст сохранён!', 'success');
        } else {
            showAdminNotification('Ошибка сохранения', 'error');
        }
    });
}

/* -- shared SEO notification helper -- */
function showAdminNotification(message, type) {
    var div = document.createElement('div');
    div.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
    div.textContent = message;
    div.style.position = 'fixed';
    div.style.top = '20px';
    div.style.right = '20px';
    div.style.zIndex = '9999';
    div.style.padding = '10px 20px';
    document.body.appendChild(div);
    setTimeout(function() { div.remove(); }, 3000);
}

/* === ANALYTICS pages === */

/* -- analytics/index.php -- */
document.addEventListener('DOMContentLoaded', function() {
    window.refreshRfm = function(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Рассчёт...';
        var msgEl = document.getElementById('rfm-msg');
        var rfmUrl = (btn && btn.dataset.rfmUrl) ? btn.dataset.rfmUrl : '/admin/analytics/rfm';

        fetch(rfmUrl, {
            method: 'GET',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Рассчитать RFM';
            if (data.success && data.segments) {
                var colors = {Champion: '#10b981', Loyal: '#3b82f6', 'At Risk': '#f59e0b', Lost: '#ef4444', New: '#8b5cf6'};
                var tbody = document.querySelector('#rfm-table tbody');
                var exportBaseUrl = (btn && btn.dataset.exportRfmUrl) ? btn.dataset.exportRfmUrl : '/admin/analytics/export-rfm';
                if (tbody) {
                    tbody.innerHTML = '';
                    data.segments.forEach(function(seg) {
                        var c = colors[seg.segment] || '#64748b';
                        var tr = document.createElement('tr');
                        var exportUrl = exportBaseUrl + '?segment=' + encodeURIComponent(seg.segment);
                        tr.innerHTML = '<td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + c + ';margin-right:.4rem;"></span><strong>' + seg.segment + '</strong></td>'
                            + '<td style="text-align:right;font-weight:700;">' + seg.count + '</td>'
                            + '<td style="text-align:right;">' + parseFloat(seg.avg_monetary).toFixed(2) + '</td>'
                            + '<td style="text-align:center;"><a href="' + exportUrl + '" class="admin-btn admin-btn-secondary" style="font-size:.75rem;padding:.3rem .7rem;"><i class="bi bi-download"></i> CSV</a></td>';
                        tbody.appendChild(tr);
                    });
                }
                if (msgEl) {
                    msgEl.style.display = 'block';
                    msgEl.style.background = '#d1fae5';
                    msgEl.style.color = '#065f46';
                    msgEl.textContent = 'RFM пересчитан успешно';
                    setTimeout(function() { msgEl.style.display = 'none'; }, 3000);
                }
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Рассчитать RFM';
            if (msgEl) {
                msgEl.style.display = 'block';
                msgEl.style.background = '#fee2e2';
                msgEl.style.color = '#991b1b';
                msgEl.textContent = 'Ошибка при расчёте RFM';
            }
        });
    };
});

/* -- analytics/rfm.php -- */
function showSegmentDetails(segment) {
    alert('Детали сегмента "' + segment + '"\n\nЗдесь будет список клиентов этого сегмента с возможностью экспорта и массовых действий.');
}

function exportAtRisk() {
    alert('Экспорт списка покупателей в статусе риска\n\nCSV файл будет содержать: имя, email, LTV, класс LTV, последний заказ, дней без заказа, уровень риска.');
}

function sendEmail(email) {
    alert('Отправка email клиенту: ' + email + '\n\nОткроется форма персонального письма для реактивации клиента.');
}

function sendSms(email) {
    alert('Отправка SMS клиенту: ' + email + '\n\nОткроется форма SMS-рассылки с предложением.');
}

function createOffer(email) {
    alert('Создание персонального предложения для: ' + email + '\n\nМожно создать персональный купон или скидку для этого клиента.');
}

/* === MARKETING pages === */

/* -- marketing/index.php -- */
function sendReminder(cartId, btn) {
    if (!confirm('Отправить напоминание клиенту?')) return;
    var reminderUrl = (btn && btn.dataset.reminderUrl) ? btn.dataset.reminderUrl : '/admin/marketing/send-reminder';

    fetch(reminderUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        },
        body: 'cart_id=' + cartId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        alert(data.message);
        if (data.success) location.reload();
    });
}

function sendBulkReminders(btn) {
    if (!confirm('Отправить напоминания всем клиентам с брошенными корзинами?')) return;
    var bulkUrl = (btn && btn.dataset.bulkUrl) ? btn.dataset.bulkUrl : '/admin/marketing/send-bulk-reminders';

    fetch(bulkUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        alert(data.message);
    });
}

/* === FRONTEND MAIN LAYOUT (layouts/main.php) === */

/* -- layouts/main.php -- */
function toggleMobileMenu() {
    var menu = document.getElementById('mobileMenu');
    if (!menu) return;
    menu.classList.toggle('open');
    document.body.style.overflow = menu.classList.contains('open') ? 'hidden' : '';
}

function openSearch() {
    var modal = document.getElementById('searchModal');
    if (!modal) return;
    modal.classList.add('open');
    var input = document.getElementById('searchInput');
    if (input) input.focus();
    document.body.style.overflow = 'hidden';
}

function closeSearch() {
    var modal = document.getElementById('searchModal');
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

function handleSearch(event) {
    if (event.key === 'Escape') {
        closeSearch();
        return;
    }

    var query = event.target.value.trim();
    var resultsContainer = document.getElementById('searchResults');
    if (!resultsContainer) return;

    if (query.length < 2) {
        resultsContainer.innerHTML = '';
        return;
    }

    fetch('/api/v1/products/search?q=' + encodeURIComponent(query))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.length > 0) {
                resultsContainer.innerHTML = data.map(function(product) {
                    return '<a href="/product/' + product.id + '" class="search-result-item" onclick="closeSearch()">' +
                        '<img src="' + (product.image || '/images/placeholder.png') + '" alt="' + product.name + '">' +
                        '<div class="search-result-info">' +
                            '<div class="search-result-name">' + product.name + '</div>' +
                            '<div class="search-result-price">' + product.price + ' BYN</div>' +
                        '</div>' +
                    '</a>';
                }).join('');
            } else {
                resultsContainer.innerHTML = '<div class="search-no-results">Ничего не найдено</div>';
            }
        });
}

document.addEventListener('DOMContentLoaded', function() {
    var searchModal = document.getElementById('searchModal');
    if (searchModal) {
        searchModal.addEventListener('click', function(e) {
            if (e.target.id === 'searchModal') closeSearch();
        });
    }
});

/* === ADMIN LOGIN === */

/* -- admin/login.php -- */
document.addEventListener('DOMContentLoaded', function() {
    var firstInput = document.querySelector('#login-form input');
    if (firstInput) firstInput.focus();

    var passwordToggle = document.querySelector('.password-toggle');
    var passwordInput  = document.querySelector('#loginform-password');

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            var icon = this.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    }
});

/* === ORDER pages (create form) === */

/* -- order/create.php and poizon/order/create.php -- */
/* Note: orderItemIndex is initialized from data-initial-index attribute on #orderItemsBuilder */
document.addEventListener('DOMContentLoaded', function() {
    var orderItemsBuilder = document.getElementById('orderItemsBuilder');
    if (!orderItemsBuilder) return;
    var addItemBtn = document.getElementById('addItemBtn');
    var orderItemIndex = parseInt(orderItemsBuilder.dataset.initialIndex || '1', 10);

    function updateRemoveButtons() {
        var rows = orderItemsBuilder.querySelectorAll('.order-item-row');
        rows.forEach(function(row) {
            var btn = row.querySelector('.remove-item');
            if (btn) btn.disabled = rows.length === 1;
        });
        var countEl = document.getElementById('itemCountDisplay');
        if (countEl) countEl.textContent = rows.length;
        recalcTotal();
    }

    function recalcTotal() {
        var total = 0;
        orderItemsBuilder.querySelectorAll('.order-item-row').forEach(function(row) {
            var qty = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
            var price = parseFloat(row.querySelector('input[name*="[price]"]').value) || 0;
            total += qty * price;
        });
        var display = document.getElementById('orderTotalDisplay');
        if (display) display.textContent = total ? total.toFixed(2) + ' BYN' : '—';
    }

    orderItemsBuilder.addEventListener('input', function(e) {
        if (e.target.matches('input[type="number"]')) recalcTotal();
    });

    orderItemsBuilder.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            e.target.closest('.order-item-row').remove();
            updateRemoveButtons();
        }
    });

    if (addItemBtn) {
        addItemBtn.addEventListener('click', function() {
            var tpl = document.createElement('div');
            tpl.className = 'order-item-row';
            tpl.dataset.index = orderItemIndex;
            tpl.innerHTML =
                '<div class="form-field"><label>Название</label>' +
                '<input type="text" name="OrderItem[' + orderItemIndex + '][product_name]" placeholder="Название товара"></div>' +
                '<div class="form-field"><label>Кол-во</label>' +
                '<input type="number" name="OrderItem[' + orderItemIndex + '][quantity]" value="1" min="1"></div>' +
                '<div class="form-field"><label>Цена, BYN</label>' +
                '<input type="number" step="0.01" name="OrderItem[' + orderItemIndex + '][price]" placeholder="0.00"></div>' +
                '<button type="button" class="remove-item">×</button>';
            orderItemsBuilder.appendChild(tpl);
            orderItemIndex++;
            updateRemoveButtons();
        });
    }

    updateRemoveButtons();
    recalcTotal();
});

/* === POIZON module pages === */

/* -- poizon/customer/index.php -- */
function toggleStatus(id) {
    if (!confirm('Изменить статус покупателя?')) return;

    fetch('/admin/customer/' + id + '/toggle-status', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

/* -- poizon/order/view-new.php -- */
function copyLink() {
    var input = document.getElementById('publicLink');
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(function() {
        showSaveIndicator('Ссылка скопирована');
    });
}

function showSaveIndicator(message) {
    var indicator = document.getElementById('saveIndicator');
    if (!indicator) return;
    indicator.innerHTML = '<i class="bi bi-check-circle"></i> ' + (message || 'Сохранено');
    indicator.classList.add('show');
    setTimeout(function() { indicator.classList.remove('show'); }, 2000);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-flag').forEach(function(flag) {
        flag.addEventListener('change', function() {
            var field = this.dataset.field;
            var value = this.checked ? 1 : 0;
            var config = document.getElementById('order-view-config');
            var updateUrl = config ? (config.dataset.updateFieldUrl || '/admin/order/update-field') : '/admin/order/update-field';
            var orderId = config ? config.dataset.orderId : '';
            var csrfToken = config ? config.dataset.csrfToken : ((document.querySelector('meta[name="csrf-token"]') || {}).content || '');
            var csrfParam = config ? config.dataset.csrfParam : '_csrf';
            var formData = new FormData();
            formData.append('field', field);
            formData.append('value', value);
            formData.append(csrfParam, csrfToken);
            fetch(updateUrl + (orderId ? '?id=' + orderId : ''), {
                method: 'POST',
                body: formData
            }).then(function(response) { return response.json(); })
              .then(function(data) {
                  if (data.success) showSaveIndicator('Флаг сохранён');
              });
        });
    });
});

/* -- poizon/tariff/index.php -- */
function calculateCost() {
    var tariffId = document.getElementById('calcTariff').value;
    var priceCny = document.getElementById('calcPrice').value;
    var weightKg = document.getElementById('calcWeight').value;
    var note = document.getElementById('calcNote').value;
    var config = document.getElementById('tariff-calc-config');
    var calcUrl = config ? (config.dataset.calcUrl || '/admin/poizon/tariff/calculate') : '/admin/poizon/tariff/calculate';
    var csrfToken = config ? config.dataset.csrfToken : ((document.querySelector('meta[name="csrf-token"]') || {}).content || '');

    fetch(calcUrl, {
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
            var result = document.getElementById('calcResult');
            var breakdown = document.getElementById('calcBreakdown');
            var html = '';
            for (var label in data.calculation.breakdown) {
                if (data.calculation.breakdown.hasOwnProperty(label)) {
                    html += '<div class="result-row"><span class="result-label">' + label + '</span><span class="result-value">' + data.calculation.breakdown[label] + '</span></div>';
                }
            }
            breakdown.innerHTML = html;
            result.classList.add('show');
            if (data.history_id) {
                showTariffNotification('Расчет сохранен в историю', 'success');
            }
        }
    });
}

function showTariffNotification(message, type) {
    var notification = document.createElement('div');
    notification.className = 'admin-toast ' + (type === 'success' ? 'admin-toast--success' : '');
    notification.innerHTML = '<i class="bi bi-check-circle"></i> ' + message;
    document.body.appendChild(notification);
    setTimeout(function() { notification.classList.add('show'); }, 100);
    setTimeout(function() {
        notification.classList.remove('show');
        setTimeout(function() { notification.remove(); }, 300);
    }, 3000);
}

/* === POS module pages === */

/* -- pos/index.php -- */
/* Note: URLs read from data-* attributes on #pos-config element */
document.addEventListener('DOMContentLoaded', function() {
    var cfg = document.getElementById('pos-config');
    if (!cfg) return;

    var URLS = {
        getProducts:    cfg.dataset.getProductsUrl    || '/admin/pos/get-products',
        getCustomers:   cfg.dataset.getCustomersUrl   || '/admin/pos/get-customers',
        createCustomer: cfg.dataset.createCustomerUrl || '/admin/pos/create-customer',
        getOrders:      cfg.dataset.getOrdersUrl      || '/admin/pos/get-customer-orders',
        completeOrder:  cfg.dataset.completeOrderUrl  || '/admin/pos/complete-order',
        applyDiscount:  cfg.dataset.applyDiscountUrl  || '/admin/pos/apply-discount',
        quickOrder:     cfg.dataset.quickOrderUrl     || '/admin/pos/quick-order'
    };

    var cart = [], customer = null, payment = 'cash', products = [], customers = [],
        selectedSize = null, discount = {type:'percent', value:0, amount:0};

    function getCsrf() {
        return (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    }

    function load() {
        fetch(URLS.getProducts).then(function(r){return r.json();}).then(function(d){products=d.products||[];show();});
    }
    function show(list) {
        var g = document.getElementById('products'), l = list || products;
        if (!l.length) { g.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--admin-text-secondary)">Нет товаров</div>'; return; }
        g.innerHTML = l.map(function(p){ return '<div class="pos-product ' + (p.total_stock===0?'out':'') + '" onclick="' + (p.total_stock>0 ? 'posAdd('+p.id+')' : '') + '"><img src="'+p.image+'"><div class="name">'+p.name+'</div><div class="price">'+p.price+' BYN</div><div class="stock">'+(p.total_stock>0?p.total_stock+' шт':'Нет')+'</div></div>'; }).join('');
    }
    function filter() {
        var cat=document.getElementById('cat-filter').value, brand=document.getElementById('brand-filter').value, search=document.getElementById('search').value.toLowerCase();
        var filtered=products;
        if(cat) filtered=filtered.filter(function(p){return p.category_id==cat;});
        if(brand) filtered=filtered.filter(function(p){return p.brand_id==brand;});
        if(search) filtered=filtered.filter(function(p){return p.name.toLowerCase().includes(search)||p.article.toLowerCase().includes(search);});
        if(selectedSize&&selectedSize!=='all') filtered=filtered.filter(function(p){return p.sizes&&p.sizes.some(function(s){return s.size==selectedSize&&s.stock>0;});});
        show(filtered);
    }
    function toggleSize(size) {
        selectedSize=size==='all'?null:size;
        document.querySelectorAll('.pos-size-btn').forEach(function(b){b.classList.toggle('active',b.textContent===size||(!selectedSize&&b.textContent==='Все'));});
        filter();
    }
    function switchTab(tab) { document.querySelectorAll('.pos-tab').forEach(function(t){t.classList.toggle('active',t.textContent.toLowerCase().includes(tab));});}
    function posAdd(id) {
        var p=products.find(function(x){return x.id===id;});
        if(!p||!p.sizes) return;
        var avail=p.sizes.filter(function(s){return s.stock>0;});
        if(!avail.length){alert('Нет в наличии');return;}
        var s;
        if(avail.length===1) s=avail[0];
        else {
            var size=prompt('Размеры: '+avail.map(function(z){return z.size+'('+z.stock+')';}).join(', ')+'\nВведите размер:');
            if(!size) return;
            s=avail.find(function(x){return x.size==size;});
            if(!s){alert('Размер не найден');return;}
        }
        var e=cart.find(function(i){return i.product_id===p.id&&i.size_id===s.id;});
        if(e){if(e.quantity<s.stock)e.quantity++;else{alert('Лимит');return;}}
        else cart.push({product_id:p.id,size_id:s.id,name:p.name,size:s.size,price:s.price||p.price,image:p.image,quantity:1,max_stock:s.stock});
        upd();
    }
    function upd() {
        var cont=document.getElementById('cart'),countEl=document.getElementById('count'),subEl=document.getElementById('subtotal'),totEl=document.getElementById('total'),btn=document.getElementById('checkout-btn');
        if(!cart.length){cont.innerHTML='<div class="pos-cart-empty"><i class="bi bi-cart" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>Корзина пуста</div>';countEl.textContent='0';subEl.textContent='0 BYN';totEl.textContent='0 BYN';btn.disabled=true;discount={type:'percent',value:0,amount:0};document.getElementById('discount-row').style.display='none';return;}
        var sub=0,c=0;
        cont.innerHTML=cart.map(function(it,i){var itt=it.price*it.quantity;sub+=itt;c+=it.quantity;return '<div class="pos-cart-item"><img src="'+it.image+'"><div class="pos-cart-item-info"><div class="pos-cart-item-name">'+it.name+'</div><div class="pos-cart-item-meta">'+it.size+'</div></div><div class="pos-cart-item-qty"><button class="pos-qty-btn" onclick="posChg('+i+',-1)">-</button><span>'+it.quantity+'</span><button class="pos-qty-btn" onclick="posChg('+i+',1)" '+(it.quantity>=it.max_stock?'disabled':'')+'>+</button></div><div class="pos-cart-item-total">'+Math.round(itt)+'</div><button class="pos-qty-btn" onclick="posRem('+i+')" style="color:var(--admin-danger)"><i class="bi bi-trash"></i></button></div>';}).join('');
        var tot=sub-discount.amount;countEl.textContent=c;subEl.textContent=sub.toFixed(2)+' BYN';totEl.textContent=tot.toFixed(2)+' BYN';
        if(discount.amount>0){document.getElementById('discount-row').style.display='flex';document.getElementById('discount-amt').textContent='-'+discount.amount.toFixed(2)+' BYN';}
        else document.getElementById('discount-row').style.display='none';
        btn.disabled=false;
    }
    function posChg(i,d){var it=cart[i],n=it.quantity+d;if(n<=0)posRem(i);else if(n<=it.max_stock){it.quantity=n;upd();}}
    function posRem(i){cart.splice(i,1);upd();}
    function applyDiscount(){
        var val=parseFloat(document.getElementById('discount-val').value)||0,type=document.getElementById('discount-type').value,sub=cart.reduce(function(a,i){return a+i.price*i.quantity;},0);
        if(val<=0) return;
        fetch(URLS.applyDiscount,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrf()},body:JSON.stringify({type:type,value:val,subtotal:sub})}).then(function(r){return r.json();}).then(function(d){if(d.success){discount={type:type,value:val,amount:d.discount_amount};upd();}else alert(d.message);});
    }
    function selectPayment(m){payment=m;document.querySelectorAll('.pos-payment-btn').forEach(function(b){b.classList.toggle('active',b.dataset.method===m);});document.getElementById('split-payment').classList.toggle('active',m==='split');}
    function openCustomer(){fetch(URLS.getCustomers).then(function(r){return r.json();}).then(function(d){customers=d.customers||[];renderCustomers();document.getElementById('customer-modal').classList.add('active');});}
    function closeCustomer(){document.getElementById('customer-modal').classList.remove('active');}
    function renderCustomers(list){var l=list||customers,cont=document.getElementById('cust-list');if(!l.length){cont.innerHTML='<div style="text-align:center;padding:2rem;color:var(--admin-text-secondary)">Нет клиентов</div>';return;}cont.innerHTML=l.map(function(c){return '<div class="pos-customer-row" onclick="posSelectCustomer('+c.id+')"><div><div style="font-weight:600">'+c.name+'</div><div style="font-size:0.8rem;color:var(--admin-text-secondary)">'+(c.phone||'')+'</div></div><button class="admin-btn admin-btn-primary admin-btn-sm">Выбрать</button></div>';}).join('');}
    function searchCustomers(){var q=document.getElementById('cust-search').value.toLowerCase();renderCustomers(customers.filter(function(c){return c.name.toLowerCase().includes(q)||(c.phone&&c.phone.includes(q));}));}
    function posSelectCustomer(id){customer=customers.find(function(c){return c.id===id;});if(customer){document.getElementById('cust-info').innerHTML='<div class="pos-customer-name">'+customer.name+'</div><div class="pos-customer-phone">'+(customer.phone||'')+'</div>';}closeCustomer();}
    function createCustomer(){var name=prompt('Имя клиента:');if(!name) return;var phone=prompt('Телефон:');fetch(URLS.createCustomer,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrf()},body:JSON.stringify({name:name,phone:phone})}).then(function(r){return r.json();}).then(function(d){if(d.success){customer=d.customer;posSelectCustomer(customer.id);customers.push(customer);}else alert(d.message);});}
    function openOrders(){if(!customer){alert('Выберите клиента');return;}fetch(URLS.getOrders+'?customer_id='+customer.id).then(function(r){return r.json();}).then(function(d){if(d.success){renderOrders(d.orders);document.getElementById('orders-modal').classList.add('active');}else alert(d.message||'Ошибка загрузки заказов');});}
    function closeOrders(){document.getElementById('orders-modal').classList.remove('active');}
    function renderOrders(orders){var cont=document.getElementById('orders-list');if(!orders.length){cont.innerHTML='<div style="text-align:center;padding:2rem;color:var(--admin-text-secondary)">Нет готовых заказов</div>';return;}cont.innerHTML=orders.map(function(o){return '<div class="pos-order-row"><div class="pos-order-header"><div class="pos-order-number">#'+o.order_number+'</div><span class="pos-order-status '+o.status+'">'+o.status+'</span></div><div class="pos-order-items">'+o.items_count+' товаров</div><div class="pos-order-total">'+o.total_amount+' BYN</div><div class="pos-actions"><button class="admin-btn admin-btn-success admin-btn-sm" onclick="posCompleteOrder('+o.id+')"><i class="bi bi-check-circle"></i> Выдать</button></div></div>';}).join('');}
    function posCompleteOrder(id){if(!confirm('Выдать заказ клиенту?')) return;fetch(URLS.completeOrder,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':getCsrf()},body:'order_id='+id}).then(function(r){return r.json();}).then(function(d){if(d.success){alert('Заказ #'+d.order_number+' выдан');closeOrders();}else alert(d.message);});}
    function openScan(){document.getElementById('scan-modal').classList.add('active');setTimeout(function(){document.getElementById('barcode').focus();},100);}
    function closeScan(){document.getElementById('scan-modal').classList.remove('active');}
    function scan(){var code=document.getElementById('barcode').value.trim();if(!code) return;var p=products.find(function(x){return x.article===code||x.sku===code;});if(p){posAdd(p.id);closeScan();document.getElementById('barcode').value='';}else alert('Товар не найден');}
    function checkout(){
        if(!cart.length) return;
        var paymentData={method:payment};
        if(payment==='split'){var cash=parseFloat(document.getElementById('cash-amt').value)||0,card=parseFloat(document.getElementById('card-amt').value)||0,total=cart.reduce(function(a,i){return a+i.price*i.quantity;},0)-discount.amount;if(cash+card<total){alert('Сумма оплаты меньше итога');return;}paymentData={method:'split',cash_amount:cash,card_amount:card};}
        var data={items:cart,customer_id:customer?customer.id:null,customer_name:customer?customer.name:'Гость',customer_phone:customer?customer.phone:'',payment_method:payment,payment_data:paymentData,discount:discount.amount>0?discount:null};
        fetch(URLS.quickOrder,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrf()},body:JSON.stringify(data)}).then(function(r){return r.json();}).then(function(d){if(d.success){alert('Заказ #'+d.order_number+' создан!\nСумма: '+d.total+' BYN');cart=[];customer=null;discount={type:'percent',value:0,amount:0};document.getElementById('cust-info').innerHTML='<div class="pos-customer-name">Гость</div><div class="pos-customer-phone"></div>';document.getElementById('discount-val').value='';upd();}else alert(d.message);});
    }

    // Expose functions to window for inline onclick handlers
    window.posAdd = posAdd;
    window.posChg = posChg;
    window.posRem = posRem;
    window.applyDiscount = applyDiscount;
    window.selectPayment = selectPayment;
    window.openCustomer = openCustomer;
    window.closeCustomer = closeCustomer;
    window.searchCustomers = searchCustomers;
    window.posSelectCustomer = posSelectCustomer;
    window.createCustomer = createCustomer;
    window.openOrders = openOrders;
    window.closeOrders = closeOrders;
    window.posCompleteOrder = posCompleteOrder;
    window.openScan = openScan;
    window.closeScan = closeScan;
    window.scan = scan;
    window.checkout = checkout;
    window.toggleSize = toggleSize;
    window.switchTab = switchTab;
    window.filter = filter;

    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') { e.preventDefault(); document.getElementById('search').focus(); }
        if (e.key === 'Escape') document.querySelectorAll('.pos-modal').forEach(function(m){ m.classList.remove('active'); });
    });

    load();
});


/* -- settings/integrations.php -- */
document.addEventListener('DOMContentLoaded', function() {
function testAmoCRM() {
    alert('Тестирование подключения к AmoCRM...');
    // TODO: AJAX запрос к /admin/amo-crm/test
}

function saveAmoCRM() {
    alert('Сохранение настроек AmoCRM...');
    // TODO: AJAX запрос к /admin/amo-crm/save
}

function testMoySklad() {
    alert('Тестирование подключения к МойСклад...');
    // TODO: AJAX запрос к /admin/moy-sklad/test
}

function saveMoySklad() {
    alert('Сохранение настроек МойСклад...');
    // TODO: AJAX запрос к /admin/moy-sklad/save
}

function testTelegram() {
    const token = document.getElementById('telegram-token').value;
    if (!token) {
        alert('Введите Bot Token');
        return;
    }
    
    fetch('/admin/telegram-bot/test', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({token})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Тестовое сообщение отправлено!');
        } else {
            alert('❌ Ошибка: ' + data.message);
        }
    });
}

function saveTelegram() {
    alert('Сохранение настроек Telegram...');
    // TODO: AJAX запрос к /admin/telegram-bot/save
}

function updateCNYRate() {
    const btn = document.activeElement;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Обновление...';
    
    fetch('/admin/exchange-rate/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(data => {
        if (data.success) {
            document.getElementById('cny-rate').textContent = data.rate;
            document.getElementById('cny-updated').textContent = new Date().toLocaleString('ru-RU');
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Обновлено!';
            btn.classList.remove('admin-btn-primary');
            btn.classList.add('admin-btn-success');
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.classList.add('admin-btn-primary');
                btn.classList.remove('admin-btn-success');
            }, 2000);
        } else {
            throw new Error(data.message || 'Ошибка обновления');
        }
    })
    .catch(err => {
        console.error('Ошибка:', err);
        alert('❌ ' + (err.message || 'Ошибка обновления курса'));
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// Загрузка текущего курса при открытии страницы
fetch('/admin/exchange-rate/current')
    .then(r => r.json())
    .then(data => {
        if (data.rate) {
            document.getElementById('cny-rate').textContent = data.rate;
            document.getElementById('cny-updated').textContent = data.updated_at;
        }
    });
});


/* -- settings/telegram.php -- */
document.addEventListener('DOMContentLoaded', function() {
function saveSettings(btn) {
    const settings = {};
    document.querySelectorAll('[data-setting]').forEach(el => {
        const key = el.getAttribute('data-setting');
        settings[key] = el.type === 'checkbox' ? el.checked : el.value;
    });
    
    fetch('/admin/settings/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(settings)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Настройки сохранены');
        } else {
            alert('Ошибка: ' + data.message);
        }
    });
}

function testTelegramConnection() {
    alert('Проверка подключения...\n\nФункция будет реализована после настройки Bot Token');
}

function sendTestNotification() {
    alert('Тестовое уведомление отправлено!\n\n(Функция будет активна после настройки бота)');
}
});


/* -- poizon/user/index.php -- */
document.addEventListener('DOMContentLoaded', () => {
    const filtersCard = document.getElementById('usersFiltersCard');
    const toggleBtn = document.getElementById('usersFiltersToggle');

    const toggleFilters = () => {
        const isOpen = filtersCard.getAttribute('data-open') === 'true';
        filtersCard.setAttribute('data-open', String(!isOpen));
        filtersCard.classList.toggle('is-collapsed', isOpen);
        filtersCard.querySelector('.admin-card-body').style.display = isOpen ? 'none' : 'block';
    };

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleFilters);
    }

    // Скрываем тело фильтра, если нет активных критериев
    if (Object.keys(JSON.parse(document.getElementById('users-filters-card')?.dataset.activeFilters || '{}')).length === 0) {
        filtersCard.querySelector('.admin-card-body').style.display = 'none';
    } else {
        filtersCard.setAttribute('data-open', 'true');
    }
});

function editUser(userId) {
    window.location.href = `/admin/user/edit?id=${userId}`;
}

function toggleUserStatus(userId) {
    if (confirm('Изменить статус пользователя?')) {
        fetch(`/admin/user/${userId}/toggle`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
            .then(response => response.json())
            .then(data => data.success ? window.location.reload() : alert(data.message || 'Ошибка при изменении статуса'))
            .catch(() => alert('Ошибка сети'));
    }
}

function deleteUser(userId, username) {
    if (confirm(`Удалить пользователя «${username}»? Это действие нельзя отменить.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/user/delete?id=${userId}`;

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
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


/* === POS TERMINAL === */

/* -- pos/index.php -- */
document.addEventListener('DOMContentLoaded', function() {

});

/* === IMPORT LOGS === */

/* -- import/logs.php -- */
/* Note: taskId read from #logs-config data-task-id attribute */
document.addEventListener('DOMContentLoaded', function() {
    var logsConfig = document.getElementById('logs-config');
    if (!logsConfig) return;

    var taskId = logsConfig.dataset.taskId;
    if (!taskId) return;

    var lastLogId = 0;

    function updateLogs() {
        if (typeof $ !== 'undefined') {
            $.get('/admin/import-ajax/logs', {taskId: taskId, lastId: lastLogId}, function(data) {
                if (data.success && data.logs.length > 0) {
                    lastLogId = data.last_id;
                    data.logs.forEach(function(log) {
                        var colorMap = {created: 'success', updated: 'info', duplicate: 'warning', error: 'danger'};
                        var color = colorMap[log.action] || 'secondary';
                        var row = '<tr>'
                            + '<td><small>' + log.time + '</small></td>'
                            + '<td><span class="badge bg-' + color + '">' + log.action_label + '</span></td>'
                            + '<td>' + (log.sku || '') + '</td>'
                            + '<td>' + log.product_name + '</td>'
                            + '<td>' + log.message + '</td>'
                            + '</tr>';
                        $('table tbody').prepend(row);
                    });
                }
                if (data.task_status !== 'running') {
                    location.reload();
                }
            });
        }
    }

    setInterval(updateLogs, 2000);
});

/* === IMPORT SOURCE === */

/* -- import/source.php -- */
document.addEventListener('DOMContentLoaded', function() {
    var proxyToggle = document.getElementById('importsource-proxy_enabled');
    if (!proxyToggle) return;

    proxyToggle.addEventListener('change', function() {
        var proxySettings = document.getElementById('proxy-settings');
        if (!proxySettings) return;
        if (typeof $ !== 'undefined') {
            if (this.checked) { $(proxySettings).slideDown(); }
            else { $(proxySettings).slideUp(); }
        } else {
            proxySettings.style.display = this.checked ? '' : 'none';
        }
    });
});

/* === IMPORT STATS === */

/* -- import/stats.php -- */
/* Note: Chart data read from canvas#dailyChart data-* attributes */
document.addEventListener('DOMContentLoaded', function() {
    var canvas = document.getElementById('dailyChart');
    if (!canvas) return;

    var labels   = JSON.parse(canvas.dataset.labels   || '[]');
    var imported = JSON.parse(canvas.dataset.imported || '[]');
    var updated  = JSON.parse(canvas.dataset.updated  || '[]');
    var errors   = JSON.parse(canvas.dataset.errors   || '[]');

    if (typeof Chart === 'undefined') return;

    new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Импортировано', data: imported, backgroundColor: 'rgba(25, 135, 84, 0.7)' },
                { label: 'Обновлено',     data: updated,  backgroundColor: 'rgba(13, 202, 240, 0.7)' },
                { label: 'Ошибок',        data: errors,   backgroundColor: 'rgba(220, 53, 69, 0.7)' }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true }
            }
        }
    });
});
