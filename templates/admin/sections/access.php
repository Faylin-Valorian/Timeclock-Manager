<div id="view-access" class="admin-view hidden">
    <div class="view-header"><h2>Access Control</h2><p>Grant permission to specific features.</p></div>
    <div class="view-body" style="max-width: 900px;">
        <div class="split-layout">
            <div class="split-panel left" style="width: 250px;">
                <h4 class="panel-title">Settings Categories</h4>
                <div class="access-tab active" data-target="group-general">General Settings</div>
                <div class="access-tab" data-target="group-admin">Admin Settings</div>
                <div class="access-tab" data-target="group-analysis">Analysis Settings</div>
            </div>

            <div class="split-panel right">
                
                <div id="group-general" class="access-group-panel">
                    <h3 class="panel-title">General Settings</h3>
                    <p class="panel-desc">Basic application visibility settings.</p>
                    
                    <div class="setting-card" style="margin-top:20px; padding:15px; border:1px solid var(--color-border); border-radius:var(--border-radius);">
                        <h4 style="margin-top:0;">Archive / Filter Toggle</h4>
                        <p style="font-size:0.9em; opacity:0.7;">Controls visibility of the "Show Archived" button on the main calendar.</p>
                        <div id="list-access-archive" class="group-toggle-list"></div>
                    </div>
                </div>

                <div id="group-admin" class="access-group-panel hidden">
                    <h3 class="panel-title">Admin Panel Settings</h3>
                    <p class="panel-desc">Control access to the admin area and its specific modules.</p>

                    <div class="setting-card" style="margin-top:20px; margin-bottom:25px; padding:15px; border:1px solid var(--color-border); border-radius:var(--border-radius); background-color: var(--color-background-hover);">
                        <h4 style="margin-top:0;">Admin Panel Button (Global)</h4>
                        <p style="font-size:0.9em; opacity:0.7;">Who can see the "Admin Panel" link in the main navigation sidebar?</p>
                        <div id="list-access-admin-global" class="group-toggle-list"></div>
                    </div>

                    <div style="border-top:1px solid var(--color-border); margin: 20px 0; padding-top:20px;">
                        <h4 class="panel-title">Sidebar Modules</h4>
                        <p style="font-size:0.9em; margin-bottom:15px;">Grant access to specific pages within the Admin Panel.</p>
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div><h5 style="margin-bottom:5px;">Access Control</h5><div id="list-access-admin-access" class="group-toggle-list"></div></div>
                            <div><h5 style="margin-bottom:5px;">Employees</h5><div id="list-access-admin-users" class="group-toggle-list"></div></div>
                            <div><h5 style="margin-bottom:5px;">Payroll</h5><div id="list-access-admin-payroll" class="group-toggle-list"></div></div>
                            <div><h5 style="margin-bottom:5px;">Holidays</h5><div id="list-access-admin-holidays" class="group-toggle-list"></div></div>
                            <div><h5 style="margin-bottom:5px;">Jobs</h5><div id="list-access-admin-jobs" class="group-toggle-list"></div></div>
                            <div><h5 style="margin-bottom:5px;">Locations</h5><div id="list-access-admin-locations" class="group-toggle-list"></div></div>
                        </div>
                    </div>
                </div>

                <div id="group-analysis" class="access-group-panel hidden">
                    <h3 class="panel-title">Analysis Settings</h3>
                    <p class="panel-desc">Configure what users can see in the Time Analysis tab.</p>

                    <div class="setting-card" style="margin-top:20px; margin-bottom:25px; padding:15px; border:1px solid var(--color-border); border-radius:var(--border-radius);">
                        <h4 style="margin-top:0;">Main Analysis Tab</h4>
                        <p style="font-size:0.9em; opacity:0.7;">Who can access the Analysis page at all?</p>
                        <div id="list-access-analysis-tab" class="group-toggle-list"></div>
                    </div>

                    <div style="border-top:1px solid var(--color-border); margin: 20px 0; padding-top:20px;">
                        <h4 class="panel-title">Specific Features</h4>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div><h5 style="margin-bottom:5px;">View All Employees</h5><div id="list-access-analysis-others" class="group-toggle-list"></div></div>
                            <div><h5 style="margin-bottom:5px;">Travel Tab</h5><div id="list-access-analysis-travel" class="group-toggle-list"></div></div>
                            <div><h5 style="margin-bottom:5px;">Financial Tab</h5><div id="list-access-analysis-financial" class="group-toggle-list"></div></div>
                            <div><h5 style="margin-bottom:5px;">Location Tab</h5><div id="list-access-analysis-location" class="group-toggle-list"></div></div>
                            <div><h5 style="margin-bottom:5px;">Job Breakdown</h5><div id="list-access-analysis-jobs" class="group-toggle-list"></div></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>