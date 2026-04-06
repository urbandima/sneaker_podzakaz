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
    var updateUrl = (document.getElementById('return-config') || {}).dataset
        ? (document.getElementById('return-config').dataset.updateStepUrl || '/admin/return/update-step')
        : '/admin/return/update-step';

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

    fetch('/admin/plugin/toggle', {
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
        var rfmUrl = (document.getElementById('analytics-config') || {}).dataset
            ? (document.getElementById('analytics-config').dataset.rfmUrl || '/admin/analytics/rfm')
            : '/admin/analytics/rfm';

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
                var exportBaseUrl = (document.getElementById('analytics-config') || {}).dataset
                    ? (document.getElementById('analytics-config').dataset.exportRfmUrl || '/admin/analytics/export-rfm')
                    : '/admin/analytics/export-rfm';
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
function sendReminder(cartId) {
    if (!confirm('Отправить напоминание клиенту?')) return;
    var reminderUrl = (document.getElementById('marketing-config') || {}).dataset
        ? (document.getElementById('marketing-config').dataset.sendReminderUrl || '/admin/marketing/send-reminder')
        : '/admin/marketing/send-reminder';

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

function sendBulkReminders() {
    if (!confirm('Отправить напоминания всем клиентам с брошенными корзинами?')) return;
    var bulkUrl = (document.getElementById('marketing-config') || {}).dataset
        ? (document.getElementById('marketing-config').dataset.sendBulkUrl || '/admin/marketing/send-bulk-reminders')
        : '/admin/marketing/send-bulk-reminders';

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
