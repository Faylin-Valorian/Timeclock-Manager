import { TimesheetAPI } from './js/api.js';
import { TimesheetForm } from './js/form.js';
import { RowManager } from './js/manager.js';
import { Popup } from 'src/components/popup/popup.js';

export const TimesheetModule = {
    currentId: null,
    initialSnapshot: '',
    isArchivedView: false,
    permissions: {
        can_view_archive: true,
        can_edit_archived: false,
        can_restore_archived: false
    },
    ptoJobName: '',

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

        // PTO Auto Defaults
        document.getElementById('toggle-pto')?.addEventListener('change', (e) => {
            if (e.target.checked) {
                this.applyPtoDefaults();
            }
        });

        // Modal Close
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.requestClose();
            });
        });

        document.addEventListener('keydown', (e) => this.handleGlobalKeydown(e));
    },

    async loadAttributes() {
        const data = await TimesheetAPI.getAttributes();
        if (data) {
            RowManager.setJobs(data.jobs || []);
            TimesheetForm.populateStateDatalist(data.states || []);
            this.permissions = {
                can_view_archive: !!(data.permissions?.can_view_archive ?? true),
                can_edit_archived: !!(data.permissions?.can_edit_archived ?? false),
                can_restore_archived: !!(data.permissions?.can_restore_archived ?? false)
            };
            this.ptoJobName = data.pto_job || (data.jobs || []).find(j => parseInt(j.is_pto, 10) === 1)?.job_name || '';
        }
    },

    applyPtoDefaults() {
        const appliedTime = TimesheetForm.applyPtoTimeDefaults();
        if (!appliedTime) return;

        const firstJobInput = document.querySelector('#work-rows-container .work-row .work-desc');
        if (firstJobInput) {
            firstJobInput.value = this.ptoJobName || firstJobInput.value || '';
            return;
        }

        RowManager.add(this.ptoJobName || '', 100, true);
    },

    /**
     * Entry Point: Called by Calendar when a date/event is clicked
     * @param {string} date - YYYY-MM-DD
     * @param {string|number|null} id - The Timesheet ID (if editing)
     */
    open(date, id = null, options = {}) {
        // [CRITICAL] Ensure ID is an integer if present
        this.currentId = id ? parseInt(id, 10) : null;
        this.isArchivedView = !!options.archive;
        
        TimesheetForm.reset();
        TimesheetForm.setDate(date);
        TimesheetForm.setTitle(this.currentId ? (this.isArchivedView ? "Archived Entry" : "Edit Entry") : "New Entry");
        
        // Show Delete button only if editing
        TimesheetForm.toggleDeleteButton(!!this.currentId);
        this.configureHeaderActions();

        // [FIX] Always clear rows first to prevent duplicates from previous opens
        RowManager.clear();

        if (this.currentId) {
            this.loadEntry(this.currentId);
        } else {
            // Only add a default row if it's a NEW entry
            RowManager.add('', 100, true);
            TimesheetForm.setReadOnly(false);
            TimesheetForm.showModal();
            this.captureInitialSnapshot();
        }
    },

    configureHeaderActions() {
        const deleteBtn = document.getElementById('btn-delete');
        const saveBtn = document.querySelector('#timesheet-form button[type="submit"]');
        if (!deleteBtn || !saveBtn) return;

        // New records: hide delete/restore, keep save visible
        if (!this.currentId) {
            deleteBtn.style.display = 'none';
            saveBtn.style.display = 'inline-flex';
            return;
        }

        if (!this.isArchivedView) {
            deleteBtn.textContent = 'Delete';
            deleteBtn.title = 'Delete entry';
            deleteBtn.style.display = 'inline-flex';
            saveBtn.style.display = 'inline-flex';
            return;
        }

        // Archived records
        if (this.permissions.can_restore_archived) {
            deleteBtn.textContent = 'Restore Tab';
            deleteBtn.title = 'Restore archived entry';
            deleteBtn.style.display = 'inline-flex';
        } else {
            deleteBtn.style.display = 'none';
        }

        saveBtn.style.display = this.permissions.can_edit_archived ? 'inline-flex' : 'none';
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
                        const rawDesc = act.description || act.activity_description || '';
                        const desc = (typeof rawDesc === 'string' && rawDesc.trim().toLowerCase() === 'null') ? '' : rawDesc;
                        const pct = act.percent || act.activity_percent || 0;
                        RowManager.add(desc, pct);
                    });
                } else {
                    const isAutoHoliday = parseInt(data.is_pto, 10) === 2;
                    if (!isAutoHoliday) {
                        RowManager.add('', 100);
                    }
                }

                const isReadOnlyArchived = this.isArchivedView && !this.permissions.can_edit_archived;
                TimesheetForm.setReadOnly(isReadOnlyArchived);
                this.configureHeaderActions();
                TimesheetForm.showModal();
                this.captureInitialSnapshot();
            }
        } catch (e) {
            console.error("Failed to load entry", e);
            await Popup.alert("Error loading timesheet data.", "Unable to Load", "error");
        }
    },

    async handleSubmit(e) {
        if (e && typeof e.preventDefault === 'function') {
            e.preventDefault();
        }
        await this.saveCurrentEntry();
    },

    async saveCurrentEntry() {

        if (this.isArchivedView && !this.permissions.can_edit_archived) {
            await Popup.alert("Archived records are read-only for your role.", "Read-Only Record", "info");
            return false;
        }
        
        // Gather Data from Form Helper
        const formData = TimesheetForm.gatherData();
        
        // [CRITICAL FIX] Attach the ID so the backend knows to UPDATE
        formData.id = this.currentId; 
        
        // Gather Rows
        const rows = RowManager.getAll();
        const hasJobDescription = rows.some((r) => (r.desc || '').trim() !== '');
        const perDiemEnabled = parseInt(formData.travel_per_diem, 10) === 1;
        const hasTimeIn = !!formData.time_in;
        const hasTimeOut = !!formData.time_out;

        // Validation path 1:
        // When Time Out is entered, both Time In and Job Description are required.
        if (hasTimeOut) {
            const missing = [];
            if (!hasTimeIn) missing.push('Time In');
            if (!hasJobDescription) missing.push('Job Description');
            if (missing.length > 0) {
                await Popup.alert(
                    `Time Out was entered. Please provide: ${missing.join(' and ')}.`,
                    "Time Out Requirements",
                    "error"
                );
                return false;
            }
        }

        // Validation path 2:
        // Without Time Out, description is only optional if Per Diem is enabled OR Time In is entered.
        const allowWithoutDescription = perDiemEnabled || hasTimeIn;
        if (!hasTimeOut && !allowWithoutDescription && !hasJobDescription) {
            await Popup.alert(
                "No Time Out was entered. To submit without a job description, enter Time In or enable Per Diem.",
                "Job Description Required",
                "error"
            );
            return false;
        }
        
        // Format activities for the backend
        formData.activities = rows.map(r => ({
            description: r.desc,
            percent: r.percent
        }));

        const success = await TimesheetAPI.save(formData);
        
        if (success) {
            // Close modal ONLY on success
            TimesheetForm.hideModal();
            this.initialSnapshot = '';
            this.refreshCalendar();
            return true;
        } else {
            await Popup.alert("Failed to save timesheet.", "Save Failed", "error");
            return false;
        }
    },

    async handleDelete() {
        if (!this.currentId) return;

        // Archived view + admin => restore action
        if (this.isArchivedView) {
            if (!this.permissions.can_restore_archived) {
                await Popup.alert("Only admins can restore archived tabs.", "Permission Required", "error");
                return;
            }

            const shouldRestore = await Popup.confirm("Restore this archived tab back to active records?", "Restore Tab", "Restore");
            if (!shouldRestore) return;

            const restored = await TimesheetAPI.restore(this.currentId);
            if (restored) {
                TimesheetForm.hideModal();
                this.refreshCalendar();
            } else {
                await Popup.alert("Failed to restore archived tab.", "Restore Failed", "error");
            }
            return;
        }

        const shouldDelete = await Popup.confirm("Are you sure you want to delete this entry?", "Delete Timesheet");
        if (!shouldDelete) return;
        
        const deleted = await TimesheetAPI.delete(this.currentId);
        
        if (deleted) {
            TimesheetForm.hideModal();
            this.refreshCalendar();
        } else {
            await Popup.alert("Failed to delete timesheet.", "Delete Failed", "error");
        }
    },

    refreshCalendar() {
        if (window.TimeclockManager.CalendarInstance) {
            // Refetch events to show the updated data (or remove the deleted one)
            window.TimeclockManager.CalendarInstance.refetchEvents();
        }
    },

    isModalOpen() {
        const modal = document.getElementById('timesheet-modal');
        return !!(modal && modal.style.display !== 'none');
    },

    normalizeValue(value) {
        if (value === null || value === undefined) return '';
        return value;
    },

    buildSnapshot() {
        const formData = TimesheetForm.gatherData();
        const rows = RowManager.getAll().map((r) => ({
            desc: String(this.normalizeValue(r.desc)).trim(),
            percent: Number(this.normalizeValue(r.percent) || 0)
        }));

        const normalized = {
            id: this.currentId || 0,
            archived: this.isArchivedView ? 1 : 0,
            date: String(this.normalizeValue(formData.date)),
            time_in: String(this.normalizeValue(formData.time_in)),
            time_out: String(this.normalizeValue(formData.time_out)),
            break_min: String(this.normalizeValue(formData.break_min)),
            time_total: String(this.normalizeValue(formData.time_total)),
            comments: String(this.normalizeValue(formData.comments)),
            is_pto: Number(this.normalizeValue(formData.is_pto) || 0),
            travel_per_diem: Number(this.normalizeValue(formData.travel_per_diem) || 0),
            travel_road_scanning: Number(this.normalizeValue(formData.travel_road_scanning) || 0),
            travel_first_last_day: Number(this.normalizeValue(formData.travel_first_last_day) || 0),
            travel_overnight: Number(this.normalizeValue(formData.travel_overnight) || 0),
            travel_state: String(this.normalizeValue(formData.travel_state)),
            travel_county: String(this.normalizeValue(formData.travel_county)),
            travel_miles: Number(this.normalizeValue(formData.travel_miles) || 0),
            travel_extra_expenses: Number(this.normalizeValue(formData.travel_extra_expenses) || 0),
            rows
        };

        return JSON.stringify(normalized);
    },

    captureInitialSnapshot() {
        this.initialSnapshot = this.buildSnapshot();
    },

    hasUnsavedChanges() {
        if (!this.initialSnapshot) return false;
        return this.buildSnapshot() !== this.initialSnapshot;
    },

    async requestClose() {
        if (!this.isModalOpen()) return;
        if (document.querySelector('.tm-popup-overlay')) return;

        if (!this.hasUnsavedChanges()) {
            TimesheetForm.sanitizeNullishDom?.();
            TimesheetForm.hideModal();
            this.initialSnapshot = '';
            return;
        }

        const choice = await Popup.confirmSaveBeforeClose(
            'You have unsaved changes. Save before closing this entry?',
            'Unsaved Changes'
        );

        if (choice === 'save') {
            await this.saveCurrentEntry();
            return;
        }
        if (choice === 'discard') {
            TimesheetForm.sanitizeNullishDom?.();
            TimesheetForm.hideModal();
            this.initialSnapshot = '';
        }
    },

    async handleGlobalKeydown(e) {
        if (e.key !== 'Escape') return;
        if (!this.isModalOpen()) return;
        if (document.querySelector('.tm-popup-overlay')) return;

        e.preventDefault();
        e.stopPropagation();
        await this.requestClose();
    }
};
window.TimeclockManager = window.TimeclockManager || {};
window.TimeclockManager.Modules = window.TimeclockManager.Modules || {};
window.TimeclockManager.Modules.Timesheet = TimesheetModule;
