<div id="view-holidays" class="admin-view hidden">
    <div class="view-header"><h2>Holiday Calendar</h2><p>Define company holidays.</p></div>
    <div class="split-layout">
        <div class="split-panel left">
            <div class="panel-header-row">
                <span class="panel-title">Holiday List</span>
                <div class="search-filter-wrapper" style="width: auto;">
                    <button id="holiday-filter-btn" class="btn-filter-icon"><span class="icon-filter"></span></button>
                    <div id="holiday-filter-menu" class="filter-menu hidden">
                        <label><input type="radio" name="holiday-status" value="active" checked> Active</label>
                        <label><input type="radio" name="holiday-status" value="archived"> Archived</label>
                    </div>
                </div>
            </div>
            <input type="text" id="holiday-search-input" class="form-control" placeholder="Search holidays..." style="margin-bottom: 10px;">
            <div id="holiday-list" class="scroll-list"></div>
        </div>
        <div class="split-panel right">
            <h3 id="holiday-form-title" class="panel-title">Add Holiday</h3>
            <form id="form-holiday" class="max-width-600">
                <input type="hidden" id="holiday-id">
                <div class="input-group"><label>Holiday Name</label><input type="text" id="holiday-name" class="form-control" required></div>
                <div class="input-group"><label>Start Date</label><input type="date" id="holiday-start" class="form-control" required></div>
                <div class="input-group"><label>End Date (Optional)</label><input type="date" id="holiday-end" class="form-control"></div>
                
                <div class="input-group">
                    <label>Holiday Color</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="color" id="holiday-color" value="#e67e22" style="height: 36px; width: 60px; padding: 0; border: 1px solid var(--color-border); cursor: pointer;">
                        <input type="text" id="holiday-color-text" value="#e67e22" class="form-control" style="width: 100px; font-family: monospace;" maxlength="7">
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" id="btn-save-holiday" class="primary-button full-width">Add Holiday</button>
                    <button type="button" id="btn-cancel-holiday" class="secondary-button hidden">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>