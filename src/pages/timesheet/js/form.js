export const TimesheetForm = {
    init() {
        this.setupSmartTimeInputs();
        this.setupTravelToggle();
        this.setupCalculations();
    },

    // ... (Keep existing showModal, hideModal, setTitle, etc.) ...
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
    },

    populate(data) {
        // Direct assignment now (Parser handles formatting on blur if user edits)
        this.setSmartTime('time-in', data.time_in);
        this.setSmartTime('time-out', data.time_out);
        
        document.getElementById('break-min').value = data.time_break || 0;
        document.getElementById('total-hours').value = data.time_total || 0;
        document.getElementById('additional-comments').value = data.additional_comments || '';

        // Toggles & Travel (Same as before)
        document.getElementById('req-per-diem').checked = parseInt(data.travel_per_diem) === 1;
        document.getElementById('road-scanning').checked = parseInt(data.travel_road_scanning) === 1;
        document.getElementById('first-last-day').checked = parseInt(data.travel_first_last_day) === 1;
        document.getElementById('overnight').checked = parseInt(data.travel_overnight) === 1;
        
        document.getElementById('travel-state').value = data.travel_state || '';
        document.getElementById('travel-county').value = data.travel_county || '';
        document.getElementById('travel-miles').value = data.travel_miles || 0;
        document.getElementById('travel-extra-expense').value = data.travel_extra_expenses || 0;

        const hasTravel = data.travel_state || data.travel_miles > 0 || data.travel_per_diem == 1;
        document.getElementById('toggle-travel').checked = hasTravel;
        document.getElementById('travel-fields-container').classList.toggle('hidden-section', !hasTravel);
    },

    gatherData() {
        return {
            date: document.getElementById('timesheet-date').value,
            // Convert "08:00 PM" back to "20:00" for DB
            time_in: this.parseTo24Hour(document.getElementById('time-in').value),
            time_out: this.parseTo24Hour(document.getElementById('time-out').value),
            
            break_min: document.getElementById('break-min').value,
            time_total: document.getElementById('total-hours').value,
            comments: document.getElementById('additional-comments').value,
            
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

    // --- NEW SMART TIME LOGIC ---

    setupSmartTimeInputs() {
        document.querySelectorAll('.smart-time').forEach(input => {
            input.addEventListener('blur', (e) => {
                const formatted = this.formatUserInput(e.target.value);
                if (formatted) {
                    e.target.value = formatted;
                    this.calculateTotal();
                }
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.target.blur(); // Trigger formatting
                }
            });
        });
    },

    /**
     * Converts raw DB time (13:00) to Display Time (01:00 PM)
     */
    setSmartTime(id, dbTime) {
        const el = document.getElementById(id);
        if (!el) return;
        if (!dbTime) {
            el.value = '';
            return;
        }
        
        const [h, m] = dbTime.split(':');
        let hour = parseInt(h);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12; // Convert 0/12 to 12
        
        el.value = `${hour.toString().padStart(2,'0')}:${m} ${ampm}`;
    },

    /**
     * Converts User Input (0810p, 8, 14:00) -> Display Format (08:10 PM)
     */
    formatUserInput(input) {
        if (!input) return '';
        const clean = input.toLowerCase().replace(/\s/g, '');
        
        // Regex to match various formats
        // Matches: 8, 830, 0830, 8:30, 8.30 + optional (a, p, am, pm)
        const match = clean.match(/^(\d{1,2})[:.]?(\d{2})?([ap](?:m)?)?$/);

        if (!match) return input; // Return original if unknown format

        let h = parseInt(match[1]);
        let m = match[2] ? parseInt(match[2]) : 0;
        let suffix = match[3];

        // Basic validation
        if (h > 23 || m > 59) return input;

        // Handle 24h input (e.g., 1400 -> 2:00 PM)
        if (h > 12 && !suffix) {
            suffix = 'pm';
            h -= 12;
        } else if (h === 0 || h === 0 && !suffix) {
             // 00:00 -> 12:00 AM
             h = 12;
             suffix = 'am';
        }

        // Handle suffix
        let ampm = 'AM';
        if (suffix && suffix.startsWith('p')) {
            ampm = 'PM';
        } else if (suffix && suffix.startsWith('a')) {
            ampm = 'AM';
        } else {
            // No suffix provided? Guess based on typical work hours (7am - 6pm)
            // Or default to AM if vague, but "8" usually implies 8 AM. "5" might be 5 PM?
            // Safer to default AM unless > 12.
            // Power user shorthand: 8 -> 8:00 AM, 13 -> 1:00 PM
            if (h === 12) ampm = 'PM'; // Default 12 to 12 PM
        }

        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')} ${ampm}`;
    },

    /**
     * Converts Display Format (01:00 PM) -> DB Format (13:00)
     */
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
        // We use our parser to get clean 24h strings for calculation
        const tInStr = this.parseTo24Hour(document.getElementById('time-in').value);
        const tOutStr = this.parseTo24Hour(document.getElementById('time-out').value);
        const breakMin = parseInt(document.getElementById('break-min').value) || 0;
        
        if (tInStr && tOutStr) {
            const d1 = new Date(`2000-01-01T${tInStr}`);
            const d2 = new Date(`2000-01-01T${tOutStr}`);
            
            // Handle overnight (if Out is before In, assume next day)
            // Note: This logic assumes work < 24 hrs
            if (d2 < d1) d2.setDate(d2.getDate() + 1); 

            let diffMins = (d2 - d1) / 60000;
            diffMins -= breakMin;
            
            const total = Math.max(0, diffMins / 60);
            document.getElementById('total-hours').value = total.toFixed(2);
        }
    }
};