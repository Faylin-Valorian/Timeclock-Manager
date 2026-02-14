import { StechAPI } from '../../../../api/api.js';

export const LocationAdmin = {
    states: [],
    counties: [],
    selectedState: null,

    load() {
        this.bindEvents();
        this.loadStates();
    },
    
    // Alias for the main Admin loader to call if needed
    loadStates() {
        this.fetchStates();
    },

    bindEvents() {
        if (this._eventsBound) return;
        
        // Search inputs
        document.getElementById('state-search-input')?.addEventListener('input', () => this.renderStates());
        document.getElementById('county-search-input')?.addEventListener('input', () => this.renderCounties());

        // Radio filters
        document.querySelectorAll('input[name="state-status"]').forEach(r => r.addEventListener('change', () => this.renderStates()));
        document.querySelectorAll('input[name="county-status"]').forEach(r => r.addEventListener('change', () => this.renderCounties()));

        this._eventsBound = true;
    },

    async fetchStates() {
        try {
            this.states = await StechAPI.admin.getStates();
            this.renderStates();
        } catch(e) { console.error(e); }
    },

    renderStates() {
        const container = document.getElementById('state-list');
        if (!container) return;

        const filter = document.querySelector('input[name="state-status"]:checked')?.value || 'enabled';
        const search = document.getElementById('state-search-input')?.value.toLowerCase() || '';

        const filtered = this.states.filter(s => {
            const matchesSearch = s.state_name.toLowerCase().includes(search);
            const isEnabled = s.enabled == 1;
            if (!matchesSearch) return false;
            return filter === 'enabled' ? isEnabled : !isEnabled;
        });

        container.innerHTML = filtered.map(s => `
            <div class="list-item ${this.selectedState === s.state_abbr ? 'active' : ''}" 
                 onclick="window.LocationAdmin.selectState('${s.state_abbr}', '${s.state_name}')">
                <div style="flex-grow:1;">${s.state_name}</div>
                <label class="switch" onclick="event.stopPropagation()">
                    <input type="checkbox" ${s.enabled == 1 ? 'checked' : ''} 
                           onchange="window.LocationAdmin.toggleState(${s.id}, this.checked)">
                    <span class="slider round"></span>
                </label>
            </div>
        `).join('');
    },

    async selectState(abbr, name) {
        this.selectedState = abbr;
        document.getElementById('county-header').textContent = `Counties (${name})`;
        document.getElementById('county-search-input').disabled = false;
        
        // Visual highlight update
        this.renderStates(); 

        try {
            this.counties = await StechAPI.admin.getCounties(abbr);
            this.renderCounties();
        } catch(e) { console.error(e); }
    },

    renderCounties() {
        const container = document.getElementById('county-list');
        if (!container) return;
        
        const filter = document.querySelector('input[name="county-status"]:checked')?.value || 'enabled';
        const search = document.getElementById('county-search-input')?.value.toLowerCase() || '';

        const filtered = this.counties.filter(c => {
            const matchesSearch = c.county_name.toLowerCase().includes(search);
            const isEnabled = c.enabled == 1;
            if (!matchesSearch) return false;
            return filter === 'enabled' ? isEnabled : !isEnabled;
        });

        container.innerHTML = filtered.map(c => `
            <div class="list-item">
                <div style="flex-grow:1;">${c.county_name}</div>
                <label class="switch">
                    <input type="checkbox" ${c.enabled == 1 ? 'checked' : ''} 
                           onchange="window.LocationAdmin.toggleCounty(${c.id}, this.checked)">
                    <span class="slider round"></span>
                </label>
            </div>
        `).join('');
    },

    async toggleState(id, isChecked) {
        await StechAPI.admin.toggleState(id, isChecked);
        // Update local state model
        const s = this.states.find(x => x.id == id);
        if(s) s.enabled = isChecked ? 1 : 0;
        this.renderStates();
    },

    async toggleCounty(id, isChecked) {
        await StechAPI.admin.toggleCounty(id, isChecked);
        // Update local state model
        const c = this.counties.find(x => x.id == id);
        if(c) c.enabled = isChecked ? 1 : 0;
        this.renderCounties();
    }
};

window.LocationAdmin = LocationAdmin;