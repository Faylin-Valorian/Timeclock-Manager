export const RowRenderer = {
    createRow(jobs = [], selectedJob = '', percent = 0) {
        const row = document.createElement('div');
        row.className = 'work-row';
        const safeSelected = selectedJob || '';

        row.innerHTML = `
            <input type="text" class="work-desc form-control" placeholder="Search or select job..." value="${safeSelected.replace(/"/g, '&quot;')}" style="flex-grow: 1;">
            <input type="number" class="work-percent form-control" placeholder="%" value="${percent}" min="0" max="100" step="1" style="width: 80px;">
            <button class="btn-remove-row action-icon" tabindex="-1" title="Remove" style="color: red;">
                &times;
            </button>
        `;

        return row;
    },

    clearContainer(containerId) {
        const container = document.getElementById(containerId);
        if (container) container.innerHTML = '';
    },

    getValues(containerId) {
        const rows = [];
        const container = document.getElementById(containerId);
        if (!container) return [];

        container.querySelectorAll('.work-row').forEach(row => {
            const rawPercent = parseInt(row.querySelector('.work-percent').value, 10);
            const clampedPercent = Number.isNaN(rawPercent) ? 0 : Math.max(0, Math.min(100, rawPercent));
            rows.push({
                desc: row.querySelector('.work-desc').value,
                percent: clampedPercent
            });
        });
        return rows;
    }
}
