<<<<<<< HEAD
import { TimesheetAPI } from './js/api.js';
import { TimesheetForm } from './js/form.js';
import { RowManager } from './js/manager.js';

export const TimesheetModule = {
    currentId: null,

    init() {
        // 1. Initialize Components
        RowManager.init();
        TimesheetForm.init();

        // 2. Setup Global Listeners
        this.setupListeners();

        // 3. Load Dropdowns
        this.loadAttributes();
    },

    setupListeners() {
        // Save
        document.getElementById('timesheet-form')?.addEventListener('submit', (e) => this.handleSubmit(e));

        // Delete
        document.getElementById('btn-delete')?.addEventListener('click', () => this.handleDelete());

        // Add Row
        document.getElementById('btn-add-row')?.addEventListener('click', (e) => {
            e.preventDefault();
            // Add new row with 0% initially, manager will auto-balance
            RowManager.add('', 0, true);
        });

        // Modal Close
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                TimesheetForm.hideModal();
            });
        });
    },

    async loadAttributes() {
        const data = await TimesheetAPI.getAttributes();
        if (data) {
            RowManager.setJobs(data.jobs || []);
            TimesheetForm.populateStateDatalist(data.states || []);
        }
    },

    /**
     * Entry Point: Called by Calendar when a date/event is clicked
     */
    open(date, id = null) {
        this.currentId = id;
        TimesheetForm.reset();
        TimesheetForm.setDate(date);
        TimesheetForm.setTitle(id ? "Edit Entry" : "New Entry");
        TimesheetForm.toggleDeleteButton(!!id);

        // [FIXED] Always clear rows first to prevent duplicates
        RowManager.clear();

        if (id) {
            this.loadEntry(id);
        } else {
            // Only add a default row if it's a NEW entry
            RowManager.add('', 100, true);
            TimesheetForm.showModal();
        }
    },

    async loadEntry(id) {
        const data = await TimesheetAPI.getDetails(id);
        if (data) {
            TimesheetForm.populate(data);
            
            // Populate Rows via Manager
            // Note: RowManager.clear() is already called in open(), so we just add here
            if (data.activities && data.activities.length > 0) {
                data.activities.forEach(act => RowManager.add(act.activity_description, act.activity_percent));
            } else {
                RowManager.add('', 100);
            }

            TimesheetForm.showModal();
        }
    },

    async handleSubmit(e) {
        e.preventDefault();
        
        // Gather Data
        const formData = TimesheetForm.gatherData();
        formData.timesheet_id = this.currentId;
        
        // Gather Rows
        const rows = RowManager.getAll();
        formData.work_desc = rows.map(r => r.desc);
        formData.work_percent = rows.map(r => r.percent);

        const success = await TimesheetAPI.save(formData);
        if (success) {
            TimesheetForm.hideModal();
            this.refreshCalendar();
        }
    },

    async handleDelete() {
        if (!this.currentId || !confirm("Are you sure you want to delete this entry?")) return;
        
        const success = await TimesheetAPI.delete(this.currentId);
        if (success) {
            TimesheetForm.hideModal();
            this.refreshCalendar();
        }
    },

    refreshCalendar() {
        if (window.TimeclockManager.CalendarInstance) {
            window.TimeclockManager.CalendarInstance.refetchEvents();
        }
    }
=======
import { TimesheetAPI } from './js/api.js';
import { TimesheetForm } from './js/form.js';
import { RowManager } from './js/manager.js';

export const TimesheetModule = {
    currentId: null,

    init() {
        // 1. Initialize Components
        RowManager.init();
        TimesheetForm.init();

        // 2. Setup Global Listeners
        this.setupListeners();

        // 3. Load Dropdowns
        this.loadAttributes();
    },

    setupListeners() {
        // Save
        document.getElementById('timesheet-form')?.addEventListener('submit', (e) => this.handleSubmit(e));

        // Delete
        document.getElementById('btn-delete')?.addEventListener('click', () => this.handleDelete());

        // Add Row
        document.getElementById('btn-add-row')?.addEventListener('click', (e) => {
            e.preventDefault();
            // Add new row with 0% initially, manager will auto-balance
            RowManager.add('', 0, true);
        });

        // Modal Close
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                TimesheetForm.hideModal();
            });
        });
    },

    async loadAttributes() {
        const data = await TimesheetAPI.getAttributes();
        if (data) {
            RowManager.setJobs(data.jobs || []);
            TimesheetForm.populateStateDatalist(data.states || []);
        }
    },

    /**
     * Entry Point: Called by Calendar when a date/event is clicked
     */
    open(date, id = null) {
        this.currentId = id;
        TimesheetForm.reset();
        TimesheetForm.setDate(date);
        TimesheetForm.setTitle(id ? "Edit Entry" : "New Entry");
        TimesheetForm.toggleDeleteButton(!!id);

        // [FIXED] Always clear rows first to prevent duplicates
        RowManager.clear();

        if (id) {
            this.loadEntry(id);
        } else {
            // Only add a default row if it's a NEW entry
            RowManager.add('', 100, true);
            TimesheetForm.showModal();
        }
    },

    async loadEntry(id) {
        const data = await TimesheetAPI.getDetails(id);
        if (data) {
            TimesheetForm.populate(data);
            
            // Populate Rows via Manager
            // Note: RowManager.clear() is already called in open(), so we just add here
            if (data.activities && data.activities.length > 0) {
                data.activities.forEach(act => RowManager.add(act.activity_description, act.activity_percent));
            } else {
                RowManager.add('', 100);
            }

            TimesheetForm.showModal();
        }
    },

    async handleSubmit(e) {
        e.preventDefault();
        
        // Gather Data
        const formData = TimesheetForm.gatherData();
        formData.timesheet_id = this.currentId;
        
        // Gather Rows
        const rows = RowManager.getAll();
        formData.work_desc = rows.map(r => r.desc);
        formData.work_percent = rows.map(r => r.percent);

        const success = await TimesheetAPI.save(formData);
        if (success) {
            TimesheetForm.hideModal();
            this.refreshCalendar();
        }
    },

    async handleDelete() {
        if (!this.currentId || !confirm("Are you sure you want to delete this entry?")) return;
        
        const success = await TimesheetAPI.delete(this.currentId);
        if (success) {
            TimesheetForm.hideModal();
            this.refreshCalendar();
        }
    },

    refreshCalendar() {
        if (window.TimeclockManager.CalendarInstance) {
            window.TimeclockManager.CalendarInstance.refetchEvents();
        }
    }
>>>>>>> 6edf4541514ac05df3faedfa2f961d65a5495869
};