import { StechAPI } from '../../../../api/api.js';

export const HolidayAdmin = {
    holidays: [],

    load() {
        console.log("Loading Holiday Module...");
        this.bindEvents();
        this.fetchHolidays();
    },

    bindEvents() {
        if (this._eventsBound) return;

        // Form Submit
        document.getElementById('form-holiday')?.addEventListener('submit', (e) => this.submit(e));
        
        // Reset/Cancel
        document.getElementById('btn-cancel-holiday')?.addEventListener('click', () => this.resetForm());

        // Search
        document.getElementById('holiday-search-input')?.addEventListener('input', () => this.render());

        // Filter Radio
        document.querySelectorAll('input[name="holiday-status"]').forEach(r => {
            r.addEventListener('change', () => this.render());
        });

        // Color Sync
        const hColor = document.getElementById('holiday-color');
        const hColorText = document.getElementById('holiday-color-text');
        if (hColor && hColorText) {
            hColor.addEventListener('input', (e) => hColorText.value = e.target.value);
            hColorText.addEventListener('input', (e) => hColor.value = e.target.value);
        }

        this._eventsBound = true;
    },

    async fetchHolidays() {
        try {
            this.holidays = await StechAPI.admin.getHolidays();
            this.render();
        } catch (e) {
            console.error(e);
        }
    },

    render() {
        const container = document.getElementById('holiday-list');
        if (!container) return;

        const filter = document.querySelector('input[name="holiday-status"]:checked')?.value || 'active';
        const search = document.getElementById('holiday-search-input')?.value.toLowerCase() || '';

        const filtered = this.holidays.filter(h => {
            const matchesSearch = h.name.toLowerCase().includes(search);
            const isArchived = h.archived == 1 || h.archived == '1';
            
            if (!matchesSearch) return false;
            return filter === 'active' ? !isArchived : isArchived;
        });

        container.innerHTML = filtered.map(h => `
            <div class="list-item" onclick="document.dispatchEvent(new CustomEvent('edit-holiday', {detail: ${h.id}}))">
                <div class="color-dot" style="background-color: ${h.color || '#e67e22'}"></div>
                <div style="flex-grow:1;">
                    <div class="item-title">${h.name}</div>
                    <div class="item-subtitle">${h.start_date} ${h.end_date ? ' - ' + h.end_date : ''}</div>
                </div>
                <button class="action-icon" onclick="event.stopPropagation(); window.HolidayAdmin.toggle(${h.id})">
                    <span class="icon-${h.archived == 1 ? 'history' : 'delete'}"></span>
                </button>
            </div>
        `).join('');

        // Re-attach edit listeners manually or via global delegation
        // For simplicity, we expose a helper on window or use delegation in bindEvents
        // Here I used a CustomEvent dispatch for elegance
        container.querySelectorAll('.list-item').forEach((el, index) => {
            el.addEventListener('click', () => this.edit(filtered[index]));
        });
    },

    edit(holiday) {
        document.getElementById('holiday-id').value = holiday.id;
        document.getElementById('holiday-name').value = holiday.name;
        document.getElementById('holiday-start').value = holiday.start_date;
        document.getElementById('holiday-end').value = holiday.end_date || '';
        document.getElementById('holiday-color').value = holiday.color || '#e67e22';
        document.getElementById('holiday-color-text').value = holiday.color || '#e67e22';

        document.getElementById('holiday-form-title').textContent = "Edit Holiday";
        document.getElementById('btn-save-holiday').textContent = "Update Holiday";
        document.getElementById('btn-cancel-holiday').classList.remove('hidden');
    },

    resetForm() {
        document.getElementById('form-holiday').reset();
        document.getElementById('holiday-id').value = '';
        document.getElementById('holiday-form-title').textContent = "Add Holiday";
        document.getElementById('btn-save-holiday').textContent = "Add Holiday";
        document.getElementById('btn-cancel-holiday').classList.add('hidden');
    },

    async submit(e) {
        e.preventDefault();
        const id = document.getElementById('holiday-id').value;
        const payload = {
            id: id || null,
            name: document.getElementById('holiday-name').value,
            start_date: document.getElementById('holiday-start').value,
            end_date: document.getElementById('holiday-end').value,
            color: document.getElementById('holiday-color').value
        };

        await StechAPI.admin.saveHoliday(payload);
        this.resetForm();
        this.fetchHolidays();
    },

    async toggle(id) {
        if(confirm("Are you sure you want to archive/restore this holiday?")) {
            await StechAPI.admin.toggleHoliday(id);
            this.fetchHolidays();
        }
    }
};

// Global helper for the inline onclick (optional, but robust)
window.HolidayAdmin = HolidayAdmin;