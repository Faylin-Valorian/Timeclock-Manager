import { generateUrl } from '@nextcloud/router';
import axios from '@nextcloud/axios';

export const TimesheetAPI = {
    // Define Routes
    routes: {
        timesheets: generateUrl('/apps/timeclock-manager/api/timesheets'),
        attributes: generateUrl('/apps/timeclock-manager/api/attributes')
    },

    /**
     * Get Attributes (Jobs, States, etc)
     */
    async getAttributes() {
        try {
            const response = await axios.get(this.routes.attributes);
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
            const response = await axios.get(url);
            return response.data;
        } catch (error) {
            console.error(`API: Failed to load timesheet ${id}`, error);
            return null;
        }
    },

    /**
     * Save Timesheet (Create or Update)
     */
    async save(data) {
        try {
            // [CRITICAL] Nextcloud controllers expect JSON.
            // Axios sends JSON by default when data is an object.
            const response = await axios.post(this.routes.timesheets, data);
            
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
            const response = await axios.delete(url);
            return response.status === 200;
        } catch (error) {
            console.error(`API: Failed to delete timesheet ${id}`, error);
            return false;
        }
    }
};