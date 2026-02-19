const INSTANCES = new WeakMap();

function normalizeOptions(options, mapOption) {
    return (options || [])
        .map((item) => mapOption(item))
        .filter((opt) => opt && typeof opt.value === 'string')
        .map((opt) => ({
            value: opt.value,
            label: typeof opt.label === 'string' ? opt.label : opt.value,
            search: String(opt.search || `${opt.value} ${opt.label}`).toLowerCase()
        }));
}

function isOpen(instance) {
    return instance.menu.style.display !== 'none';
}

export const SearchableDropdown = {
    attach(input, config = {}) {
        if (!input) return;

        const existing = INSTANCES.get(input);
        if (existing) {
            existing.config = { ...existing.config, ...config };
            this.refresh(input);
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-dropdown-wrap';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const menu = document.createElement('div');
        menu.className = 'searchable-dropdown-menu';
        menu.style.display = 'none';
        wrapper.appendChild(menu);

        const instance = {
            input,
            menu,
            wrapper,
            config: {
                freeform: true,
                mapOption: (item) => {
                    if (typeof item === 'string') return { value: item, label: item, search: item };
                    return {
                        value: String(item?.value || ''),
                        label: String(item?.label || item?.value || ''),
                        search: String(item?.search || '')
                    };
                },
                getOptions: () => [],
                onSelect: null,
                ...config
            },
            highlightedIndex: -1,
            options: []
        };

        const closeMenu = () => {
            menu.style.display = 'none';
            instance.highlightedIndex = -1;
        };

        const openMenu = () => {
            menu.style.display = 'block';
        };

        const render = (query = '') => {
            const all = normalizeOptions(instance.config.getOptions(), instance.config.mapOption);
            instance.options = all;

            const q = String(query || '').trim().toLowerCase();
            const filtered = q ? all.filter((opt) => opt.search.includes(q)) : all;

            menu.innerHTML = '';
            if (filtered.length === 0) {
                closeMenu();
                return;
            }

            filtered.forEach((opt, idx) => {
                const item = document.createElement('div');
                item.className = 'searchable-dropdown-item';
                item.setAttribute('role', 'option');
                item.tabIndex = -1;
                item.textContent = opt.label;
                item.dataset.value = opt.value;
                item.dataset.index = String(idx);
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    input.value = opt.value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    if (typeof instance.config.onSelect === 'function') {
                        instance.config.onSelect(opt.value, opt);
                    }
                    closeMenu();
                });
                menu.appendChild(item);
            });

            instance.highlightedIndex = filtered.length > 0 ? 0 : -1;
            const first = menu.querySelector('.searchable-dropdown-item');
            if (first) first.classList.add('is-highlighted');
            openMenu();
        };

        const moveHighlight = (step) => {
            const items = Array.from(menu.querySelectorAll('.searchable-dropdown-item'));
            if (items.length === 0) return;
            if (!isOpen(instance)) {
                render(input.value);
            }
            items.forEach((el) => el.classList.remove('is-highlighted'));
            const max = items.length - 1;
            const next = instance.highlightedIndex < 0
                ? 0
                : Math.max(0, Math.min(max, instance.highlightedIndex + step));
            instance.highlightedIndex = next;
            items[next].classList.add('is-highlighted');
        };

        input.addEventListener('focus', () => render(input.value));
        input.addEventListener('click', () => render(input.value));
        input.addEventListener('input', () => render(input.value));
        input.addEventListener('blur', () => {
            window.setTimeout(() => closeMenu(), 120);
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                moveHighlight(1);
                return;
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                moveHighlight(-1);
                return;
            }
            if (e.key === 'Enter' && isOpen(instance)) {
                const items = Array.from(menu.querySelectorAll('.searchable-dropdown-item'));
                const idx = Math.max(0, instance.highlightedIndex);
                const chosen = items[idx];
                if (chosen) {
                    e.preventDefault();
                    chosen.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                }
                return;
            }
            if (e.key === 'Escape') {
                closeMenu();
            }
        });

        document.addEventListener('mousedown', (e) => {
            if (!wrapper.contains(e.target)) {
                closeMenu();
            }
        });

        INSTANCES.set(input, instance);
        render(input.value);
        closeMenu();
    },

    refresh(input) {
        const instance = INSTANCES.get(input);
        if (!instance) return;
        const shouldOpen = isOpen(instance);
        const query = input.value;
        instance.menu.innerHTML = '';
        instance.highlightedIndex = -1;
        if (shouldOpen) {
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (!shouldOpen) {
            instance.menu.style.display = 'none';
        }
        if (!instance.config.freeform && !query) {
            input.value = '';
        }
    }
};
