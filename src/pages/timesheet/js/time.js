/**
 * Handles the Hybrid Time Widget (Input + 3-Column Popover)
 * and all time formatting/parsing logic.
 */
export const TimeWidget = {
    // Callback to trigger total calculation in main form
    onUpdate: null,

    init(updateCallback) {
        this.onUpdate = updateCallback;
        this.setupWidgets();
    },

    reset() {
        document.querySelectorAll('.time-widget-wrapper').forEach(w => {
            w.querySelector('input').value = '';
            w.querySelector('.time-select-h').value = '08';
            w.querySelector('.time-select-m').value = '00';
            w.querySelector('.time-select-ampm').value = 'AM';
        });
    },

    /**
     * Set the time for a specific input ID (e.g. 'time-in')
     * Accepts DB format "13:30"
     */
    set(id, dbTime) {
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

        // Sync the dropdowns inside this wrapper
        this.syncDropdowns(input, h.toString().padStart(2,'0'), m, ampm);
    },

    /**
     * Get the 24h DB format string for an input ID
     * Returns "13:30" or null
     */
    get(id) {
        const val = document.getElementById(id)?.value;
        return this.parseTo24Hour(val);
    },

    // --- INTERNAL LOGIC ---

    setupWidgets() {
        document.querySelectorAll('.time-widget-wrapper').forEach(wrapper => {
            const input = wrapper.querySelector('input');
            const popover = wrapper.querySelector('.time-popover');
            const selects = {
                h: wrapper.querySelector('.time-select-h'),
                m: wrapper.querySelector('.time-select-m'),
                ampm: wrapper.querySelector('.time-select-ampm')
            };

            // 1. Show Popover
            const show = () => {
                document.querySelectorAll('.time-popover').forEach(el => el.style.display = 'none');
                popover.style.display = 'flex';
            };
            
            input.addEventListener('focus', show);
            input.addEventListener('click', show);

            // 2. Hide Popover (Click Outside)
            document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target)) {
                    popover.style.display = 'none';
                }
            });

            // 3. Sync Selects -> Input
            const updateInput = () => {
                input.value = `${selects.h.value}:${selects.m.value} ${selects.ampm.value}`;
                if (this.onUpdate) this.onUpdate();
            };
            
            selects.h.addEventListener('change', updateInput);
            selects.m.addEventListener('change', updateInput);
            selects.ampm.addEventListener('change', updateInput);

            // 4. Sync Input -> Selects
            input.addEventListener('blur', (e) => {
                const formatted = this.formatUserInput(e.target.value);
                if (formatted) {
                    input.value = formatted;
                    if (this.onUpdate) this.onUpdate();
                    
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

    syncDropdowns(input, h, m, ampm) {
        const wrapper = input.closest('.time-widget-wrapper');
        if (wrapper) {
            wrapper.querySelector('.time-select-h').value = h;
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
    }
};