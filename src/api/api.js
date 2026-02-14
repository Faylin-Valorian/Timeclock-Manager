import { generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';

/**
 * Shared API Client for Timeclock Manager
 */
export const StechAPI = {
    
    // --- CORE ---
    async getAttributes() {
        const url = generateUrl('/apps/timeclock_manager/api/attributes');
        const response = await axios.get(url);
        return response.data;
    },

    // --- TIMESHEETS ---
    async getTimesheets(startDate, endDate) {
        // Implementation for calendar fetching
        const url = generateUrl('/apps/timeclock_manager/api/timesheets');
        const response = await axios.get(url, { params: { start: startDate, end: endDate } });
        return response.data;
    },

    // --- ADMIN MODULES ---
    admin: {
        // Users
        async getUsers() {
            const response = await axios.get(generateUrl('/apps/timeclock_manager/api/admin/users'));
            return response.data;
        },
        
        // Access
        async getGroups() {
            const response = await axios.get(generateUrl('/apps/timeclock_manager/api/admin/groups'));
            return response.data;
        },
        async getAccess() {
            const response = await axios.get(generateUrl('/apps/timeclock_manager/api/admin/access'));
            return response.data;
        },
        async saveAccess(payload) {
            return await axios.post(generateUrl('/apps/timeclock_manager/api/admin/access'), payload);
        },

        // Settings (Payroll)
        async getSettings() {
            const response = await axios.get(generateUrl('/apps/timeclock_manager/api/admin/settings'));
            return response.data;
        },
        async saveSettings(payload) {
            return await axios.post(generateUrl('/apps/timeclock_manager/api/admin/settings'), payload);
        },

        // Holidays
        async getHolidays() {
            const response = await axios.get(generateUrl('/apps/timeclock_manager/api/admin/holidays'));
            return response.data;
        },
        async saveHoliday(payload) {
            return await axios.post(generateUrl('/apps/timeclock_manager/api/admin/holidays'), payload);
        },
        async toggleHoliday(id) {
            return await axios.post(generateUrl(`/apps/timeclock_manager/api/admin/holidays/${id}/toggle`));
        },

        // Jobs
        async getJobs() {
            const response = await axios.get(generateUrl('/apps/timeclock_manager/api/admin/jobs'));
            return response.data;
        },
        async saveJob(payload) {
            return await axios.post(generateUrl('/apps/timeclock_manager/api/admin/jobs'), payload);
        },
        async toggleJob(id) {
            return await axios.post(generateUrl(`/apps/timeclock_manager/api/admin/jobs/${id}/toggle`));
        },

        // Locations
        async getStates() {
            const response = await axios.get(generateUrl('/apps/timeclock_manager/api/admin/states'));
            return response.data;
        },
        async toggleState(id) {
            return await axios.post(generateUrl(`/apps/timeclock_manager/api/admin/states/${id}/toggle`));
        },
        async getCounties(stateAbbr) {
            const response = await axios.get(generateUrl(`/apps/timeclock_manager/api/admin/counties/${stateAbbr}`));
            return response.data;
        },
        async toggleCounty(id) {
            return await axios.post(generateUrl(`/apps/timeclock_manager/api/admin/counties/${id}/toggle`));
        }
    }
};