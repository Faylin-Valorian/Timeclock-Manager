import { Sidebar } from 'src/components/sidebar/sidebar.js';
import { CalendarModule } from './fullcalendar.js';

export const TabsModule = {
    _lastClickByKey: {},

    isRapidClick(key, thresholdMs = 350) {
        const now = Date.now();
        const prev = this._lastClickByKey[key] || 0;
        this._lastClickByKey[key] = now;
        return (now - prev) < thresholdMs;
    },

    init() {
        Sidebar.init();
        this.setupViewTabs();
        this.setupArchiveToggle();
        this.setupNavTabs();
        this.setupDateControls();
        this.disableDoubleClickUI();
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
                    if (this.isRapidClick(`view:${id}`)) return;
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
                if (this.isRapidClick('view:today')) return;
                if (CalendarModule.archiveMode === 1) return;

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

                // Reuse Calendar's date-click flow so existing in-progress/per-diem
                // records open instead of always creating a new one.
                CalendarModule.onDateClick?.({
                    dateStr: todayStr,
                    source: 'todayButton'
                });
            });
        }
    },

    updateActiveButton(target) {
        // Reset all buttons to secondary style
        document.querySelectorAll('.view-buttons button:not(#toggle-archive-view)').forEach(b => {
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
            // Admin UI is an overlay in calendar mode; outside it, route to calendar.
            'nav-link-admin': '/'
        };
        const mode = document.getElementById('app-navigation')?.dataset?.mode || '';

        Object.keys(links).forEach(id => {
            const link = document.getElementById(id);
            if (link) {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (this.isRapidClick(`nav:${id}`)) return;

                    if (id === 'nav-link-analysis' && mode === 'calendar') {
                        const overlay = window.TimeclockManager?.Modules?.AnalysisOverlay;
                        overlay?.toggle?.();
                        return;
                    }

                    if (id === 'nav-link-admin' && mode === 'calendar') {
                        const overlay = window.TimeclockManager?.Modules?.AdminOverlay;
                        overlay?.toggle?.();
                        return;
                    }

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
        document.getElementById('nav-prev')?.addEventListener('click', () => {
            if (this.isRapidClick('nav:prev')) return;
            getCal()?.prev();
        });
        document.getElementById('nav-next')?.addEventListener('click', () => {
            if (this.isRapidClick('nav:next')) return;
            getCal()?.next();
        });
        
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
    },

    setupArchiveToggle() {
        const btn = document.getElementById('toggle-archive-view');
        if (!btn) return;

        const updateLabel = () => {
            const isArchiveMode = CalendarModule.archiveMode === 1;
            btn.innerHTML = '<span class="icon-filter"></span>';
            btn.title = isArchiveMode ? 'Show Active' : 'Show Archived';
            btn.classList.toggle('primary-button', isArchiveMode);
            btn.classList.toggle('secondary-button', !isArchiveMode);
        };

        btn.addEventListener('click', () => {
            if (this.isRapidClick('view:archive')) return;
            CalendarModule.archiveMode = CalendarModule.archiveMode === 1 ? 0 : 1;
            updateLabel();
            CalendarModule.instance?.refetchEvents();
        });

        updateLabel();
    },

    disableDoubleClickUI() {
        const selectors = [
            '.view-buttons button',
            '.nav-link',
            '#nav-prev',
            '#nav-next',
            '#current-date-label'
        ];
        document.querySelectorAll(selectors.join(',')).forEach((el) => {
            el.addEventListener('dblclick', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });
    }
};
