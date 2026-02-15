<<<<<<< HEAD
import { Calendar as FullCalendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
// [UPDATED] Import the specific Calendar API service
import { CalendarAPI } from './api.js';

export const CalendarModule = {
    instance: null,
    archiveMode: 0, // 0 = Active, 1 = Archived
    
    // External Callbacks (Assigned by calendar.js)
    onEventClick: null,
    onDateClick: null,

    init(el) {
        if (!el) return;

        this.instance = new FullCalendar(el, {
            plugins: [dayGridPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            firstDay: 0, // Sunday
            headerToolbar: false, // We use the external Sidebar Tabs
            height: '100%',
            weekNumbers: true,
            navLinks: false, // We handle navigation externally
            editable: false, // Drag & Drop disabled for now

            // --- DATA SOURCES ---
            eventSources: [
                {
                    // Timesheets Source
                    events: (info, success, failure) => {
                        CalendarAPI.getTimesheets(info.startStr, info.endStr, this.archiveMode)
                            .then(data => success(data))
                            .catch(err => {
                                console.error('Failed to fetch timesheets', err);
                                failure(err);
                            });
                    }
                },
                {
                    // Holidays Source
                    events: (info, success, failure) => {
                        CalendarAPI.getHolidays(info.startStr, info.endStr)
                            .then(data => this.processHolidays(data, success))
                            .catch(err => {
                                console.warn('Failed to fetch holidays', err);
                                success([]); // Fail silently so calendar still renders
                            });
                    }
                }
            ],

            // --- RENDER HOOKS ---
            eventContent: (arg) => {
                // Custom Event Styling (The "Tab" look)
                let div = document.createElement('div');
                div.className = 'fc-event-content-box';
                div.innerText = arg.event.title;
                
                // Visual distinction for background events vs interactive events
                if (arg.event.display !== 'background') {
                    div.style.backgroundColor = arg.event.backgroundColor;
                } else {
                    div.classList.add('fc-bg-text'); 
                }
                return { domNodes: [div] };
            },

            eventDidMount: (info) => {
                // Fix for background events blocking clicks if needed
                if (info.event.display === 'background') {
                    info.el.style.pointerEvents = 'none';
                }
            },

            datesSet: (info) => {
                // Update the Sidebar Date Label (e.g. "February 2026")
                const label = document.getElementById('current-date-label');
                if (label) label.innerText = info.view.title;
            },

            // --- INTERACTION HOOKS ---
            eventClick: (info) => {
                const props = info.event.extendedProps;
                
                // Ignore clicks on background events (Holidays/Payroll)
                if (props.isVisual || info.event.display === 'background') return;
                
                // Call external handler (opens the modal)
                if (this.onEventClick) {
                    this.onEventClick(info);
                }
            },

            dateClick: (info) => {
                // Call external handler (opens modal for new entry)
                if (this.onDateClick) {
                    this.onDateClick(info);
                }
            },

            windowResize: () => {
                this.instance.render();
            }
        });

        this.instance.render();
        
        // Expose instance globally so Tabs/Sidebar can control it
        window.TimeclockManager.CalendarInstance = this.instance;
    },

    /**
     * Helper: Process Holiday Data into FullCalendar Events
     */
    processHolidays(data, callback) {
        if (!Array.isArray(data)) {
            callback([]);
            return;
        }

        const events = data.map(h => {
            return {
                id: 'holiday-' + (h.id || Math.random()),
                title: h.name || h.holiday_name,
                start: h.start || h.holiday_start_date,
                end: h.end || h.holiday_end_date, 
                display: 'background',
                backgroundColor: this.hexToRgba(h.bg || '#e67e22', 0.2),
                extendedProps: { isVisual: true }
            };
        });
        callback(events);
    },

    /**
     * Utility: Convert Hex to RGBA for transparent background events
     */
    hexToRgba(hex, opacity) {
        let c;
        if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
            c = hex.substring(1).split('');
            if(c.length === 3) {
                c = [c[0], c[0], c[1], c[1], c[2], c[2]];
            }
            c = '0x' + c.join('');
            return 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+','+opacity+')';
        }
        return 'rgba(0,0,0,'+opacity+')'; // Fallback
    }
=======
import { Calendar as FullCalendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
// [UPDATED] Import the specific Calendar API service
import { CalendarAPI } from './api.js';

export const CalendarModule = {
    instance: null,
    archiveMode: 0, // 0 = Active, 1 = Archived
    
    // External Callbacks (Assigned by calendar.js)
    onEventClick: null,
    onDateClick: null,

    init(el) {
        if (!el) return;

        this.instance = new FullCalendar(el, {
            plugins: [dayGridPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            firstDay: 0, // Sunday
            headerToolbar: false, // We use the external Sidebar Tabs
            height: '100%',
            weekNumbers: true,
            navLinks: false, // We handle navigation externally
            editable: false, // Drag & Drop disabled for now

            // --- DATA SOURCES ---
            eventSources: [
                {
                    // Timesheets Source
                    events: (info, success, failure) => {
                        CalendarAPI.getTimesheets(info.startStr, info.endStr, this.archiveMode)
                            .then(data => success(data))
                            .catch(err => {
                                console.error('Failed to fetch timesheets', err);
                                failure(err);
                            });
                    }
                },
                {
                    // Holidays Source
                    events: (info, success, failure) => {
                        CalendarAPI.getHolidays(info.startStr, info.endStr)
                            .then(data => this.processHolidays(data, success))
                            .catch(err => {
                                console.warn('Failed to fetch holidays', err);
                                success([]); // Fail silently so calendar still renders
                            });
                    }
                }
            ],

            // --- RENDER HOOKS ---
            eventContent: (arg) => {
                // Custom Event Styling (The "Tab" look)
                let div = document.createElement('div');
                div.className = 'fc-event-content-box';
                div.innerText = arg.event.title;
                
                // Visual distinction for background events vs interactive events
                if (arg.event.display !== 'background') {
                    div.style.backgroundColor = arg.event.backgroundColor;
                } else {
                    div.classList.add('fc-bg-text'); 
                }
                return { domNodes: [div] };
            },

            eventDidMount: (info) => {
                // Fix for background events blocking clicks if needed
                if (info.event.display === 'background') {
                    info.el.style.pointerEvents = 'none';
                }
            },

            datesSet: (info) => {
                // Update the Sidebar Date Label (e.g. "February 2026")
                const label = document.getElementById('current-date-label');
                if (label) label.innerText = info.view.title;
            },

            // --- INTERACTION HOOKS ---
            eventClick: (info) => {
                const props = info.event.extendedProps;
                
                // Ignore clicks on background events (Holidays/Payroll)
                if (props.isVisual || info.event.display === 'background') return;
                
                // Call external handler (opens the modal)
                if (this.onEventClick) {
                    this.onEventClick(info);
                }
            },

            dateClick: (info) => {
                // Call external handler (opens modal for new entry)
                if (this.onDateClick) {
                    this.onDateClick(info);
                }
            },

            windowResize: () => {
                this.instance.render();
            }
        });

        this.instance.render();
        
        // Expose instance globally so Tabs/Sidebar can control it
        window.TimeclockManager.CalendarInstance = this.instance;
    },

    /**
     * Helper: Process Holiday Data into FullCalendar Events
     */
    processHolidays(data, callback) {
        if (!Array.isArray(data)) {
            callback([]);
            return;
        }

        const events = data.map(h => {
            return {
                id: 'holiday-' + (h.id || Math.random()),
                title: h.name || h.holiday_name,
                start: h.start || h.holiday_start_date,
                end: h.end || h.holiday_end_date, 
                display: 'background',
                backgroundColor: this.hexToRgba(h.bg || '#e67e22', 0.2),
                extendedProps: { isVisual: true }
            };
        });
        callback(events);
    },

    /**
     * Utility: Convert Hex to RGBA for transparent background events
     */
    hexToRgba(hex, opacity) {
        let c;
        if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
            c = hex.substring(1).split('');
            if(c.length === 3) {
                c = [c[0], c[0], c[1], c[1], c[2], c[2]];
            }
            c = '0x' + c.join('');
            return 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+','+opacity+')';
        }
        return 'rgba(0,0,0,'+opacity+')'; // Fallback
    }
>>>>>>> 6edf4541514ac05df3faedfa2f961d65a5495869
};