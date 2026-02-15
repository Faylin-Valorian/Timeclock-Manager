import { generateUrl } from '@nextcloud/router';
import { StechAPI } from 'src/api/api.js';
import { Calendar } from './modules/calendar/calendar.js';
// [IMPORTANT] Import EntryForm correctly
import { EntryForm } from './modules/entryform/entryform.js'; 
import { TimeSplitWidget } from './modules/widgets/time-widget.js';
// [IMPORTANT] Update path to rows.js (it is now in entryform/)
import { ActivityRows } from './modules/entryform/rows.js';

// Global Namespace Setup
window.StechTimesheet = window.StechTimesheet || {};
window.StechTimesheet.API = StechAPI;
// [IMPORTANT] Register EntryForm globally so Calendar can access it
window.StechTimesheet.EntryForm = EntryForm; 
window.StechTimesheet.Calendar = Calendar;
window.StechTimesheet.ActivityRows = ActivityRows;
window.StechTimesheet.state = {
    jobs: [],
    stateMap: {},
    stateMapRev: {}
};

document.addEventListener('DOMContentLoaded', async () => {
    
    // 1. Handle Impersonation (Persistent Logic)
    handleImpersonation();

    // 2. Load Initial Data (Jobs & States)
    await loadAttributes();

    // 3. Initialize Components
    TimeSplitWidget.init(); 
    
    // [IMPORTANT] Initialize EntryForm (was previously Form.init())
    EntryForm.init(); 
    
    Calendar.init(document.getElementById('calendar'));

    // 4. Setup Sidebar Navigation Links
    setupSidebarLinks();
});

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
    
    const btn = document.getElementById('btn-end-impersonation');
    if (btn) {
        btn.addEventListener('click', () => {
            sessionStorage.removeItem('stech_impersonate');
            const url = new URL(window.location.href);
            url.searchParams.delete('target_user');
            window.location.href = url.toString();
        });
    }
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

/**
 * Logic to handle sidebar links (Admin, Analysis) using Nextcloud Router
 */
function setupSidebarLinks() {
    const navLinks = document.querySelectorAll('#app-navigation a');
    
    navLinks.forEach(link => {
        const text = link.innerText.toLowerCase();
        const href = link.getAttribute('href') || '';

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