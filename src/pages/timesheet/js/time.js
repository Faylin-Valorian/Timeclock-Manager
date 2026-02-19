/**
 * Handles the Hybrid Time Widget (Input + 3-Column Popover)
 * and all time formatting/parsing logic.
 */
export const TimeWidget = {
    // Callback to trigger total calculation in main form
    onUpdate: null,
    useNative: false,

    init(updateCallback) {
        this.onUpdate = updateCallback;
        this.useNative = window.matchMedia('(max-width: 1024px)').matches;
        this.applyInputMode();
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

        const raw = dbTime === null || dbTime === undefined ? '' : String(dbTime).trim();
        if (!raw || raw.toLowerCase() === 'null') {
            input.value = '';
            return;
        }

        const [h24, m] = raw.split(':');
        let h = parseInt(h24);
        if (Number.isNaN(h) || m === undefined) {
            input.value = '';
            return;
        }
        const mm = String(m).slice(0, 2).padStart(2, '0');
        const hh24 = String(h).padStart(2, '0');

        if (this.useNative || input.type === 'time') {
            input.value = `${hh24}:${mm}`;
            return;
        }

        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;

        const formatted = `${h.toString().padStart(2,'0')}:${mm} ${ampm}`;
        input.value = formatted;

        // Sync the dropdowns inside this wrapper
        this.syncDropdowns(input, h.toString().padStart(2,'0'), mm, ampm);
    },

    /**
     * Get the 24h DB format string for an input ID
     * Returns "13:30" or null
     */
    get(id) {
        const input = document.getElementById(id);
        if (!input) return null;
        const val = input.value;
        if (input.type === 'time') {
            return val ? String(val).slice(0, 5) : null;
        }
        return this.parseTo24Hour(val);
    },

    // --- INTERNAL LOGIC ---

    setupWidgets() {
        if (this.useNative) {
            document.querySelectorAll('.time-popover').forEach((el) => {
                el.style.display = 'none';
            });
            return;
        }

        document.querySelectorAll('.time-widget-wrapper').forEach(wrapper => {
            const input = wrapper.querySelector('input');
            const popover = wrapper.querySelector('.time-popover');
            const selects = {
                h: wrapper.querySelector('.time-select-h'),
                m: wrapper.querySelector('.time-select-m'),
                ampm: wrapper.querySelector('.time-select-ampm')
            };
            selects.h.tabIndex = -1;
            selects.m.tabIndex = -1;
            selects.ampm.tabIndex = -1;

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

                // Close the popover when focus leaves this time widget (e.g. Tab to next field).
                window.setTimeout(() => {
                    const active = document.activeElement;
                    if (!wrapper.contains(active)) {
                        popover.style.display = 'none';
                    }
                }, 0);
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

    applyInputMode() {
        document.querySelectorAll('.time-widget-wrapper').forEach((wrapper) => {
            const input = wrapper.querySelector('input');
            const popover = wrapper.querySelector('.time-popover');
            if (!input || !popover) return;

            if (this.useNative) {
                input.type = 'time';
                input.step = '60';
                input.placeholder = '';
                popover.style.display = 'none';

                if (!input.dataset.tmNativeBound) {
                    const trigger = () => {
                        if (this.onUpdate) this.onUpdate();
                    };
                    input.addEventListener('input', trigger);
                    input.addEventListener('change', trigger);
                    input.addEventListener('blur', trigger);
                    input.dataset.tmNativeBound = '1';
                }
            } else {
                input.type = 'text';
                input.removeAttribute('step');
                if (!input.placeholder) {
                    input.placeholder = 'e.g. 8:00 AM';
                }
            }
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
