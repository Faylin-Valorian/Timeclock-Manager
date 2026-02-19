import { TimesheetAPI } from './api.js';
import { TimeWidget } from './time.js';
import { LocationManager } from './location.js';

export const TimesheetForm = {
    
    init() {
        TimeWidget.init(() => this.calculateTotal());
        LocationManager.init();
        this.setupToggles();
        this.setupCalculations();
        this.setupNumericConstraints();
        this.setupTabSkipTargets();
        this.isAutoHolidayRecord = false;
    },

    // Optional: Keep local ID ref if needed, though Module handles it.
    setIds(id) {
        this.currentId = id; 
    },

    // --- MODAL CONTROLS ---
    showModal() { document.getElementById('timesheet-modal').style.display = 'flex'; },
    hideModal() { document.getElementById('timesheet-modal').style.display = 'none'; },
    setTitle(title) { document.getElementById('modal-date-title').innerText = title; },
    setDate(date) { document.getElementById('timesheet-date').value = date; },
    toggleDeleteButton(show) { 
        const btn = document.getElementById('btn-delete');
        const wrap = btn?.closest('.shortcut-button-wrap');
        if (btn) btn.style.display = show ? 'inline-flex' : 'none';
        if (wrap) wrap.style.display = show ? 'flex' : 'none';
    },

    setReadOnly(readOnly) {
        const form = document.getElementById('timesheet-form');
        if (!form) return;

        const controls = form.querySelectorAll('input, select, textarea, button');
        controls.forEach((el) => {
            if (el.classList.contains('close-modal')) return;
            if (el.id === 'btn-delete') return;
            if (el.type === 'submit') return;
            el.disabled = !!readOnly;
        });
    },

    applyPtoTimeDefaults() {
        const hasTimeIn = !!(document.getElementById('time-in')?.value || '').trim();
        const hasTimeOut = !!(document.getElementById('time-out')?.value || '').trim();
        if (hasTimeIn || hasTimeOut) return false;

        TimeWidget.set('time-in', '08:00');
        TimeWidget.set('time-out', '17:00');
        document.getElementById('break-min').value = 60;
        this.calculateTotal();
        return true;
    },

    reset() {
        document.getElementById('timesheet-form').reset();
        document.getElementById('total-hours').value = "0.00";
        document.getElementById('travel-fields-container').classList.add('hidden-section');
        
        // Reset Toggles
        document.getElementById('toggle-pto').checked = false;
        document.getElementById('toggle-travel').checked = false;

        // Reset Helpers
        TimeWidget.reset();
        LocationManager.reset();
        this.sanitizeNullishDom();
        this.setReadOnly(false);
        this.isAutoHolidayRecord = false;
    },

    populate(data) {
        const clean = (v) => {
            if (v === null || v === undefined) return '';
            if (typeof v === 'string' && v.trim().toLowerCase() === 'null') return '';
            return v;
        };
        const toIntOr = (v, fallback = 0) => {
            const n = parseInt(clean(v), 10);
            return Number.isNaN(n) ? fallback : n;
        };
        const toFloatOr = (v, fallback = 0) => {
            const n = parseFloat(clean(v));
            return Number.isNaN(n) ? fallback : n;
        };

        this.isAutoHolidayRecord = parseInt(data.is_pto, 10) === 2;

        // 1. Standard Fields
        // Backend sends snake_case via jsonSerialize
        document.getElementById('break-min').value = toIntOr(data.time_break ?? data.break_min, 0);
        document.getElementById('total-hours').value = toFloatOr(data.time_total, 0).toFixed(2);
        document.getElementById('additional-comments').value = String(clean(data.additional_comments) || '');

        // 2. Time Widget
        TimeWidget.set('time-in', data.time_in);
        TimeWidget.set('time-out', data.time_out);

        // 3. Toggles
        // Ensure strictly checking against 1
        document.getElementById('toggle-pto').checked = parseInt(data.is_pto) === 1;
        
        // Travel Toggles
        document.getElementById('req-per-diem').checked = parseInt(data.travel_per_diem) === 1;
        document.getElementById('road-scanning').checked = parseInt(data.travel_road_scanning) === 1;
        document.getElementById('first-last-day').checked = parseInt(data.travel_first_last_day) === 1;
        document.getElementById('overnight').checked = parseInt(data.travel_overnight) === 1;
        
        document.getElementById('travel-miles').value = toIntOr(data.travel_miles, 0);
        document.getElementById('travel-extra-expense').value = toFloatOr(data.travel_extra_expenses, 0).toFixed(2);

        // 4. Location Helper
        LocationManager.populate(clean(data.travel_state), clean(data.travel_county));

        // 5. Visibility Logic
        // Check if any travel data exists to auto-expand the section
        const hasTravel = 
            (data.travel_state && data.travel_state !== '') || 
            (data.travel_miles > 0) || 
            (parseInt(data.travel_per_diem) === 1) || 
            (parseFloat(data.travel_extra_expenses) > 0);

        document.getElementById('toggle-travel').checked = hasTravel;
        document.getElementById('travel-fields-container').classList.toggle('hidden-section', !hasTravel);
        this.sanitizeNullishDom();
    },

    gatherData() {
        const milesRaw = parseInt(document.getElementById('travel-miles').value, 10);
        const miles = Number.isNaN(milesRaw) ? 0 : Math.max(0, milesRaw);

        const expenseRaw = parseFloat(document.getElementById('travel-extra-expense').value);
        const expenses = Number.isNaN(expenseRaw) ? 0 : Math.max(0, expenseRaw);
        const roundedExpenses = Number(expenses.toFixed(2));

        return {
            date: document.getElementById('timesheet-date').value,
            
            time_in: TimeWidget.get('time-in'),
            time_out: TimeWidget.get('time-out'),
            
            break_min: document.getElementById('break-min').value,
            time_total: document.getElementById('total-hours').value,
            comments: document.getElementById('additional-comments').value,
            
            is_pto: (() => {
                const manual = document.getElementById('toggle-pto').checked ? 1 : 0;
                return (this.isAutoHolidayRecord && manual === 0) ? 2 : manual;
            })(),

            travel_per_diem: document.getElementById('req-per-diem').checked ? 1 : 0,
            travel_road_scanning: document.getElementById('road-scanning').checked ? 1 : 0,
            travel_first_last_day: document.getElementById('first-last-day').checked ? 1 : 0,
            travel_overnight: document.getElementById('overnight').checked ? 1 : 0,
            
            travel_state: document.getElementById('travel-state').value,
            travel_county: document.getElementById('travel-county').value,
            travel_miles: miles,
            travel_extra_expenses: roundedExpenses,
        };
    },

    setupToggles() {
        document.getElementById('toggle-travel')?.addEventListener('change', (e) => {
            document.getElementById('travel-fields-container').classList.toggle('hidden-section', !e.target.checked);
        });
    },

    setupCalculations() {
        // Recalculate total when break minutes change
        const breakInput = document.getElementById('break-min');
        breakInput?.addEventListener('input', () => this.calculateTotal());
        breakInput?.addEventListener('focus', () => {
            // Keyboard flow: tab into Break and overwrite immediately.
            window.setTimeout(() => breakInput.select(), 0);
        });
    },

    setupNumericConstraints() {
        const milesInput = document.getElementById('travel-miles');
        milesInput?.addEventListener('input', () => {
            const raw = parseInt(milesInput.value, 10);
            if (Number.isNaN(raw)) return;
            if (raw < 0) milesInput.value = '0';
        });

        const expenseInput = document.getElementById('travel-extra-expense');
        expenseInput?.addEventListener('input', () => {
            if (!expenseInput.value) return;
            // Keep only numeric + single decimal and clamp precision to 2.
            let val = expenseInput.value.replace(/[^0-9.]/g, '');
            const firstDot = val.indexOf('.');
            if (firstDot !== -1) {
                val = val.slice(0, firstDot + 1) + val.slice(firstDot + 1).replace(/\./g, '');
                const [whole, frac = ''] = val.split('.');
                val = `${whole}.${frac.slice(0, 2)}`;
            }
            if (val.startsWith('-')) val = val.replace('-', '');
            expenseInput.value = val;
        });
        expenseInput?.addEventListener('blur', () => {
            if (!expenseInput.value) return;
            const parsed = parseFloat(expenseInput.value);
            if (Number.isNaN(parsed) || parsed < 0) {
                expenseInput.value = '0.00';
                return;
            }
            expenseInput.value = parsed.toFixed(2);
        });
    },

    setupTabSkipTargets() {
        document.getElementById('timesheet-date')?.setAttribute('tabindex', '-1');
        document.getElementById('total-hours')?.setAttribute('tabindex', '-1');
        document.getElementById('btn-add-row')?.setAttribute('tabindex', '-1');
        document.getElementById('btn-delete')?.setAttribute('tabindex', '-1');
    },

    calculateTotal() {
        const tInStr = TimeWidget.get('time-in');
        const tOutStr = TimeWidget.get('time-out');
        const breakMin = parseInt(document.getElementById('break-min').value) || 0;
        
        if (tInStr && tOutStr) {
            const d1 = new Date(`2000-01-01T${tInStr}`);
            const d2 = new Date(`2000-01-01T${tOutStr}`);
            
            // Handle overnight shifts (e.g. 11 PM to 2 AM)
            if (d2 < d1) d2.setDate(d2.getDate() + 1); 

            let diffMins = (d2 - d1) / 60000;
            diffMins -= breakMin;
            
            const total = Math.max(0, diffMins / 60);
            document.getElementById('total-hours').value = total.toFixed(2);
        }
    },
    
    /**
     * Helper to populate the datalist for State dropdown
     */
    populateStateDatalist(states) {
        LocationManager.setStates(states || []);
    },

    sanitizeNullishDom() {
        const form = document.getElementById('timesheet-form');
        if (!form) return;

        form.querySelectorAll('input, textarea').forEach((el) => {
            if (typeof el.value === 'string' && el.value.trim().toLowerCase() === 'null') {
                el.value = '';
            }
            if (typeof el.placeholder === 'string' && el.placeholder.trim().toLowerCase() === 'null') {
                el.placeholder = '';
            }
        });
    }
};
