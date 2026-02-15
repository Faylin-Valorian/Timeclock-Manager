import { TimesheetAPI } from './api.js';

export const LocationManager = {
    init() {
        this.setupListeners();
    },

    setupListeners() {
        const stateInput = document.getElementById('travel-state');
        if (!stateInput) return;

        // Trigger lookup on change or blur
        const handleLookup = async (e) => {
            const abbr = e.target.value;
            // Only search if it looks like a state code (2 chars)
            if (abbr && abbr.length === 2) {
                await this.loadCounties(abbr);
            } else {
                this.updateDatalist('county-options', []);
            }
        };

        stateInput.addEventListener('change', handleLookup);
        stateInput.addEventListener('blur', handleLookup);
    },

    /**
     * Pre-fills the location fields and loads the county list
     */
    async populate(state, county) {
        document.getElementById('travel-state').value = state || '';
        document.getElementById('travel-county').value = county || '';

        // If a state is already saved, load the counties immediately so the list is ready
        if (state) {
            await this.loadCounties(state);
        }
    },

    reset() {
        document.getElementById('travel-state').value = '';
        document.getElementById('travel-county').value = '';
        this.updateDatalist('county-options', []);
    },

    /**
     * API Call -> Datalist Update
     */
    async loadCounties(stateAbbr) {
        const counties = await TimesheetAPI.getCounties(stateAbbr);
        // Assumes API returns [{county_name: "Orange"}, ...]
        this.updateDatalist('county-options', counties, 'county_name'); 
    },

    /**
     * Generic helper to update any datalist
     */
    updateDatalist(elementId, items, labelKey = null) {
        const dl = document.getElementById(elementId);
        if (!dl) return;
        
        dl.innerHTML = '';
        items.forEach(item => {
            const opt = document.createElement('option');
            // If item is object, use labelKey. If string, use item itself.
            const val = labelKey ? (item[labelKey] || item.name || item) : item;
            opt.value = val;
            
            // Optional: Add label if available (e.g. for States: "CA" -> "California")
            if (item.state_name) opt.label = item.state_name;
            
            dl.appendChild(opt);
        });
    }
};