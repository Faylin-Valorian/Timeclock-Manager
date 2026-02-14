import { ActivityRows } from './rows.js';
import { Dialogs } from '../ui/dialogs.js';

export const EntryForm = {
    currentId: null,
    isLocked: false,
    isArchivedRecord: false,

    init() {
        this.setupEventListeners();
        this.setupStateListener();
        this.setupToggleListeners();
        
        // Expose helper for debugging if needed
        window.StechTimesheet.ActivityRows = ActivityRows;
    },

    setupEventListeners() {
        // Save Button
        document.getElementById('btn-save')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });

        // Delete/Restore Button (Context Aware)
        document.getElementById('btn-delete')?.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.isArchivedRecord) {
                this.handleRestore();
            } else {
                this.handleDelete();
            }
        });

        // Add Row Button
        document.getElementById('btn-add-row')?.addEventListener('click', (e) => {
            e.preventDefault();
            ActivityRows.add('', '', true);
        });

        // Close Buttons
        document.querySelectorAll('.close-modal, .secondary-button').forEach(btn => {
            if (btn.id && btn.id.startsWith('btn-confirm')) return; 
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.close();
            });
        });

        // Auto-Calculate Total Hours
        ['time-in', 'time-out', 'break-min'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', () => this.calculateTotal());
            }
        });
    },

    calculateTotal() {
        const tIn = document.getElementById('time-in').value;
        const tOut = document.getElementById('time-out').value;
        const breakMin = parseInt(document.getElementById('break-min').value) || 0;
        const totalInput = document.getElementById('total-hours');

        if (tIn && tOut) {
            const d1 = new Date(`2000-01-01T${tIn}`);
            const d2 = new Date(`2000-01-01T${tOut}`);
            
            // Handle overnight shifts
            if (d2 < d1) d2.setDate(d2.getDate() + 1);

            let diffMs = d2 - d1;
            let diffMins = Math.floor(diffMs / 60000);
            
            diffMins -= breakMin;
            if (diffMins < 0) diffMins = 0;

            const hours = (diffMins / 60).toFixed(2);
            totalInput.value = hours;
        }
    },

    setupToggleListeners() {
        const travelToggle = document.getElementById('toggle-travel');
        const travelFields = document.getElementById('travel-fields-container');
        if (travelToggle && travelFields) {
            travelToggle.addEventListener('change', (e) => {
                travelFields.style.display = e.target.checked ? 'block' : 'none';
            });
        }

        const ptoToggle = document.getElementById('toggle-pto');
        if (ptoToggle) {
            ptoToggle.addEventListener('change', (e) => {
                if (e.target.checked) this.handlePTOAutoFill();
            });
        }
    },

    handlePTOAutoFill() {
        const timeIn = document.getElementById('time-in');
        const timeOut = document.getElementById('time-out');
        const breakMin = document.getElementById('break-min');

        if (!timeIn.value && !timeOut.value) {
            timeIn.value = "08:00";
            timeOut.value = "17:00";
            breakMin.value = "60";

            if (timeIn.refreshWidget) timeIn.refreshWidget();
            if (timeOut.refreshWidget) timeOut.refreshWidget();

            this.calculateTotal();

            const ptoJob = window.StechTimesheet.state.jobs 
                ? window.StechTimesheet.state.jobs.find(j => parseInt(j.is_pto) === 1) 
                : null;
            
            if (ptoJob) {
                ActivityRows.clear();
                ActivityRows.add(ptoJob.job_name, 100, true);
            }
        }
    },

    setupStateListener() {
        const stateInput = document.getElementById('travel-state');
        const countyList = document.getElementById('county-options');
        if (stateInput) {
            stateInput.addEventListener('change', (e) => {
                const stateName = e.target.value;
                const stateAbbr = window.StechTimesheet.state.stateMap[stateName];
                
                if (countyList) countyList.innerHTML = '';
                
                if (stateAbbr) {
                    window.StechTimesheet.API.getCounties(stateAbbr).then(counties => {
                        counties.forEach(county => {
                            const option = document.createElement('option');
                            option.value = county.county_name;
                            countyList.appendChild(option);
                        });
                    });
                }
            });
        }
    },

    open(date, id = null) {
        this.reset();
        this.currentId = id;
        document.getElementById('timesheet-date').value = date;
        const deleteBtn = document.getElementById('btn-delete');
        
        deleteBtn.textContent = "Delete";
        deleteBtn.style.backgroundColor = ''; 
        this.isArchivedRecord = false;

        if (id) {
            // Edit Existing
            window.StechTimesheet.API.getTimesheetDetails(id).then(data => {
                const isArchived = parseInt(data.archive) === 1;
                const isAdmin = data.is_admin; 

                if (isArchived) {
                    if (!isAdmin) {
                        Dialogs.showError("This record is locked/archived and cannot be edited.");
                        this.currentId = null;
                        return; 
                    } else {
                        this.isArchivedRecord = true;
                        deleteBtn.textContent = "Restore Record";
                        deleteBtn.style.backgroundColor = '#28a745'; 
                        deleteBtn.style.display = 'block';
                    }
                } else {
                    deleteBtn.style.display = 'block';
                }

                this.mapDataToForm(data);
                document.getElementById('timesheet-modal').style.display = 'flex';
            }).catch(err => {
                console.error("Error fetching details", err);
            });
        } else {
            // New Entry
            deleteBtn.style.display = 'none';
            ActivityRows.add('', 0, true);
            document.getElementById('timesheet-modal').style.display = 'flex';
        }
    },

    mapDataToForm(data) {
        document.getElementById('time-in').value = data.time_in || '';
        document.getElementById('time-out').value = data.time_out || '';
        
        // Refresh Time Widgets
        const tIn = document.getElementById('time-in');
        const tOut = document.getElementById('time-out');
        if(tIn.refreshWidget) tIn.refreshWidget();
        if(tOut.refreshWidget) tOut.refreshWidget();
        
        document.getElementById('break-min').value = data.time_break || 0;
        document.getElementById('total-hours').value = data.time_total || 0;
        document.getElementById('additional-comments').value = data.additional_comments || '';
        
        // Travel
        const stateRev = window.StechTimesheet.state.stateMapRev || {};
        document.getElementById('travel-state').value = stateRev[data.travel_state] || '';
        document.getElementById('travel-county').value = data.travel_county || '';
        document.getElementById('travel-miles').value = data.travel_miles || 0;
        document.getElementById('travel-extra-expense').value = data.travel_extra_expenses || 0;
        document.getElementById('req-per-diem').checked = data.travel_per_diem == 1;

        document.getElementById('road-scanning').checked = parseInt(data.travel_road_scanning) === 1;
        document.getElementById('first-last-day').checked = parseInt(data.travel_first_last_day) === 1;
        document.getElementById('overnight').checked = parseInt(data.travel_overnight) === 1;

        if (data.travel_state || data.travel_miles > 0 || 
            data.travel_road_scanning == 1 || 
            data.travel_first_last_day == 1 || 
            data.travel_overnight == 1) {
            document.getElementById('toggle-travel').checked = true;
            document.getElementById('travel-fields-container').style.display = 'block';
        }

        // Activities
        ActivityRows.clear();
        if (data.activities && data.activities.length > 0) {
            data.activities.forEach(act => ActivityRows.add(act.activity_description, act.activity_percent, false));
        } else {
            ActivityRows.add('', 0, true);
        }
    },

    getFormData() {
        const workDesc = [];
        const workPercent = [];
        document.querySelectorAll('.work-desc').forEach(el => workDesc.push(el.value));
        document.querySelectorAll('.work-percent').forEach(el => workPercent.push(el.value));
        
        return {
            timesheet_id: this.currentId,
            date: document.getElementById('timesheet-date').value,
            time_in: document.getElementById('time-in').value,
            time_out: document.getElementById('time-out').value,
            break_min: document.getElementById('break-min').value,
            total_hours: document.getElementById('total-hours').value,
            comments: document.getElementById('additional-comments').value,
            
            // Travel
            state: window.StechTimesheet.state.stateMap[document.getElementById('travel-state').value] || '',
            county: document.getElementById('travel-county').value,
            miles: document.getElementById('travel-miles').value,
            extra_expense: document.getElementById('travel-extra-expense').value,
            req_per_diem: document.getElementById('req-per-diem').checked ? 1 : 0,
            road_scanning: document.getElementById('road-scanning').checked ? 1 : 0,
            first_last_day: document.getElementById('first-last-day').checked ? 1 : 0,
            overnight: document.getElementById('overnight').checked ? 1 : 0,

            work_desc: workDesc,
            work_percent: workPercent
        };
    },

    async handleSubmit() {
        const formData = this.getFormData();
        
        const hasTimeIn = !!formData.time_in;
        const hasPerDiem = !!formData.req_per_diem;
        
        if (!hasTimeIn && !hasPerDiem) {
            const msg = "You must enter a Sign In Time OR request Per Diem to create a record.";
            if (window.OCP && window.OCP.Toast) window.OCP.Toast.error(msg);
            else alert(msg);
            return;
        }

        const hasTimeOut = !!formData.time_out;
        let hasDescription = false;
        if (formData.work_desc && formData.work_desc.length > 0) {
            hasDescription = formData.work_desc.some(desc => desc.trim() !== '');
        }

        if (hasTimeOut && !hasDescription) {
            const msg = "You cannot Clock Out without entering a Job Description.";
            if (window.OCP && window.OCP.Toast) window.OCP.Toast.error(msg);
            else alert(msg);
            return;
        }

        try {
            const res = await window.StechTimesheet.API.saveTimesheet(formData);
            if (res.status === 'success') {
                this.close();
                window.StechTimesheet.Calendar.refresh();
            }
        } catch (err) { 
            console.error('Save failed', err);
        }
    },

    handleDelete() {
        if (!this.currentId) return;
        Dialogs.confirmArchive(() => {
            window.StechTimesheet.API.deleteTimesheet(this.currentId).then(() => {
                this.close();
                window.StechTimesheet.Calendar.refresh();
                if (window.OCP && window.OCP.Toast) window.OCP.Toast.info("Record archived.");
            });
        });
    },

    handleRestore() {
        if (!this.currentId) return;
        Dialogs.confirmRestore(() => {
            window.StechTimesheet.API.restoreTimesheet(this.currentId).then(() => {
                this.close();
                window.StechTimesheet.Calendar.refresh();
                if (window.OCP && window.OCP.Toast) window.OCP.Toast.success("Record restored!");
            });
        });
    },

    reset() {
        this.currentId = null;
        this.isArchivedRecord = false; 
        document.getElementById('timesheet-form').reset();
        
        const tIn = document.getElementById('time-in');
        const tOut = document.getElementById('time-out');
        if(tIn.refreshWidget) tIn.refreshWidget();
        if(tOut.refreshWidget) tOut.refreshWidget();
        
        ActivityRows.clear();
        document.getElementById('travel-fields-container').style.display = 'none';
        document.getElementById('toggle-travel').checked = false;
        
        const ptoToggle = document.getElementById('toggle-pto');
        if (ptoToggle) ptoToggle.checked = false;
    },

    close() { 
        document.getElementById('timesheet-modal').style.display = 'none'; 
    }
};