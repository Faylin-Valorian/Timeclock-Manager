import { TmClient } from 'src/api/api.js';

export const CalendarAPI = {
    async getTimesheets(start, end, archiveMode = 0) {
        const targetUser = window.TimeclockManager?.Impersonation?.getTargetUid?.() || '';
        return await TmClient.request('GET', '/api/timesheets', null, {
            start: start,
            end: end,
            archive: archiveMode,
            target_user: targetUser || undefined
        });
    },

    async getHolidays(start, end) {
        return await TmClient.request('GET', '/api/calendar/holidays', null, {
            start: start,
            end: end
        });
    }
};
