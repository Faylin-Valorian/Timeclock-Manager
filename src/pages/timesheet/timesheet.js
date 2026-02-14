// 1. Core Imports
import { generateUrl } from '@nextcloud/router';
import { StechAPI } from 'src/api/api.js'; // Ensure path matches your structure

// 2. Module Imports
import { Calendar } from './modules/calendar/calendar.js';
import { EntryForm } from './modules/entryform/entryform.js';
import { TimeSplitWidget } from './modules/widgets/time-widget.js';

// 3. Global Namespace Setup
window.StechTimesheet = window.StechTimesheet || {};
window.StechTimesheet.API = StechAPI;
window.StechTimesheet.Form = EntryForm;
window.StechTimesheet.Calendar = Calendar;
window.StechTimesheet.state = {
    jobs: [],
    stateMap: {},
    stateMapRev: {}
};

// 4. Main Initialization
document.addEventListener('DOMContentLoaded', async () => {
    
    // A. Handle URL/Session Impersonation
    handleImpersonation();

    // B. Load Data (Jobs, States)
    await loadAttributes();

    // C. Initialize Modules
    TimeSplitWidget.init();
    Form.init();
    Calendar.init(document.getElementById('calendar'));

    // D. Setup Sidebar Navigation Links
    setupSidebarLinks();
});

/**
 * Logic to handle sidebar links (Admin, Analysis) using Nextcloud Router
 */
function setupSidebarLinks() {
    const navLinks = document.querySelectorAll('#app-navigation a');
    
    navLinks.forEach(link => {
        const text = link.innerText.toLowerCase();
        const href = link.getAttribute('href') || '';

        // If it's a link to Admin or Analysis, hijack it to use generateUrl
        // This ensures proper routing within Nextcloud
        if (text.includes('admin') || href.includes('admin')) {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = generateUrl('/apps/timeclock-manager/admin');
            });
        }

        if (text.includes('analysis') || href.includes('analysis')) {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = generateUrl('/apps/timeclock-manager/analysis');
            });
        }
    });
}

/**
 * Handle persistent impersonation state
 */
function handleImpersonation() {
    const storedTarget = sessionStorage.getItem('stech_impersonate');
    const urlParams = new URLSearchParams(window.location.search);
    const currentTarget = urlParams.get('target_user');

    if (storedTarget && storedTarget !== currentTarget) {
        urlParams.set('target_user', storedTarget);
        window.location.search = urlParams.toString();
        return;
    }
    
    document.getElementById('btn-end-impersonation')?.addEventListener('click', () => {
        sessionStorage.removeItem('stech_impersonate');
        const url = new URL(window.location.href);
        url.searchParams.delete('target_user');
        window.location.href = url.toString();
    });
}

/**
 * Load Initial Attributes from API
 */
async function loadAttributes() {
    try {
        const attributes = await StechAPI.getAttributes();
        window.StechTimesheet.state.jobs = attributes.jobs || [];
        
        attributes.states.forEach(s => {
            window.StechTimesheet.state.stateMap[s.state_name] = s.state_abbr;
            window.StechTimesheet.state.stateMapRev[s.state_abbr] = s.state_name;
        });

        // Populate Datalist for States
        const stateDatalist = document.getElementById('state-options');
        if (stateDatalist) {
            stateDatalist.innerHTML = ''; 
            attributes.states.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.state_name;
                stateDatalist.appendChild(opt);
            });
        }
    } catch (e) {
        console.error("Failed to load attributes", e);
    }
}