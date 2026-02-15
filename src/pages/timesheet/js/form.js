import { TimesheetAPI } from './api.js';
import { TimeWidget } from './time.js';
import { LocationManager } from './location.js';

export const TimesheetForm = {
    init() {
        // Initialize Helpers
        // Pass 'calculateTotal' as a callback so the widget recalculates when time changes
        TimeWidget.init(() => this.calculateTotal());
        LocationManager.init();
        
        this.setupToggles();
        this.setupCalculations();
    },

    // --- MODAL CONTROLS ---
    showModal() { document.getElementById('timesheet-modal').style.display = 'flex'; },
    hideModal() { document.getElementById('timesheet-modal').style.display = 'none'; },
    setTitle(title) { document.getElementById('modal-date-title').innerText = title; },
    setDate(date) { document.getElementById('timesheet-date').value = date; },
    toggleDeleteButton(show) { 
        const btn = document.getElementById('btn-delete');
        if (btn) btn.style.display = show ? 'block' : 'none'; 
    },

    // --- DATA MANAGEMENT ---

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
    },

    populate(data) {
        // 1. Standard Fields
        document.getElementById('break-min').value = data.time_break || 0;
        document.getElementById('total-hours').value = data.time_total || 0;
        document.getElementById('additional-comments').value = data.additional_comments || '';

        // 2. Time Widget Helper
        TimeWidget.set('time-in', data.time_in);
        TimeWidget.set('time-out', data.time_out);

        // 3. Toggles
        document.getElementById('toggle-pto').checked = parseInt(data.is_pto) === 1;
        
        // Travel Sub-Toggles
        document.getElementById('req-per-diem').checked = parseInt(data.travel_per_diem) === 1;
        document.getElementById('road-scanning').checked = parseInt(data.travel_road_scanning) === 1;
        document.getElementById('first-last-day').checked = parseInt(data.travel_first_last_day) === 1;
        document.getElementById('overnight').checked = parseInt(data.travel_overnight) === 1;
        
        document.getElementById('travel-miles').value = data.travel_miles || 0;
        document.getElementById('travel-extra-expense').value = data.travel_extra_expenses || 0;

        // 4. Location Helper (Async)
        LocationManager.populate(data.travel_state, data.travel_county);

        // 5. Visibility Logic
        const hasTravel = data.travel_state || data.travel_miles > 0 || data.travel_per_diem == 1 || data.travel_extra_expenses > 0;
        document.getElementById('toggle-travel').checked = hasTravel;
        document.getElementById('travel-fields-container').classList.toggle('hidden-section', !hasTravel);
    },

    gatherData() {
        return {
            date: document.getElementById('timesheet-date').value,
            
            // Get Time from Helper
            time_in: TimeWidget.get('time-in'),
            time_out: TimeWidget.get('time-out'),
            
            break_min: document.getElementById('break-min').value,
            time_total: document.getElementById('total-hours').value,
            comments: document.getElementById('additional-comments').value,
            
            // Toggles
            is_pto: document.getElementById('toggle-pto').checked ? 1 : 0,

            // Travel Data
            travel_per_diem: document.getElementById('req-per-diem').checked ? 1 : 0,
            travel_road_scanning: document.getElementById('road-scanning').checked ? 1 : 0,
            travel_first_last_day: document.getElementById('first-last-day').checked ? 1 : 0,
            travel_overnight: document.getElementById('overnight').checked ? 1 : 0,
            
            travel_state: document.getElementById('travel-state').value,
            travel_county: document.getElementById('travel-county').value,
            travel_miles: document.getElementById('travel-miles').value,
            travel_extra_expenses: document.getElementById('travel-extra-expense').value,
        };
    },

    // --- INTERNAL LOGIC ---

    setupToggles() {
        // Toggle Travel Section Visibility
        document.getElementById('toggle-travel')?.addEventListener('change', (e) => {
            document.getElementById('travel-fields-container').classList.toggle('hidden-section', !e.target.checked);
        });
    },

    setupCalculations() {
        // Trigger recalc on Break change
        document.getElementById('break-min')?.addEventListener('input', () => this.calculateTotal());
        
        // Note: Time inputs trigger this via the TimeWidget callback
    },

    calculateTotal() {
        // Get raw 24h strings from helper
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
    }
};