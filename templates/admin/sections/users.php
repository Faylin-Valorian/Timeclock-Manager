<div id="view-users" class="admin-view hidden">
    <div class="view-header">
        <div class="panel-header-row">
            <div>
                <h2>Employee Management</h2>
                <p>Manage access, view timesheets, and set active status.</p>
            </div>
            <div class="action-buttons">
                <div class="search-filter-wrapper">
                    <button id="user-filter-btn" class="btn-filter-icon">
                        <span class="icon-filter"></span>
                    </button>
                    <div id="user-filter-menu" class="filter-menu hidden">
                        <label><input type="radio" name="user-status" value="active" checked> Active</label>
                        <label><input type="radio" name="user-status" value="inactive"> Inactive</label>
                        <label><input type="radio" name="user-status" value="all"> All</label>
                    </div>
                    <input type="text" id="user-search-input" class="form-control" placeholder="Search employees...">
                </div>
            </div>
        </div>
    </div>
    
    <div class="view-body">
        <div id="user-grid-container" class="user-grid">
            </div>
    </div>
</div>