// =============================================================================
// ADMIN MODULE REGISTRY
// To remove a module's logic, comment out its import and registry line.
// =============================================================================

import { Sidebar } from '../../components/sidebar/sidebar.js';
import { UserAdmin } from './modules/users/users.js';
import { AccessAdmin } from './modules/access/access.js';
import { PayrollAdmin } from './modules/payroll/payroll.js';
import { HolidayAdmin } from './modules/holidays/holidays.js';
import { JobAdmin } from './modules/jobs/jobs.js';
import { LocationAdmin } from './modules/locations/locations.js';

document.addEventListener('DOMContentLoaded', () => {

    // 1. CONFIG: Register Modules
    const modules = {
        'users':     { loader: UserAdmin,     navId: 'nav-users' },
        'access':    { loader: AccessAdmin,   navId: 'nav-access' },
        'payroll':   { loader: PayrollAdmin,  navId: 'nav-payroll' },
        'holidays':  { loader: HolidayAdmin,  navId: 'nav-holidays' },
        'jobs':      { loader: JobAdmin,      navId: 'nav-jobs' },
        'locations': { loader: LocationAdmin, navId: 'nav-locations' }
    };

    // 2. SECURITY: Clear Impersonation
    // We strictly ensure no one is impersonating another user while in Admin Mode
    sessionStorage.removeItem('stech_impersonate');

    // 3. INIT: Sidebar Navigation
    // We pass a callback to the sidebar to handle module switching
    Sidebar.init({
        onModuleChange: (moduleKey) => {
            switchAdminView(moduleKey);
        }
    });

    // 4. VIEW SWITCHER LOGIC
    function switchAdminView(viewId) {
        if (!modules[viewId]) return;

        // A. Hide all views
        document.querySelectorAll('.admin-view').forEach(el => el.classList.add('hidden'));
        
        // B. Show target view (if the HTML exists)
        const viewContainer = document.getElementById('view-' + viewId);
        if (viewContainer) {
            viewContainer.classList.remove('hidden');
        }

        // C. Trigger the Module's Load Function
        const module = modules[viewId].loader;
        if (module) {
            if (typeof module.load === 'function') module.load();
            else if (typeof module.render === 'function') module.render(); // Fallback
            else if (viewId === 'locations' && typeof module.loadStates === 'function') module.loadStates(); // Special case
        }
    }

    // 5. GLOBAL EVENTS (Filters & Toggles)
    bindGlobalEvents();

    // 6. INITIAL LOAD
    // Find the first available module to load by default
    const firstModule = Object.keys(modules).find(key => document.getElementById(modules[key].navId));
    if (firstModule) {
        // Manually trigger visual active state for first load
        document.getElementById(modules[firstModule].navId)?.classList.add('active');
        switchAdminView(firstModule);
    }
});

function bindGlobalEvents() {
    // Dropdown Filters (Generic handler for all modules)
    document.querySelectorAll('.btn-filter-icon').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const menuId = btn.id.replace('-btn', '-menu');
            document.getElementById(menuId)?.classList.toggle('hidden');
        });
    });

    // Close menus when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.filter-menu').forEach(menu => menu.classList.add('hidden'));
    });
}