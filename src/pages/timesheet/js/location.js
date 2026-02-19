import { TimesheetAPI } from './api.js';
import { SearchableDropdown } from './searchableDropdown.js';

export const LocationManager = {
    states: [],
    counties: [],

    init() {
        this.setupDropdowns();
        this.setupListeners();
    },

    setupDropdowns() {
        const stateInput = document.getElementById('travel-state');
        const countyInput = document.getElementById('travel-county');
        if (!stateInput || !countyInput) return;

        SearchableDropdown.attach(stateInput, {
            freeform: true,
            getOptions: () => this.states.map((s) => ({
                value: s.name,
                label: s.name,
                search: `${s.name} ${s.abbr}`
            }))
        });

        SearchableDropdown.attach(countyInput, {
            freeform: true,
            getOptions: () => this.counties.map((c) => ({
                value: c,
                label: c,
                search: c
            }))
        });
    },

    setupListeners() {
        const stateInput = document.getElementById('travel-state');
        if (!stateInput) return;

        // Trigger lookup on change or blur
        const handleLookup = async (e) => {
            const abbr = this.resolveStateAbbr(e.target.value);
            if (abbr) {
                await this.loadCounties(abbr);
            } else {
                this.counties = [];
                SearchableDropdown.refresh(document.getElementById('travel-county'));
            }
        };

        stateInput.addEventListener('change', handleLookup);
        stateInput.addEventListener('blur', handleLookup);
    },

    setStates(states) {
        this.states = (states || []).map((s) => ({
            abbr: String(s?.state_abbr || '').toUpperCase(),
            name: String(s?.state_name || ''),
            label: String(s?.state_name || '')
        })).filter((s) => !!s.abbr && !!s.name);
        SearchableDropdown.refresh(document.getElementById('travel-state'));
    },

    /**
     * Pre-fills the location fields and loads the county list
     */
    async populate(state, county) {
        const normalizedState = this.resolveStateName(state || '');
        document.getElementById('travel-state').value = normalizedState;
        document.getElementById('travel-county').value = county || '';

        // If a state is already saved, load the counties immediately so the list is ready
        const abbr = this.resolveStateAbbr(state || normalizedState);
        if (abbr) {
            await this.loadCounties(abbr);
        }
    },

    reset() {
        document.getElementById('travel-state').value = '';
        document.getElementById('travel-county').value = '';
        this.counties = [];
        SearchableDropdown.refresh(document.getElementById('travel-state'));
        SearchableDropdown.refresh(document.getElementById('travel-county'));
    },

    /**
     * API Call -> Datalist Update
     */
    async loadCounties(stateAbbr) {
        const counties = await TimesheetAPI.getCounties(stateAbbr);
        this.counties = (counties || [])
            .map((item) => String(item?.county_name || '').trim())
            .filter(Boolean);
        SearchableDropdown.refresh(document.getElementById('travel-county'));
    },

    resolveStateAbbr(rawValue) {
        const raw = String(rawValue || '').trim();
        if (!raw) return '';

        if (raw.length === 2) {
            const fromAbbr = this.states.find((s) => s.abbr === raw.toUpperCase());
            return fromAbbr ? fromAbbr.abbr : raw.toUpperCase();
        }

        const found = this.states.find((s) =>
            s.name.toLowerCase() === raw.toLowerCase() ||
            s.label.toLowerCase() === raw.toLowerCase()
        );
        return found ? found.abbr : '';
    },

    resolveStateName(rawValue) {
        const raw = String(rawValue || '').trim();
        if (!raw) return '';

        const byAbbr = this.states.find((s) => s.abbr === raw.toUpperCase());
        if (byAbbr) return byAbbr.name;

        const byName = this.states.find((s) => s.name.toLowerCase() === raw.toLowerCase());
        return byName ? byName.name : raw;
    }
};
