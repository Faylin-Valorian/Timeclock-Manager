import { Calendar as FullCalendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

export const Calendar = {
    instance: null,
    archiveMode: 0, // 0 = Active, 1 = Archived

    init(el) {
        this.instance = new FullCalendar(el, {
            plugins: [dayGridPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            firstDay: 0, 
            headerToolbar: false, 
            height: '100%',
            weekNumbers: true,
            
            eventSources: [
                // SOURCE 1: Timesheets
                {
                    events: (info, successCallback, failureCallback) => {
                        window.StechTimesheet.API.getTimesheets(info.startStr, info.endStr, this.archiveMode)
                            .then(data => successCallback(data))
                            .catch(err => failureCallback(err));
                    }
                },
                // SOURCE 2: Holidays
                {
                    events: (info, successCallback, failureCallback) => {
                        window.StechTimesheet.API.request('get', '/api/calendar/holidays', {
                            start: info.startStr,
                            end: info.endStr
                        }).then(data => {
                            const events = this.processHolidays(data);
                            successCallback(events);
                        }).catch(err => {
                            console.warn('Failed to fetch holidays', err);
                            successCallback([]);
                        });
                    }
                }
            ],
            
            // Render Background Events (Holidays/Payroll)
            eventDidMount: (info) => {
                if (info.event.display === 'background') {
                    const customBg = info.event.extendedProps.customBg;
                    if (customBg && customBg.trim() !== '') {
                        info.el.style.background = customBg;
                        if (customBg.includes('url(')) {
                            info.el.style.backgroundSize = 'cover';
                            info.el.style.backgroundPosition = 'center';
                        }
                    } else {
                        info.el.style.backgroundColor = info.event.backgroundColor;
                    }
                    info.el.style.pointerEvents = 'none'; 
                    info.el.style.zIndex = '0';
                }
            },

            // Render Text Content (Fix Overlaps)
            eventContent: (arg) => {
                let div = document.createElement('div');
                div.className = 'fc-event-content-box'; 
                div.innerText = arg.event.title;

                if (arg.event.display === 'background') {
                    div.classList.add('fc-bg-text'); // Class for styling in SCSS
                } else {
                    div.style.backgroundColor = arg.event.backgroundColor;
                    div.style.padding = '2px 4px';
                }
                return { domNodes: [div] };
            },

            // Click Event (Open Form)
            eventClick: (info) => {
                const props = info.event.extendedProps;
                if (props.isVisual || props.is_visual || info.event.display === 'background') return;

                window.StechTimesheet.API.getTimesheetDetails(info.event.id)
                    .then(data => {
                        if (window.StechTimesheet.Form) {
                            window.StechTimesheet.Form.open(data.timesheet_date, data.timesheet_id);
                        }
                    })
                    .catch(err => console.error('Failed to load record details:', err));
            },

            // Date Click (New Entry)
            dateClick: (info) => {
                const formEl = document.getElementById('timesheet-form');
                if (formEl) {
                    formEl.reset();
                    // Clear hidden inputs for widgets
                    formEl.querySelectorAll('.combined-time-input').forEach(input => input.value = '');
                }

                if (window.StechTimesheet.Form) {
                    window.StechTimesheet.Form.open(info.dateStr, null);
                }
            },

            datesSet: (info) => {
                const titleEl = document.getElementById('current-date-label');
                if (titleEl) titleEl.innerText = info.view.title;
            },

            windowResize: () => {
                this.instance.render();
            }
        });

        this.instance.render();
        this.setupNavigation();
        this.setupArchiveFilter();
    },

    // Helper: Holiday Processing
    processHolidays(data) {
        const events = [];
        data.forEach(h => {
            const startDate = new Date(h.start || h.holiday_start_date);
            let limitDate = h.end || h.holiday_end_date 
                ? new Date(h.end || h.holiday_end_date) 
                : new Date(startDate);
            
            // Adjust end date logic
            if (limitDate <= startDate) {
                limitDate = new Date(startDate);
            }
            limitDate.setUTCDate(limitDate.getUTCDate() + 1);

            const rawColor = h.holiday_bg || h.bg || '#e67e22';
            const overlayColor = this.hexToRgba(rawColor, 0.2);

            const loopDate = new Date(startDate);
            while (loopDate < limitDate) {
                const dateStr = loopDate.toISOString().split('T')[0];
                events.push({
                    id: 'holiday-' + (h.id || Math.random()) + '-' + dateStr,
                    title: h.name || h.holiday_name,
                    start: dateStr,
                    display: 'background',
                    backgroundColor: overlayColor, 
                    extendedProps: { isVisual: true, customBg: '' }
                });
                loopDate.setUTCDate(loopDate.getUTCDate() + 1);
            }
        });
        return events;
    },

    hexToRgba(hex, opacity) {
        if (!hex) return `rgba(230, 126, 34, ${opacity})`;
        let c;
        if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
            c = hex.substring(1).split('');
            if(c.length === 3) c = [c[0], c[0], c[1], c[1], c[2], c[2]];
            c = '0x'+c.join('');
            return 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+','+opacity+')';
        }
        return hex;
    },

    setupNavigation() {
        document.getElementById('nav-prev')?.addEventListener('click', () => this.instance.prev());
        document.getElementById('nav-next')?.addEventListener('click', () => this.instance.next());
        
        document.getElementById('view-month')?.addEventListener('click', (e) => {
            this.instance.changeView('dayGridMonth');
            this.toggleActiveButton(e.target);
        });
        document.getElementById('view-week')?.addEventListener('click', (e) => {
            this.instance.changeView('dayGridWeek');
            this.toggleActiveButton(e.target);
        });
        document.getElementById('view-today')?.addEventListener('click', () => this.instance.today());

        document.getElementById('date-picker-input')?.addEventListener('change', (e) => {
            if (e.target.value) this.instance.gotoDate(e.target.value);
        });
    },

    setupArchiveFilter() {
        const btn = document.getElementById('toggle-archive-view');
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.archiveMode = (this.archiveMode === 0) ? 1 : 0;
                
                const textSpan = btn.querySelector('span:not([class*="icon"])');
                const iconSpan = btn.querySelector('span[class*="icon"]');
                
                if (this.archiveMode === 1) {
                    btn.classList.add('active', 'primary-button');
                    btn.classList.remove('secondary-button');
                    if(textSpan) textSpan.innerText = "Back to Active";
                    if(iconSpan) iconSpan.className = 'icon-history';
                } else {
                    btn.classList.remove('active', 'primary-button');
                    btn.classList.add('secondary-button');
                    if(textSpan) textSpan.innerText = "Show Archived";
                    if(iconSpan) iconSpan.className = 'icon-filter';
                }
                this.refresh();
            });
        }
    },

    toggleActiveButton(activeBtn) {
        document.querySelectorAll('.view-buttons button').forEach(btn => btn.classList.remove('active'));
        activeBtn.classList.add('active');
    },

    refresh() {
        if(this.instance) this.instance.refetchEvents();
    }
};