import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';

export const TmClient = {
    /**
     * Generic Request Handler
     * Handles CSRF, URL generation, and basic error parsing.
     * @param {string} method - GET, POST, DELETE, etc.
     * @param {string} route - The API route (e.g., '/api/timesheets')
     * @param {object} [data] - The JSON body for POST/PUT
     * @param {object} [params] - The Query Parameters for GET
     */
    async request(method, route, data = null, params = null) {
        try {
            // Generate the full Nextcloud URL
            // e.g., '/apps/timeclock-manager/api/timesheets'
            const url = generateUrl(`/apps/timeclock-manager${route}`);

            const response = await axios({
                method: method,
                url: url,
                data: data,
                params: params,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // Standard NC check
                }
            });

            return response.data;
        } catch (error) {
            console.error(`API Error [${method} ${route}]:`, error);
            throw error;
        }
    }
};