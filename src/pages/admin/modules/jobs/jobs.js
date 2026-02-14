import { StechAPI } from '../../../../api/api.js';

export const JobAdmin = {
    jobs: [],

    load() {
        this.bindEvents();
        this.fetchJobs();
    },

    bindEvents() {
        if (this._eventsBound) return;
        document.getElementById('form-job')?.addEventListener('submit', (e) => this.submit(e));
        document.getElementById('btn-cancel-job')?.addEventListener('click', () => this.resetForm());
        document.getElementById('job-search-input')?.addEventListener('input', () => this.render());
        document.querySelectorAll('input[name="job-status"]').forEach(r => r.addEventListener('change', () => this.render()));
        this._eventsBound = true;
    },

    async fetchJobs() {
        try {
            this.jobs = await StechAPI.admin.getJobs();
            this.render();
        } catch (e) { console.error(e); }
    },

    render() {
        const container = document.getElementById('job-list');
        if (!container) return;
        
        const filter = document.querySelector('input[name="job-status"]:checked')?.value || 'active';
        const search = document.getElementById('job-search-input')?.value.toLowerCase() || '';

        const filtered = this.jobs.filter(j => {
            const matchesSearch = j.job_name.toLowerCase().includes(search);
            const isArchived = j.archived == 1;
            if (!matchesSearch) return false;
            return filter === 'active' ? !isArchived : isArchived;
        });

        container.innerHTML = filtered.map(j => `
            <div class="list-item" id="job-item-${j.id}">
                <div style="flex-grow:1;">
                    <div class="item-title">${j.job_name}</div>
                    <div class="item-subtitle">${j.is_pto ? 'VACATION/PTO' : 'Standard Job'}</div>
                </div>
                <button class="action-icon" onclick="window.JobAdmin.toggle(${j.id})">
                    <span class="icon-${j.archived == 1 ? 'history' : 'delete'}"></span>
                </button>
            </div>
        `).join('');

        // Attach click to edit
        filtered.forEach(j => {
            document.getElementById(`job-item-${j.id}`).addEventListener('click', (e) => {
                // Ignore if clicked the delete button
                if (!e.target.closest('.action-icon')) this.edit(j);
            });
        });
    },

    edit(job) {
        document.getElementById('job-id').value = job.id;
        document.getElementById('job-name').value = job.job_name;
        document.getElementById('job-desc').value = job.description || '';
        document.getElementById('job-is-pto').checked = (job.is_pto == 1);
        document.getElementById('job-revenue').value = job.revenue || '';
        document.getElementById('job-expense').value = job.expense || '';
        document.getElementById('job-hourly').value = job.hourly_cost || '';

        document.getElementById('job-form-title').textContent = "Edit Job";
        document.getElementById('btn-save-job').textContent = "Update Job";
        document.getElementById('btn-cancel-job').classList.remove('hidden');
    },

    resetForm() {
        document.getElementById('form-job').reset();
        document.getElementById('job-id').value = '';
        document.getElementById('job-form-title').textContent = "Create Job";
        document.getElementById('btn-save-job').textContent = "Create Job";
        document.getElementById('btn-cancel-job').classList.add('hidden');
    },

    async submit(e) {
        e.preventDefault();
        const payload = {
            id: document.getElementById('job-id').value || null,
            name: document.getElementById('job-name').value,
            description: document.getElementById('job-desc').value,
            is_pto: document.getElementById('job-is-pto').checked,
            revenue: document.getElementById('job-revenue').value,
            expense: document.getElementById('job-expense').value,
            hourly: document.getElementById('job-hourly').value
        };
        await StechAPI.admin.saveJob(payload);
        this.resetForm();
        this.fetchJobs();
    },

    async toggle(id) {
        if(confirm("Archive/Restore this job?")) {
            await StechAPI.admin.toggleJob(id);
            this.fetchJobs();
        }
    }
};

window.JobAdmin = JobAdmin;