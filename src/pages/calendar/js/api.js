<<<<<<< HEAD
import { TmClient } from 'src/api/api.js';

export const CalendarAPI = {
    async getTimesheets(start, end, archiveMode = 0) {
        return await TmClient.request('GET', '/api/timesheets', null, {
            start: start,
            end: end,
            archive: archiveMode
        });
    },

    async getHolidays(start, end) {
        return await TmClient.request('GET', '/api/calendar/holidays', null, {
            start: start,
            end: end
        });
    }
=======
import { TmClient } from 'src/api/api.js';

export const CalendarAPI = {
    async getTimesheets(start, end, archiveMode = 0) {
        return await TmClient.request('GET', '/api/timesheets', null, {
            start: start,
            end: end,
            archive: archiveMode
        });
    },

    async getHolidays(start, end) {
        return await TmClient.request('GET', '/api/calendar/holidays', null, {
            start: start,
            end: end
        });
    }
>>>>>>> 6edf4541514ac05df3faedfa2f961d65a5495869
};