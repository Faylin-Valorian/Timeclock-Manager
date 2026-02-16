// Import TmClient
import { TmClient } from 'src/api/api.js'; 
import { CalendarModule } from './js/fullcalendar.js';
import { TabsModule } from './js/tabs.js';
import { TimesheetModule } from '../timesheet/timesheet.js';

// Global Namespace
window.TimeclockManager = window.TimeclockManager || {};
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
            // [FIX] Use the real DB ID stored in extendedProps
            const props = info.event.extendedProps;
            const id = props && props.timesheet_id ? props.timesheet_id : info.event.id;
            
            // Extract date (YYYY-MM-DD)
            const date = info.event.startStr.split('T')[0];
            
            console.log("Opening Timesheet:", date, id);
            TimesheetModule.open(date, id);
        };

        CalendarModule.onDateClick = (info) => {
            TimesheetModule.open(info.dateStr, null);
        };
    }
});