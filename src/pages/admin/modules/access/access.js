import { StechAPI } from '../../../../api/api.js';

export const AccessAdmin = {
    groups: [],
    access: {},

    load() {
        console.log("Loading Access Module...");
        this.bindEvents();
        this.fetchData();
    },

    bindEvents() {
        if (this._eventsBound) return;
        
        // Tab Switching (General / Admin / Analysis)
        document.querySelectorAll('.access-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.access-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const target = tab.getAttribute('data-target');
                document.querySelectorAll('.access-group-panel').forEach(p => p.classList.add('hidden'));
                document.getElementById(target)?.classList.remove('hidden');
            });
        });
        
        this._eventsBound = true;
    },

    async fetchData() {
        try {
            // Parallel fetch for speed
            const [groups, access] = await Promise.all([
                StechAPI.admin.getGroups(),
                StechAPI.admin.getAccess()
            ]);
            this.groups = groups || [];
            this.access = access || {};
            this.render();
        } catch (e) {
            console.error("Access data load failed", e);
        }
    },

    render() {
        // Map of UI container IDs to database permission keys
        const map = {
            'list-access-archive': 'can_toggle_archive',
            'list-access-admin-global': 'can_view_admin',
            'list-access-admin-access': 'admin_access',
            'list-access-admin-users': 'admin_users',
            'list-access-admin-payroll': 'admin_payroll',
            'list-access-admin-holidays': 'admin_holidays',
            'list-access-admin-jobs': 'admin_jobs',
            'list-access-admin-locations': 'admin_locations',
            'list-access-analysis-tab': 'can_view_analysis',
            'list-access-analysis-others': 'analysis_view_others',
            'list-access-analysis-travel': 'analysis_travel',
            'list-access-analysis-financial': 'analysis_financial',
            'list-access-analysis-location': 'analysis_location',
            'list-access-analysis-jobs': 'analysis_jobs'
        };

        for (const [elemId, configKey] of Object.entries(map)) {
            const container = document.getElementById(elemId);
            if (container) {
                this.renderGroupList(container, configKey);
            }
        }
    },

    renderGroupList(container, configKey) {
        // Current permitted groups for this setting
        const permitted = this.access[configKey] || [];

        container.innerHTML = this.groups.map(g => {
            const isChecked = permitted.includes(g.gid) ? 'checked' : '';
            // We use a data attribute to track which setting this checkbox controls
            return `
                <label class="group-checkbox-row">
                    <input type="checkbox" 
                           data-config="${configKey}" 
                           value="${g.gid}" 
                           ${isChecked}
                           onchange="window.AccessAdmin.toggle('${configKey}', '${g.gid}', this.checked)">
                    <span>${g.displayName}</span>
                </label>
            `;
        }).join('');
    },

    async toggle(configKey, gid, isChecked) {
        // Optimistic UI update handled by checkbox natively
        try {
            await StechAPI.admin.saveAccess({
                key: configKey,
                gid: gid,
                action: isChecked ? 'add' : 'remove'
            });
        } catch (e) {
            alert("Failed to save permission.");
            // Revert checkbox if failed
            this.fetchData();
        }
    }
};

// Global helper for inline onchange
window.AccessAdmin = AccessAdmin;