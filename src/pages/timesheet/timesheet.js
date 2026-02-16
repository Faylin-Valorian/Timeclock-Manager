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
        document.getElementById('btn-delete')?.addEventListener('click', (e) => this.handleDelete());

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
     * @param {string} date - YYYY-MM-DD
     * @param {string|number|null} id - The Timesheet ID (if editing)
     */
    open(date, id = null) {
        // [CRITICAL] Ensure ID is an integer if present
        this.currentId = id ? parseInt(id, 10) : null;
        
        TimesheetForm.reset();
        TimesheetForm.setDate(date);
        TimesheetForm.setTitle(id ? "Edit Entry" : "New Entry");
        
        // Show Delete button only if editing
        TimesheetForm.toggleDeleteButton(!!this.currentId);

        // [FIX] Always clear rows first to prevent duplicates from previous opens
        RowManager.clear();

        if (this.currentId) {
            this.loadEntry(this.currentId);
        } else {
            // Only add a default row if it's a NEW entry
            RowManager.add('', 100, true);
            TimesheetForm.showModal();
        }
    },

    async loadEntry(id) {
        try {
            const data = await TimesheetAPI.getDetails(id);
            if (data) {
                // Populate Form Fields
                TimesheetForm.populate(data);
                
                // Populate Rows via Manager
                if (data.activities && data.activities.length > 0) {
                    data.activities.forEach(act => {
                        // Handle potential backend naming differences
                        const desc = act.description || act.activity_description || '';
                        const pct = act.percent || act.activity_percent || 0;
                        RowManager.add(desc, pct);
                    });
                } else {
                    RowManager.add('', 100);
                }

                TimesheetForm.showModal();
            }
        } catch (e) {
            console.error("Failed to load entry", e);
            alert("Error loading timesheet data.");
        }
    },

    async handleSubmit(e) {
        e.preventDefault();
        
        // Gather Data from Form Helper
        const formData = TimesheetForm.gatherData();
        
        // [CRITICAL FIX] Attach the ID so the backend knows to UPDATE
        formData.id = this.currentId; 
        
        // Gather Rows
        const rows = RowManager.getAll();
        
        // Format activities for the backend
        formData.activities = rows.map(r => ({
            description: r.desc,
            percent: r.percent
        }));

        const success = await TimesheetAPI.save(formData);
        
        if (success) {
            // Close modal ONLY on success
            TimesheetForm.hideModal();
            this.refreshCalendar();
        } else {
            alert("Failed to save timesheet.");
        }
    },

    async handleDelete() {
        if (!this.currentId || !confirm("Are you sure you want to delete this entry?")) return;
        
        const success = await TimesheetAPI.delete(this.currentId);
        
        if (success) {
            TimesheetForm.hideModal();
            this.refreshCalendar();
        } else {
            alert("Failed to delete timesheet.");
        }
    },

    refreshCalendar() {
        if (window.TimeclockManager.CalendarInstance) {
            // Refetch events to show the updated data (or remove the deleted one)
            window.TimeclockManager.CalendarInstance.refetchEvents();
        }
    }
};