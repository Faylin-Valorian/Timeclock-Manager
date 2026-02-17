import { TmClient } from 'src/api/api.js';

export const AdminAPI = {
    async getBootstrap() {
        return await TmClient.request('GET', '/api/admin/bootstrap');
    },

    async saveHoliday(payload) {
        return await TmClient.request('POST', '/api/admin/holidays', payload);
    },

    async saveAccess(ruleKey, allowedGroups) {
        return await TmClient.request('POST', '/api/admin/access', {
            rule_key: ruleKey,
            allowed_groups: allowedGroups
        });
    }
};
