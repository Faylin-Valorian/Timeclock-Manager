<div id="view-jobs" class="admin-view hidden">
    <div class="view-header"><h2>Job Codes</h2><p>Manage job codes.</p></div>
    <div class="split-layout">
        <div class="split-panel left">
            <div class="panel-header-row">
                <span class="panel-title">Job List</span>
                <div class="search-filter-wrapper" style="width: auto;">
                    <button id="job-filter-btn" class="btn-filter-icon"><span class="icon-filter"></span></button>
                    <div id="job-filter-menu" class="filter-menu hidden">
                        <label><input type="radio" name="job-status" value="active" checked> Active Jobs</label>
                        <label><input type="radio" name="job-status" value="archived"> Archived Jobs</label>
                    </div>
                </div>
            </div>
            <input type="text" id="job-search-input" class="form-control" placeholder="Search jobs..." style="margin-bottom: 10px;">
            <div id="job-list" class="scroll-list"></div>
        </div>
        <div class="split-panel right">
            <h3 id="job-form-title" class="panel-title">Create Job</h3>
            <form id="form-job" class="max-width-600">
                <input type="hidden" id="job-id">
                <div class="input-group"><label>Job Code / Name</label><input type="text" id="job-name" class="form-control" required></div>
                <div class="input-group"><label>Description</label><textarea id="job-desc" class="form-control" rows="3"></textarea></div>
                
                <div class="input-group" style="display: flex; align-items: center; gap: 10px; border: 1px solid var(--color-border); padding: 10px; border-radius: 4px; background: var(--color-main-background);">
                    <label class="admin-switch" style="margin:0;"><input type="checkbox" id="job-is-pto"><span class="admin-slider"></span></label>
                    <span style="font-weight: bold; font-size: 0.9em; opacity: 0.8;">Is Vacation / Sick Record?</span>
                </div>
                
                <div class="form-separator"></div>
                <h4>Financials</h4>
                <div class="input-group"><label>Estimated Revenue ($)</label><input type="number" step="0.01" id="job-revenue" class="form-control"></div>
                <div class="input-group"><label>Expense Budget ($)</label><input type="number" step="0.01" id="job-expense" class="form-control"></div>
                <div class="input-group"><label>Hourly Cost Estimate ($)</label><input type="number" step="0.01" id="job-hourly" class="form-control"></div>
                
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" id="btn-save-job" class="primary-button full-width">Create Job</button>
                    <button type="button" id="btn-cancel-job" class="secondary-button hidden">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>