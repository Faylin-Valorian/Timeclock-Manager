export const RowRenderer = {
    createRow(jobs = [], selectedJob = '', percent = 0) {
        const row = document.createElement('div');
        row.className = 'work-row';
        
        let optionsHtml = '<option value="">Select Job...</option>';
        jobs.forEach(job => {
            const selected = job.job_name === selectedJob ? 'selected' : '';
            optionsHtml += `<option value="${job.job_name}" ${selected}>${job.job_name}</option>`;
        });

        row.innerHTML = `
            <select class="work-desc form-control" style="flex-grow: 1; margin-right: 10px;">
                ${optionsHtml}
            </select>
            <input type="number" class="work-percent form-control" placeholder="%" value="${percent}" min="0" max="100" style="width: 80px;">
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
            rows.push({
                desc: row.querySelector('.work-desc').value,
                percent: parseInt(row.querySelector('.work-percent').value) || 0
            });
        });
        return rows;
    }
}