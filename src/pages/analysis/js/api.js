import { TmClient } from 'src/api/api.js';

const getImpersonationTarget = () => {
    try {
        const fromGlobal = window.TimeclockManager?.Impersonation?.getTargetUid?.();
        if (fromGlobal) return fromGlobal;

        const raw = window.sessionStorage.getItem('tm_impersonation');
        if (!raw) return '';
        const parsed = JSON.parse(raw);
        return String(parsed?.uid || '');
    } catch (e) {
        return '';
    }
};

export const AnalysisAPI = {
    async getSummary(start, end) {
        const targetUser = getImpersonationTarget();
        return await TmClient.request('GET', '/api/analysis/summary', null, {
            start,
            end,
            target_user: targetUser || undefined
        });
    },

    async getDetail(metric, start, end) {
        const targetUser = getImpersonationTarget();
        return await TmClient.request('GET', '/api/analysis/detail', null, {
            metric,
            start,
            end,
            target_user: targetUser || undefined
        });
    }
};
