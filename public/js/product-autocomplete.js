/**
 * ProductAutocomplete — shared autocomplete widget for item rows.
 *
 * Usage:
 *   ProductAutocomplete.init({
 *     items        : window._sellables,   // array of {key, label, description, [unit_price|unit_cost]}
 *     priceField   : 'unit_price',        // which property to read as the auto-fill price
 *     wrapperAttr  : 'data-sale-items',   // attribute on the row container
 *     rowClass     : 'sale-item-row',     // class on each row
 *     addBtnAttr   : 'data-add-sale-item',
 *     removeBtnAttr: 'data-remove-sale-item',
 *     newRowHtml   : (index) => '...',    // function that returns HTML for a new empty row
 *   });
 */
window.ProductAutocomplete = (() => {

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function filter(items, query) {
        const q = query.toLowerCase().trim();
        if (!q) return [];
        return items.filter(p =>
            p.label.toLowerCase().includes(q) ||
            (p.description || '').toLowerCase().includes(q)
        ).slice(0, 20);
    }

    function renderDropdown(dropdown, results, onSelect) {
        dropdown.innerHTML = '';

        if (!results.length) {
            dropdown.innerHTML = '<div class="px-3 py-2 text-muted small">No products found.</div>';
            dropdown.classList.remove('d-none');
            return;
        }

        results.forEach(item => {
            const el = document.createElement('div');
            el.className = 'px-3 py-2 border-bottom';
            el.style.cssText = 'cursor:pointer; transition:background .1s;';
            el.innerHTML = `<div class="fw-medium small">${escapeHtml(item.label)}</div>`
                + `<div class="text-muted" style="font-size:.75rem;">${escapeHtml(item.description || '')}</div>`;

            el.addEventListener('mouseenter', () => el.style.background = 'var(--tblr-bg-surface-secondary, #f6f8fb)');
            el.addEventListener('mouseleave', () => el.style.background = '');
            el.addEventListener('mousedown', e => {
                e.preventDefault();
                onSelect(item);
            });

            dropdown.appendChild(el);
        });

        dropdown.classList.remove('d-none');
    }

    function closeDropdown(dropdown) {
        dropdown.classList.add('d-none');
        dropdown.innerHTML = '';
    }

    function bindAutocomplete(row, items, priceField) {
        const searchInput = row.querySelector('[data-item-search]');
        const keyInput    = row.querySelector('[data-item-key]');
        const dropdown    = row.querySelector('[data-item-dropdown]');
        const hint        = row.querySelector('[data-item-hint]');
        const priceInput  = row.querySelector('[data-item-price]');
        if (!searchInput || !keyInput || !dropdown) return;

        const defaultHint = 'Type to search — default price will be filled automatically.';

        function selectItem(item) {
            searchInput.value = item.label;
            keyInput.value    = item.key;
            if (hint) hint.textContent = item.description || defaultHint;
            if (priceInput && (!priceInput.value || parseFloat(priceInput.value) === 0)) {
                priceInput.value = item[priceField] ?? 0;
            }
            closeDropdown(dropdown);
            searchInput.dataset.selected = '1';
        }

        searchInput.addEventListener('input', () => {
            searchInput.dataset.selected = '';
            keyInput.value = '';
            if (hint) hint.textContent = defaultHint;
            const results = filter(items, searchInput.value);
            if (searchInput.value.trim()) {
                renderDropdown(dropdown, results, selectItem);
            } else {
                closeDropdown(dropdown);
            }
        });

        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim() && !searchInput.dataset.selected) {
                renderDropdown(dropdown, filter(items, searchInput.value), selectItem);
            }
        });

        searchInput.addEventListener('blur', () => {
            setTimeout(() => closeDropdown(dropdown), 150);
            if (!searchInput.dataset.selected && !keyInput.value) {
                searchInput.value = '';
                if (hint) hint.textContent = defaultHint;
            }
        });

        searchInput.addEventListener('keydown', e => {
            const els = [...dropdown.querySelectorAll('[data-active], div')].filter(el => el.parentElement === dropdown);
            const active = dropdown.querySelector('[data-ac-active]');
            let idx = active ? els.indexOf(active) : -1;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (active) { active.removeAttribute('data-ac-active'); active.style.background = ''; }
                idx = Math.min(idx + 1, els.length - 1);
                if (els[idx]) { els[idx].setAttribute('data-ac-active', '1'); els[idx].style.background = 'var(--tblr-bg-surface-secondary, #f6f8fb)'; }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (active) { active.removeAttribute('data-ac-active'); active.style.background = ''; }
                idx = Math.max(idx - 1, 0);
                if (els[idx]) { els[idx].setAttribute('data-ac-active', '1'); els[idx].style.background = 'var(--tblr-bg-surface-secondary, #f6f8fb)'; }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (active) active.dispatchEvent(new MouseEvent('mousedown'));
            } else if (e.key === 'Escape') {
                closeDropdown(dropdown);
                searchInput.blur();
            }
        });
    }

    function init({ items, priceField, wrapperAttr, rowClass, addBtnAttr, removeBtnAttr, newRowHtml }) {
        const wrapper = document.querySelector(`[${wrapperAttr}]`);
        const addBtn  = document.querySelector(`[${addBtnAttr}]`);
        if (!wrapper || !addBtn) return;

        function bindRow(row) {
            row.querySelector(`[${removeBtnAttr}]`)?.addEventListener('click', () => {
                if (wrapper.querySelectorAll(`.${rowClass}`).length === 1) return;
                row.remove();
                reindex();
            });
            bindAutocomplete(row, items, priceField);
        }

        function reindex() {
            wrapper.querySelectorAll(`.${rowClass}`).forEach((row, i) => {
                row.querySelectorAll('[name]').forEach(input => {
                    input.name = input.name.replace(/items\[\d+\]/, `items[${i}]`);
                });
            });
        }

        wrapper.querySelectorAll(`.${rowClass}`).forEach(bindRow);

        addBtn.addEventListener('click', () => {
            const index = wrapper.querySelectorAll(`.${rowClass}`).length;
            const fragment = document.createRange().createContextualFragment(newRowHtml(index));
            wrapper.appendChild(fragment);
            bindRow(wrapper.lastElementChild);
            wrapper.lastElementChild.querySelector('[data-item-search]')?.focus();
        });
    }

    return { init };
})();
