import { Sidebar } from 'src/components/sidebar/sidebar.js';
import { CalendarModule } from './fullcalendar.js';
import { TimesheetModule } from 'src/pages/timesheet/timesheet.js';

export const TabsModule = {
    init() {
        Sidebar.init();
        this.setupViewTabs();
        this.setupNavTabs();
        this.setupDateControls();
    },

    setupViewTabs() {
        // 1. View Switching Buttons (Month, Week)
        const views = {
            'view-month': 'dayGridMonth',
            'view-week': 'dayGridWeek'
        };

        Object.keys(views).forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.addEventListener('click', (e) => {
                    const action = views[id];
                    const cal = window.TimeclockManager.CalendarInstance;
                    
                    if (cal) {
                        cal.changeView(action);
                        this.updateActiveButton(e.target);
                    }
                });
            }
        });

        // 2. Today Button Logic
        // Navigates to today AND opens the Timesheet for the current date
        const todayBtn = document.getElementById('view-today');
        if (todayBtn) {
            todayBtn.addEventListener('click', (e) => {
                const cal = window.TimeclockManager.CalendarInstance;
                if (cal) {
                    cal.today(); // Move calendar view to current date
                }

                // Generate local YYYY-MM-DD string
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const todayStr = `${year}-${month}-${day}`;

                // Open the modal
                TimesheetModule.open(todayStr, null);
            });
        }
    },

    updateActiveButton(target) {
        // Reset all buttons to secondary style
        document.querySelectorAll('.view-buttons button').forEach(b => {
            b.classList.remove('active');
            b.classList.remove('primary-button');
            b.classList.add('secondary-button');
        });
        
        // Set clicked button to primary style
        target.classList.add('active');
        target.classList.remove('secondary-button');
        target.classList.add('primary-button');
    },

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
                    
                    if (window.OC && window.OC.generateUrl) {
                        window.location.href = window.OC.generateUrl('/apps/timeclock-manager' + route);
                    } else {
                        window.location.href = '/index.php/apps/timeclock-manager' + route;
                    }
                });
            }
        });
    },

    setupDateControls() {
        const getCal = () => window.TimeclockManager.CalendarInstance;

        // Prev / Next Arrows
        document.getElementById('nav-prev')?.addEventListener('click', () => getCal()?.prev());
        document.getElementById('nav-next')?.addEventListener('click', () => getCal()?.next());
        
        // Hidden Date Picker Input
        const picker = document.getElementById('date-picker-input');
        if (picker) {
            picker.addEventListener('change', (e) => {
                const cal = getCal();
                if (cal && e.target.value) {
                    cal.gotoDate(e.target.value);
                }
            });
            
            // Text Label Trigger
            const label = document.getElementById('current-date-label');
            if (label) {
                label.addEventListener('click', () => {
                    if (picker.showPicker) {
                        picker.showPicker();
                    } else {
                        picker.focus();
                        picker.click();
                    }
                });
            }
        }
    }
};