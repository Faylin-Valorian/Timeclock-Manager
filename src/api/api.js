import { generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';

export const StechAPI = {
    
    /**
     * Generic Request Helper
     * Automatically appends the App ID and handles errors.
     */
    async request(method, url, data = null, params = null) {
        try {
            // APP ID: timeclock-manager (Hyphenated)
            const fullUrl = generateUrl('/apps/timeclock-manager' + url);
            
            const config = {
                method: method,
                url: fullUrl,
                data: data,
                params: params
            };

            const response = await axios(config);
            return response.data;
        } catch (error) {
            console.error(`API Error [${method} ${url}]:`, error);
            throw error;
        }
    },

    // =========================================================================
    // 1. TIMESHEET & ENTRY FORM
    // =========================================================================

    /**
     * Load initial data (Jobs, States) for the Timesheet page.
     */
    async getAttributes() {
        return this.request('GET', '/api/attributes');
    },

    /**
     * Fetch calendar events (Timesheets + Payroll Markers).
     * @param {string} start - Start date (YYYY-MM-DD)
     * @param {string} end - End date (YYYY-MM-DD)
     * @param {number} archiveMode - 0 = Active, 1 = Archived
     */
    async getTimesheets(start, end, archiveMode = 0) {
        return this.request('GET', '/api/timesheets', null, { 
            start, 
            end, 
            archive: archiveMode 
        });
    },

    /**
     * Get details for a single timesheet entry (Edit Mode).
     */
    async getTimesheetDetails(id) {
        return this.request('GET', `/api/timesheets/${id}`);
    },

    /**
     * Save a new or existing timesheet entry.
     * @param {Object} formData 
     */
    async saveTimesheet(formData) {
        return this.request('POST', '/api/timesheets', formData);
    },

    /**
     * Archive a timesheet entry (Soft Delete).
     */
    async deleteTimesheet(id) {
        return this.request('DELETE', `/api/timesheets/${id}`);
    },

    /**
     * Restore an archived timesheet entry.
     */
    async restoreTimesheet(id) {
        return this.request('POST', `/api/timesheets/${id}/restore`);
    },

    /**
     * Fetch holidays specifically for the Calendar view (Read-Only background events).
     */
    async getCalendarHolidays(start, end) {
        return this.request('GET', '/api/calendar/holidays', null, { start, end });
    },


    // =========================================================================
    // 2. ADMIN MODULES
    // =========================================================================

    // --- USERS ---
    async getUsers() {
        return this.request('GET', '/api/admin/users');
    },

    async toggleUser(uid, status) {
        return this.request('POST', '/api/admin/users/toggle', { uid, status });
    },

    // --- ACCESS CONTROL ---
    async getGroups() {
        return this.request('GET', '/api/admin/groups');
    },

    async getAccessRules() {
        return this.request('GET', '/api/admin/access');
    },

    async saveAccessRule(key, groups) {
        return this.request('POST', '/api/admin/access', { key, groups });
    },

    // --- PAYROLL ---
    async getPayrollSettings() {
        return this.request('GET', '/api/admin/settings');
    },

    async savePayrollSetting(key, value) {
        return this.request('POST', '/api/admin/settings', { key, value });
    },

    // --- HOLIDAYS (Management) ---
    async getAdminHolidays() {
        return this.request('GET', '/api/admin/holidays');
    },

    async saveAdminHoliday(data) {
        return this.request('POST', '/api/admin/holidays', data);
    },

    async toggleHoliday(id) {
        return this.request('POST', `/api/admin/holidays/${id}/toggle`);
    },

    // --- JOBS ---
    async getJobs() {
        return this.request('GET', '/api/admin/jobs');
    },

    async saveJob(data) {
        return this.request('POST', '/api/admin/jobs', data);
    },

    async toggleJob(id) {
        return this.request('POST', `/api/admin/jobs/${id}/toggle`);
    },

    // --- LOCATIONS ---
    async getStates() {
        return this.request('GET', '/api/admin/states');
    },

    async toggleState(id) {
        return this.request('POST', `/api/admin/states/${id}/toggle`);
    },

    /**
     * Get counties for a state. Used by both Admin and Entry Form.
     * @param {string} abbr - State Abbreviation (e.g. 'TX')
     */
    async getCounties(abbr) {
        return this.request('GET', `/api/admin/counties/${abbr}`);
    },

    async toggleCounty(id) {
        return this.request('POST', `/api/admin/counties/${id}/toggle`);
    },


    // =========================================================================
    // 3. ANALYSIS
    // =========================================================================
    
    async getAnalysisStats(filters) {
        return this.request('GET', '/api/analysis/stats', null, filters);
    }
};