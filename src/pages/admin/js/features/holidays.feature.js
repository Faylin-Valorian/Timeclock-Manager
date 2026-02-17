const HolidaysFeature = {
    id: 'holidays',
    label: "Manage Holiday's",
    order: 20,
    state: {
        filter: 'active'
    },

    render() {
        return `
            <div class="admin-toolbar">
                <input id="holiday-search" class="admin-control" type="text" placeholder="Search holidays...">
                <button id="holiday-filter-toggle" type="button" class="admin-pill">Filter: Active</button>
                <button id="holiday-new" type="button" class="admin-pill admin-pill-primary">+ New Holiday</button>
            </div>
            <div class="admin-holidays-layout">
                <div id="holiday-list" class="admin-list"></div>
                <div class="admin-editor">
                    <input id="holiday-id" type="hidden">
                    <label>Holiday Name</label>
                    <input id="holiday-name" class="admin-control" type="text">
                    <label>Start Date</label>
                    <input id="holiday-start" class="admin-control" type="date">
                    <label>End Date</label>
                    <input id="holiday-end" class="admin-control" type="date">
                    <label>Color</label>
                    <input id="holiday-bg" class="admin-control" type="color" value="#95a5a6">
                    <div class="admin-toggle-row">
                        <input type="checkbox" id="holiday-active" checked>
                        <label for="holiday-active">Active</label>
                    </div>
                    <button id="holiday-save" type="button" class="admin-pill admin-pill-primary">Save Holiday</button>
                </div>
            </div>
        `;
    },

    init(ctx) {
        ctx.root.querySelector('#holiday-search')?.addEventListener('input', () => this.renderHolidayList(ctx));
        ctx.root.querySelector('#holiday-filter-toggle')?.addEventListener('click', () => {
            const next = this.state.filter === 'active' ? 'inactive' : 'active';
            this.state.filter = next;
            const btn = ctx.root.querySelector('#holiday-filter-toggle');
            if (btn) btn.textContent = `Filter: ${next.charAt(0).toUpperCase() + next.slice(1)}`;
            this.renderHolidayList(ctx);
        });
        ctx.root.querySelector('#holiday-new')?.addEventListener('click', () => this.populateHolidayEditor(ctx, null));
        ctx.root.querySelector('#holiday-save')?.addEventListener('click', async () => {
            await this.saveHoliday(ctx);
        });
    },

    refresh(ctx) {
        this.renderHolidayList(ctx);
        this.populateHolidayEditor(ctx, null);
    },

    renderHolidayList(ctx) {
        const list = ctx.root.querySelector('#holiday-list');
        if (!list) return;

        const q = (ctx.root.querySelector('#holiday-search')?.value || '').toLowerCase();
        const filtered = (ctx.overlay.data.holidays || []).filter((h) => {
            const archived = parseInt(h.holiday_archive, 10) === 1;
            if (this.state.filter === 'active' && archived) return false;
            if (this.state.filter === 'inactive' && !archived) return false;
            return (h.holiday_name || '').toLowerCase().includes(q);
        });

        if (filtered.length === 0) {
            list.innerHTML = '<div class="admin-list-empty">No holidays found.</div>';
            return;
        }

        list.innerHTML = filtered.map((h) => {
            const archived = parseInt(h.holiday_archive, 10) === 1;
            return `
                <button type="button" class="admin-list-item ${archived ? 'inactive' : ''}" data-holiday-id="${h.holiday_id}">
                    <span class="admin-list-name">${h.holiday_name}</span>
                    <span class="admin-list-meta">${h.holiday_start_date} to ${h.holiday_end_date}</span>
                </button>
            `;
        }).join('');

        list.querySelectorAll('.admin-list-item').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.holidayId, 10);
                const holiday = (ctx.overlay.data.holidays || []).find((h) => parseInt(h.holiday_id, 10) === id);
                this.populateHolidayEditor(ctx, holiday || null);
            });
        });
    },

    populateHolidayEditor(ctx, holiday) {
        const id = ctx.root.querySelector('#holiday-id');
        const name = ctx.root.querySelector('#holiday-name');
        const start = ctx.root.querySelector('#holiday-start');
        const end = ctx.root.querySelector('#holiday-end');
        const bg = ctx.root.querySelector('#holiday-bg');
        const active = ctx.root.querySelector('#holiday-active');
        if (!id || !name || !start || !end || !bg || !active) return;

        id.value = holiday ? String(holiday.holiday_id || '') : '';
        name.value = holiday ? (holiday.holiday_name || '') : '';
        start.value = holiday ? (holiday.holiday_start_date || '') : '';
        end.value = holiday ? (holiday.holiday_end_date || '') : '';
        bg.value = holiday ? (holiday.holiday_bg || '#95a5a6') : '#95a5a6';
        active.checked = holiday ? (parseInt(holiday.holiday_archive, 10) !== 1) : true;
    },

    async saveHoliday(ctx) {
        const payload = {
            id: parseInt(ctx.root.querySelector('#holiday-id')?.value || '0', 10) || 0,
            name: (ctx.root.querySelector('#holiday-name')?.value || '').trim(),
            start: ctx.root.querySelector('#holiday-start')?.value || '',
            end: ctx.root.querySelector('#holiday-end')?.value || '',
            bg: ctx.root.querySelector('#holiday-bg')?.value || '#95a5a6',
            archive: (ctx.root.querySelector('#holiday-active')?.checked ? 0 : 1)
        };
        if (!payload.name || !payload.start || !payload.end) return;

        await ctx.api.saveHoliday(payload);
        await ctx.requestReload();
        ctx.refetchCalendar();
    }
};

export default HolidaysFeature;

