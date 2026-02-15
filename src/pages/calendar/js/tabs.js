import { Sidebar } from 'src/components/sidebar/sidebar.js';
import { CalendarModule } from './fullcalendar.js';

export const TabsModule = {
    /**
     * Main Initialization
     */
    init() {
        // 1. Initialize shared sidebar logic (hides the active link based on data-mode)
        Sidebar.init();

        // 2. Setup Calendar specific controls
        this.setupViewTabs();
        this.setupNavTabs();
        this.setupDateControls();
    },

    /**
     * Handles the Month / Week / Today buttons
     */
    setupViewTabs() {
        const views = {
            'view-month': 'dayGridMonth',
            'view-week': 'dayGridWeek',
            'view-today': 'today'
        };

        Object.keys(views).forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.addEventListener('click', (e) => {
                    const action = views[id];
                    const cal = window.TimeclockManager.CalendarInstance;
                    
                    if (!cal) return;

                    if (action === 'today') {
                        cal.today();
                    } else {
                        cal.changeView(action);
                        
                        // Update Active State Visuals
                        document.querySelectorAll('.view-buttons button').forEach(b => {
                            b.classList.remove('active');
                            b.classList.remove('primary-button');
                            b.classList.add('secondary-button');
                        });
                        
                        // Set clicked button to active/primary
                        e.target.classList.add('active');
                        e.target.classList.remove('secondary-button');
                        e.target.classList.add('primary-button');
                    }
                });
            }
        });
    },

    /**
     * Handles the sidebar navigation links (Calendar, Analysis, Admin)
     */
    setupNavTabs() {
        const links = {
            'nav-link-calendar': '/',
            'nav-link-analysis': '/analysis',
            'nav-link-admin': '/admin'
        };

        Object.keys(links).forEach(id => {
            const link = document.getElementById(id);
            if (link) {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const route = links[id];
                    
                    // Use Nextcloud's URL generator if available
                    if (window.OC && window.OC.generateUrl) {
                        window.location.href = window.OC.generateUrl('/apps/timeclock-manager' + route);
                    } else {
                        // Fallback mostly for dev environments
                        window.location.href = '/index.php/apps/timeclock-manager' + route;
                    }
                });
            }
        });
    },

    /**
     * Handles the Date Navigation (< Prev, Next >, and Date Picker)
     */
    setupDateControls() {
        const getCal = () => window.TimeclockManager.CalendarInstance;

        // Previous Button
        document.getElementById('nav-prev')?.addEventListener('click', () => {
            const cal = getCal();
            if (cal) cal.prev();
        });

        // Next Button
        document.getElementById('nav-next')?.addEventListener('click', () => {
            const cal = getCal();
            if (cal) cal.next();
        });
        
        // Date Picker Input (Hidden month input)
        const picker = document.getElementById('date-picker-input');
        if (picker) {
            picker.addEventListener('change', (e) => {
                const cal = getCal();
                if (cal && e.target.value) {
                    cal.gotoDate(e.target.value);
                }
            });
            
            // Allow clicking the text label ("February 2026") to open the picker
            const label = document.getElementById('current-date-label');
            if (label) {
                label.addEventListener('click', () => {
                    if (picker.showPicker) {
                        picker.showPicker(); // Modern browsers
                    } else {
                        picker.focus(); // Fallback
                        picker.click();
                    }
                });
            }
        }
    }
};