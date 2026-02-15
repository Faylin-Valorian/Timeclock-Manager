import { TimesheetAPI } from './api.js';

export const TimesheetForm = {
    init() {
        this.setupHybridTimeWidgets();
        this.setupTravelToggle();
        this.setupCalculations();
        this.setupLocationLogic(); 
    },

    showModal() { document.getElementById('timesheet-modal').style.display = 'flex'; },
    hideModal() { document.getElementById('timesheet-modal').style.display = 'none'; },
    setTitle(title) { document.getElementById('modal-date-title').innerText = title; },
    setDate(date) { document.getElementById('timesheet-date').value = date; },
    toggleDeleteButton(show) { 
        const btn = document.getElementById('btn-delete');
        if (btn) btn.style.display = show ? 'block' : 'none'; 
    },

    reset() {
        document.getElementById('timesheet-form').reset();
        document.getElementById('total-hours').value = "0.00";
        document.getElementById('travel-fields-container').classList.add('hidden-section');
        
        // [UPDATED] Reset Toggles
        document.getElementById('toggle-pto').checked = false;
        document.getElementById('toggle-travel').checked = false;

        // Reset widgets
        document.querySelectorAll('.time-widget-wrapper').forEach(w => {
            w.querySelector('input').value = '';
            w.querySelector('.time-select-h').value = '08';
            w.querySelector('.time-select-m').value = '00';
            w.querySelector('.time-select-ampm').value = 'AM';
        });
        
        this.populateCountyDatalist([]);
    },

    populate(data) {
        this.setHybridTime('time-in', data.time_in);
        this.setHybridTime('time-out', data.time_out);
        
        document.getElementById('break-min').value = data.time_break || 0;
        document.getElementById('total-hours').value = data.time_total || 0;
        document.getElementById('additional-comments').value = data.additional_comments || '';

        // [UPDATED] Populate PTO Toggle
        document.getElementById('toggle-pto').checked = parseInt(data.is_pto) === 1;

        // Populate Travel Toggles
        document.getElementById('req-per-diem').checked = parseInt(data.travel_per_diem) === 1;
        document.getElementById('road-scanning').checked = parseInt(data.travel_road_scanning) === 1;
        document.getElementById('first-last-day').checked = parseInt(data.travel_first_last_day) === 1;
        document.getElementById('overnight').checked = parseInt(data.travel_overnight) === 1;
        
        document.getElementById('travel-state').value = data.travel_state || '';
        document.getElementById('travel-county').value = data.travel_county || '';
        document.getElementById('travel-miles').value = data.travel_miles || 0;
        document.getElementById('travel-extra-expense').value = data.travel_extra_expenses || 0;

        if (data.travel_state) {
            TimesheetAPI.getCounties(data.travel_state).then(counties => {
                this.populateCountyDatalist(counties);
            });
        }

        // Logic to show Travel Section if data exists
        const hasTravel = data.travel_state || data.travel_miles > 0 || data.travel_per_diem == 1 || data.travel_extra_expenses > 0;
        document.getElementById('toggle-travel').checked = hasTravel;
        document.getElementById('travel-fields-container').classList.toggle('hidden-section', !hasTravel);
    },

    gatherData() {
        return {
            date: document.getElementById('timesheet-date').value,
            time_in: this.parseTo24Hour(document.getElementById('time-in').value),
            time_out: this.parseTo24Hour(document.getElementById('time-out').value),
            break_min: document.getElementById('break-min').value,
            time_total: document.getElementById('total-hours').value,
            comments: document.getElementById('additional-comments').value,
            
            // [UPDATED] Include PTO in save data
            is_pto: document.getElementById('toggle-pto').checked ? 1 : 0,

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

    // --- LOCATION LOGIC ---

    setupLocationLogic() {
        const stateInput = document.getElementById('travel-state');
        if (stateInput) {
            stateInput.addEventListener('change', async (e) => {
                const abbr = e.target.value;
                if (abbr && abbr.length === 2) {
                    const counties = await TimesheetAPI.getCounties(abbr);
                    this.populateCountyDatalist(counties);
                } else {
                    this.populateCountyDatalist([]);
                }
            });
            
            stateInput.addEventListener('blur', async (e) => {
                const abbr = e.target.value;
                 if (abbr && abbr.length === 2) {
                    const counties = await TimesheetAPI.getCounties(abbr);
                    this.populateCountyDatalist(counties);
                }
            });
        }
    },

    populateStateDatalist(states) {
        const dl = document.getElementById('state-options');
        if (dl) {
            dl.innerHTML = '';
            states.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.state_abbr;
                opt.label = s.state_name;
                dl.appendChild(opt);
            });
        }
    },

    populateCountyDatalist(counties) {
        const dl = document.getElementById('county-options');
        if (dl) {
            dl.innerHTML = '';
            counties.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.county_name || c.name || c; 
                dl.appendChild(opt);
            });
        }
    },

    // --- HYBRID WIDGET LOGIC ---

    setupHybridTimeWidgets() {
        document.querySelectorAll('.time-widget-wrapper').forEach(wrapper => {
            const input = wrapper.querySelector('input');
            const popover = wrapper.querySelector('.time-popover');
            const selects = {
                h: wrapper.querySelector('.time-select-h'),
                m: wrapper.querySelector('.time-select-m'),
                ampm: wrapper.querySelector('.time-select-ampm')
            };

            const show = () => {
                document.querySelectorAll('.time-popover').forEach(el => el.style.display = 'none');
                popover.style.display = 'flex';
            };
            
            input.addEventListener('focus', show);
            input.addEventListener('click', show);

            document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target)) {
                    popover.style.display = 'none';
                }
            });

            const updateInput = () => {
                const h = selects.h.value;
                const m = selects.m.value;
                const ampm = selects.ampm.value;
                input.value = `${h}:${m} ${ampm}`;
                this.calculateTotal();
            };
            
            selects.h.addEventListener('change', updateInput);
            selects.m.addEventListener('change', updateInput);
            selects.ampm.addEventListener('change', updateInput);

            input.addEventListener('blur', (e) => {
                const formatted = this.formatUserInput(e.target.value);
                if (formatted) {
                    input.value = formatted;
                    this.calculateTotal();
                    
                    const match = formatted.match(/(\d{2}):(\d{2})\s(AM|PM)/);
                    if (match) {
                        selects.h.value = match[1];
                        selects.m.value = match[2];
                        selects.ampm.value = match[3];
                    }
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    popover.style.display = 'none';
                    input.blur();
                }
            });
        });
    },

    setHybridTime(id, dbTime) {
        const input = document.getElementById(id);
        if (!input) return;
        
        if (!dbTime) {
            input.value = '';
            return;
        }

        const [h24, m] = dbTime.split(':');
        let h = parseInt(h24);
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;

        const formatted = `${h.toString().padStart(2,'0')}:${m} ${ampm}`;
        input.value = formatted;

        const wrapper = input.closest('.time-widget-wrapper');
        if (wrapper) {
            wrapper.querySelector('.time-select-h').value = h.toString().padStart(2,'0');
            wrapper.querySelector('.time-select-m').value = m;
            wrapper.querySelector('.time-select-ampm').value = ampm;
        }
    },

    formatUserInput(input) {
        if (!input) return '';
        const clean = input.toLowerCase().replace(/\s/g, '');
        const match = clean.match(/^(\d{1,2})[:.]?(\d{2})?([ap](?:m)?)?$/);
        if (!match) return input; 

        let h = parseInt(match[1]);
        let m = match[2] ? parseInt(match[2]) : 0;
        let suffix = match[3];

        if (h > 23 || m > 59) return input;

        if (h > 12 && !suffix) { suffix = 'pm'; h -= 12; }
        else if (h === 0) { h = 12; suffix = 'am'; }

        let ampm = 'AM';
        if (suffix && suffix.startsWith('p')) ampm = 'PM';
        else if (h === 12 && !suffix) ampm = 'PM'; 

        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')} ${ampm}`;
    },

    parseTo24Hour(displayTime) {
        if (!displayTime) return null;
        const match = displayTime.match(/^(\d{1,2}):(\d{2})\s?(AM|PM)$/i);
        if (!match) return null;

        let h = parseInt(match[1]);
        const m = match[2];
        const ampm = match[3].toUpperCase();

        if (ampm === 'PM' && h < 12) h += 12;
        if (ampm === 'AM' && h === 12) h = 0;

        return `${h.toString().padStart(2, '0')}:${m}`;
    },

    setupTravelToggle() {
        document.getElementById('toggle-travel')?.addEventListener('change', (e) => {
            document.getElementById('travel-fields-container').classList.toggle('hidden-section', !e.target.checked);
        });
    },

    setupCalculations() {
        ['break-min'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => this.calculateTotal());
        });
    },

    calculateTotal() {
        const tInStr = this.parseTo24Hour(document.getElementById('time-in').value);
        const tOutStr = this.parseTo24Hour(document.getElementById('time-out').value);
        const breakMin = parseInt(document.getElementById('break-min').value) || 0;
        
        if (tInStr && tOutStr) {
            const d1 = new Date(`2000-01-01T${tInStr}`);
            const d2 = new Date(`2000-01-01T${tOutStr}`);
            if (d2 < d1) d2.setDate(d2.getDate() + 1); 

            let diffMins = (d2 - d1) / 60000;
            diffMins -= breakMin;
            
            const total = Math.max(0, diffMins / 60);
            document.getElementById('total-hours').value = total.toFixed(2);
        }
    }
};