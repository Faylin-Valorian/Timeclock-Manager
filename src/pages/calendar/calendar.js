<<<<<<< HEAD
// [FIXED] Import TmClient instead of TmAPI
import { TmClient } from 'src/api/api.js'; 
import { CalendarModule } from './js/fullcalendar.js';
import { TabsModule } from './js/tabs.js';
import { TimesheetModule } from '../timesheet/timesheet.js';

// Global Namespace
window.TimeclockManager = window.TimeclockManager || {};

// [FIXED] Attach the new client (Optional, good for debugging)
window.TimeclockManager.Client = TmClient; 
window.TimeclockManager.CalendarInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Initialize Modules
    TabsModule.init();
    TimesheetModule.init();

    // 2. Initialize Calendar
    const calContainer = document.getElementById('calendar');
    if (calContainer) {
        CalendarModule.init(calContainer);
        
        // 3. Link Calendar Events to Module
        CalendarModule.onEventClick = (info) => {
            const id = info.event.id;
            const date = info.event.startStr.split('T')[0];
            TimesheetModule.open(date, id);
        };

        CalendarModule.onDateClick = (info) => {
            TimesheetModule.open(info.dateStr, null);
        };
    }
=======
// [FIXED] Import TmClient instead of TmAPI
import { TmClient } from 'src/api/api.js'; 
import { CalendarModule } from './js/fullcalendar.js';
import { TabsModule } from './js/tabs.js';
import { TimesheetModule } from '../timesheet/timesheet.js';

// Global Namespace
window.TimeclockManager = window.TimeclockManager || {};

// [FIXED] Attach the new client (Optional, good for debugging)
window.TimeclockManager.Client = TmClient; 
window.TimeclockManager.CalendarInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Initialize Modules
    TabsModule.init();
    TimesheetModule.init();

    // 2. Initialize Calendar
    const calContainer = document.getElementById('calendar');
    if (calContainer) {
        CalendarModule.init(calContainer);
        
        // 3. Link Calendar Events to Module
        CalendarModule.onEventClick = (info) => {
            const id = info.event.id;
            const date = info.event.startStr.split('T')[0];
            TimesheetModule.open(date, id);
        };

        CalendarModule.onDateClick = (info) => {
            TimesheetModule.open(info.dateStr, null);
        };
    }
>>>>>>> 6edf4541514ac05df3faedfa2f961d65a5495869
});