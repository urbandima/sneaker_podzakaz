<?php

/**
 * Inline Editing Component
 * 
 * Рекомендация #89: Inline Editing
 * 
 * Редактирование прямо в таблице без перехода на страницу
 */
?>

<script>
/**
 * Inline Editing System
 * 
 * Использование:
 * 1. Добавить data-inline-edit="true" к ячейке
 * 2. Добавить data-field="fieldname" 
 * 3. Добавить data-entity="Product" и data-id="123"
 */

class InlineEditor {
    constructor() {
        this.editing = null;
        this.originalValue = null;
        
        this.init();
    }
    
    init() {
        document.addEventListener('click', (e) => {
            const cell = e.target.closest('[data-inline-edit="true"]');
            if (!cell) {
                this.save();
                return;
            }
            
            if (this.editing && this.editing !== cell) {
                this.save();
            }
            
            this.edit(cell);
        });
        
        document.addEventListener('keydown', (e) => {
            if (!this.editing) return;
            
            if (e.key === 'Enter') {
                e.preventDefault();
                this.save();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.cancel();
            }
        });
    }
    
    edit(cell) {
        if (this.editing === cell) return;
        
        const value = cell.textContent.trim();
        this.originalValue = value;
        this.editing = cell;
        
        const type = cell.dataset.type || 'text';
        const width = cell.offsetWidth;
        const height = cell.offsetHeight;
        
        let input;
        
        switch (type) {
            case 'select':
                input = this.createSelect(cell.dataset.options, value);
                break;
            case 'number':
                input = document.createElement('input');
                input.type = 'number';
                input.value = value.replace(/[^\d.-]/g, '');
                input.step = cell.dataset.step || '0.01';
                break;
            case 'date':
                input = document.createElement('input');
                input.type = 'date';
                input.value = this.parseDate(value);
                break;
            default:
                input = document.createElement('input');
                input.type = 'text';
                input.value = value;
        }
        
        input.className = 'inline-edit-input';
        input.style.cssText = `
            width: ${width}px;
            min-height: ${height}px;
            padding: 4px 8px;
            border: 2px solid #3b82f6;
            border-radius: 4px;
            font-size: inherit;
            font-family: inherit;
            outline: none;
            background: white;
        `;
        
        cell.innerHTML = '';
        cell.appendChild(input);
        input.focus();
        input.select();
    }
    
    createSelect(options, value) {
        const select = document.createElement('select');
        select.className = 'inline-edit-input';
        
        const opts = JSON.parse(options || '{}');
        Object.entries(opts).forEach(([key, label]) => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = label;
            if (key === value) option.selected = true;
            select.appendChild(option);
        });
        
        return select;
    }
    
    parseDate(value) {
        // Преобразует "30.03.2026" в "2026-03-30"
        const parts = value.match(/(\d{2})\.(\d{2})\.(\d{4})/);
        if (parts) {
            return `${parts[3]}-${parts[2]}-${parts[1]}`;
        }
        return value;
    }
    
    formatDate(value) {
        // Преобразует "2026-03-30" в "30.03.2026"
        const parts = value.match(/(\d{4})-(\d{2})-(\d{2})/);
        if (parts) {
            return `${parts[3]}.${parts[2]}.${parts[1]}`;
        }
        return value;
    }
    
    async save() {
        if (!this.editing) return;
        
        const input = this.editing.querySelector('.inline-edit-input');
        if (!input) return;
        
        let value = input.value;
        const type = this.editing.dataset.type;
        
        if (type === 'date') {
            value = this.formatDate(value);
        } else if (type === 'number') {
            value = parseFloat(value).toFixed(2);
        }
        
        // Если значение не изменилось
        if (value === this.originalValue) {
            this.editing.textContent = value;
            this.editing = null;
            return;
        }
        
        // Показываем индикатор загрузки
        this.editing.innerHTML = '<span class="inline-loading">Сохранение...</span>';
        
        const entity = this.editing.dataset.entity;
        const id = this.editing.dataset.id;
        const field = this.editing.dataset.field;
        
        try {
            const response = await fetch('/admin/inline-edit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ entity, id, field, value }),
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.editing.textContent = value;
                this.editing.classList.add('inline-saved');
                setTimeout(() => this.editing.classList.remove('inline-saved'), 1000);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            this.editing.textContent = this.originalValue;
            alert('Ошибка сохранения: ' + error.message);
        }
        
        this.editing = null;
    }
    
    cancel() {
        if (!this.editing) return;
        this.editing.textContent = this.originalValue;
        this.editing = null;
    }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', () => {
    new InlineEditor();
});
</script>

<style>
[data-inline-edit="true"] {
    cursor: pointer;
    position: relative;
    transition: background 0.2s;
}

[data-inline-edit="true"]:hover {
    background: #f0f9ff;
}

[data-inline-edit="true"]:hover::after {
    content: '✎';
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #3b82f6;
    opacity: 0.5;
}

.inline-saved {
    animation: flash-success 1s ease;
}

@keyframes flash-success {
    0%, 100% { background: transparent; }
    50% { background: #dcfce7; }
}

.inline-loading {
    color: #6b7280;
    font-size: 12px;
}
</style>
