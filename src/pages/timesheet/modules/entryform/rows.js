export const ActivityRows = {
    containerId: 'work-rows-container',

    add(descVal = '', percentVal = '', isUserAction = false) {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        const existingRows = container.querySelectorAll('.work-row');
        
        if (descVal === '' && percentVal === '') {
            if (existingRows.length === 0) {
                percentVal = 100;
            } else {
                const count = existingRows.length + 1;
                const split = Math.floor(100 / count);
                container.querySelectorAll('.work-percent-input').forEach(inp => inp.value = split);
                percentVal = 100 - (split * (count - 1));
            }
        }

        const row = document.createElement('div');
        row.className = 'work-row';
        
        let optionsHtml = '<option value="">Select Job...</option>';
        if (window.StechTimesheet.state && window.StechTimesheet.state.jobs) {
            window.StechTimesheet.state.jobs.forEach(job => {
                const selected = (job.job_name === descVal) ? 'selected' : '';
                optionsHtml += `<option value="${job.job_name}" ${selected}>${job.job_name}</option>`;
            });
        }

        row.innerHTML = `
            <select name="work_desc[]" class="form-control work-desc">${optionsHtml}</select>
            <input type="number" name="work_percent[]" class="form-control text-center work-percent work-percent-input" 
                   value="${percentVal}" placeholder="0" min="0" max="100">
            <div class="btn-remove-row" title="Remove">&times;</div>
        `;

        const input = row.querySelector('.work-percent-input');
        input.addEventListener('input', (e) => this.recalculate(e.target));

        row.querySelector('.btn-remove-row').addEventListener('click', () => {
            row.remove();
            this.recalculate(null);
        });

        container.appendChild(row);

        if (isUserAction) this.recalculate(null);
    },

    recalculate(sourceInput) {
        const allInputs = Array.from(document.querySelectorAll('.work-percent-input'));
        if (allInputs.length === 0) return;

        if (!sourceInput) {
            const count = allInputs.length;
            const base = Math.floor(100 / count);
            let remainder = 100 % count;
            allInputs.forEach(input => {
                input.value = base + (remainder > 0 ? 1 : 0);
                remainder--;
            });
            return;
        }

        let val = parseInt(sourceInput.value) || 0;
        if (val < 0) val = 0;
        if (val > 100) val = 100;
        
        const remaining = 100 - val;
        const others = allInputs.filter(i => i !== sourceInput);
        
        if (others.length === 0) return;

        const base = Math.floor(remaining / others.length);
        let remainder = remaining % others.length;

        others.forEach(input => {
            input.value = base + (remainder > 0 ? 1 : 0);
            remainder--;
        });
    },

    clear() {
        const container = document.getElementById(this.containerId);
        if (container) container.innerHTML = '';
    }
};