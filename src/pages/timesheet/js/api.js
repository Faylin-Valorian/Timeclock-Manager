import { generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';

export const TimesheetAPI = {
    // Define Routes
    routes: {
        timesheets: generateUrl('/apps/timeclock-manager/api/timesheets'),
        attributes: generateUrl('/apps/timeclock-manager/api/attributes'),
        countiesBase: generateUrl('/apps/timeclock-manager/api/locations/counties')
    },

    getTargetParams() {
        const uid = window.TimeclockManager?.Impersonation?.getTargetUid?.() || '';
        return uid ? { target_user: uid } : {};
    },

    /**
     * Get Attributes (Jobs, States, etc)
     */
    async getAttributes() {
        try {
            const response = await axios.get(this.routes.attributes, { params: this.getTargetParams() });
            return response.data;
        } catch (error) {
            console.error('API: Failed to load attributes', error);
            return null;
        }
    },

    /**
     * Get Single Timesheet Details
     */
    async getDetails(id) {
        if (!id) return null;
        try {
            const url = `${this.routes.timesheets}/${id}`;
            const response = await axios.get(url, { params: this.getTargetParams() });
            return response.data;
        } catch (error) {
            console.error(`API: Failed to load timesheet ${id}`, error);
            return null;
        }
    },

    async getCounties(stateAbbr) {
        if (!stateAbbr) return [];
        try {
            const url = `${this.routes.countiesBase}/${encodeURIComponent(stateAbbr)}`;
            const response = await axios.get(url);
            return Array.isArray(response.data) ? response.data : [];
        } catch (error) {
            console.error(`API: Failed to load counties for ${stateAbbr}`, error);
            return [];
        }
    },

    /**
     * Save Timesheet (Create or Update)
     */
    async save(data) {
        try {
            // [CRITICAL] Nextcloud controllers expect JSON.
            // Axios sends JSON by default when data is an object.
            const response = await axios.post(this.routes.timesheets, data, { params: this.getTargetParams() });
            
            // Return true if success (200 OK or 201 Created)
            return response.status === 200 || response.status === 201;
        } catch (error) {
            console.error('API: Failed to save timesheet', error);
            return false;
        }
    },

    /**
     * Delete Timesheet (Archive)
     */
    async delete(id) {
        try {
            const url = `${this.routes.timesheets}/${id}`;
            const response = await axios.delete(url, { params: this.getTargetParams() });
            return response.status === 200;
        } catch (error) {
            console.error(`API: Failed to delete timesheet ${id}`, error);
            return false;
        }
    },

    /**
     * Restore Archived Timesheet
     */
    async restore(id) {
        try {
            const url = `${this.routes.timesheets}/${id}/restore`;
            const response = await axios.post(url, null, { params: this.getTargetParams() });
            return response.status === 200;
        } catch (error) {
            console.error(`API: Failed to restore timesheet ${id}`, error);
            return false;
        }
    }
};
