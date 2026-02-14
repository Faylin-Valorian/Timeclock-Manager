import { StechAPI } from '../../../../api/api.js';

export const UserAdmin = {
    users: [],

    load() {
        console.log("Loading User Module...");
        this.bindEvents();
        this.fetchUsers();
    },

    bindEvents() {
        // Prevent double binding
        if (this._eventsBound) return;
        
        // Search Input
        const searchInput = document.getElementById('user-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.render());
        }

        // Radio Filters
        document.querySelectorAll('input[name="user-status"]').forEach(radio => {
            radio.addEventListener('change', () => this.render());
        });

        this._eventsBound = true;
    },

    async fetchUsers() {
        try {
            // Using the shared API wrapper
            const response = await StechAPI.admin.getUsers();
            this.users = response || [];
            this.render();
        } catch (error) {
            console.error('Error fetching users:', error);
            const container = document.getElementById('user-grid-container');
            if(container) container.innerHTML = '<p class="error-msg">Failed to load users.</p>';
        }
    },

    render() {
        const container = document.getElementById('user-grid-container');
        if (!container) return;

        const filterVal = document.querySelector('input[name="user-status"]:checked')?.value || 'active';
        const searchVal = document.getElementById('user-search-input')?.value.toLowerCase() || '';

        // Filter Logic
        const filtered = this.users.filter(u => {
            const matchesSearch = (u.displayname || u.uid).toLowerCase().includes(searchVal);
            const isActive = u.is_active !== false && u.is_active !== 0 && u.is_active !== '0';
            
            if (!matchesSearch) return false;
            if (filterVal === 'active') return isActive;
            if (filterVal === 'inactive') return !isActive;
            return true; // 'all'
        });

        // Render HTML
        if (filtered.length === 0) {
            container.innerHTML = '<div class="empty-state">No employees found.</div>';
            return;
        }

        container.innerHTML = filtered.map(u => `
            <div class="user-card ${u.is_active ? '' : 'inactive'}">
                <div class="user-avatar-placeholder">${u.displayname.charAt(0)}</div>
                <div class="user-info">
                    <div class="user-name">${u.displayname}</div>
                    <div class="user-uid">${u.uid}</div>
                </div>
                <div class="user-actions">
                    <button class="action-btn" onclick="alert('View Timesheet for ${u.uid}')" title="View Timesheet">
                        <span class="icon-history"></span>
                    </button>
                    </div>
            </div>
        `).join('');
    }
};