<<<<<<< HEAD
import { TmClient } from 'src/api/api.js';

export const TimesheetAPI = {
    /**
     * Get Dropdown Attributes (Jobs, States)
     */
    async getAttributes() {
        try {
            return await TmClient.request('GET', '/api/attributes');
        } catch (e) {
            console.error("Failed to load attributes", e);
            return { jobs: [], states: [] }; // Return empty structure on failure
        }
    },

    /**
     * Get details for a single timesheet entry
     */
    async getDetails(id) {
        try {
            return await TmClient.request('GET', `/api/timesheets/${id}`);
        } catch (e) {
            console.error("Error loading entry", e);
            alert("Failed to load entry details.");
            return null;
        }
    },

    /**
     * Save a timesheet entry (New or Update)
     */
    async save(formData) {
        try {
            const response = await TmClient.request('POST', '/api/timesheets', formData);
            return response?.id; // Return the ID of the saved item
        } catch (err) {
            console.error(err);
            alert("Failed to save timesheet.");
            return null;
        }
    },

    /**
     * Delete a timesheet entry
     */
    async delete(id) {
        try {
            await TmClient.request('DELETE', `/api/timesheets/${id}`);
            return true;
        } catch (err) {
            console.error(err);
            alert("Failed to delete entry.");
            return false;
        }
    }
=======
import { TmClient } from 'src/api/api.js';

export const TimesheetAPI = {
    /**
     * Get Dropdown Attributes (Jobs, States)
     */
    async getAttributes() {
        try {
            return await TmClient.request('GET', '/api/attributes');
        } catch (e) {
            console.error("Failed to load attributes", e);
            return { jobs: [], states: [] }; // Return empty structure on failure
        }
    },

    /**
     * Get details for a single timesheet entry
     */
    async getDetails(id) {
        try {
            return await TmClient.request('GET', `/api/timesheets/${id}`);
        } catch (e) {
            console.error("Error loading entry", e);
            alert("Failed to load entry details.");
            return null;
        }
    },

    /**
     * Save a timesheet entry (New or Update)
     */
    async save(formData) {
        try {
            const response = await TmClient.request('POST', '/api/timesheets', formData);
            return response?.id; // Return the ID of the saved item
        } catch (err) {
            console.error(err);
            alert("Failed to save timesheet.");
            return null;
        }
    },

    /**
     * Delete a timesheet entry
     */
    async delete(id) {
        try {
            await TmClient.request('DELETE', `/api/timesheets/${id}`);
            return true;
        } catch (err) {
            console.error(err);
            alert("Failed to delete entry.");
            return false;
        }
    }
>>>>>>> 6edf4541514ac05df3faedfa2f961d65a5495869
};