<div id="view-payroll" class="admin-view hidden">
    <div class="view-header">
        <h2>Payroll Configuration</h2>
        <p>Configure the visual "Payroll" tab appearance on the calendar.</p>
    </div>
    <div class="view-body" style="max-width: 600px;">
        <div class="form-section">
            <div class="input-group">
                <label>Pay Frequency</label>
                <select id="pay-frequency" class="form-control">
                    <option value="14">Bi-Weekly (Every 2 Weeks)</option>
                    <option value="7">Weekly</option>
                    <option value="28">Every 4 Weeks</option>
                    <option value="custom_twice">Custom (Twice a Month)</option>
                </select>
            </div>
            
            <div id="freq-standard-options" class="input-group" style="margin-top: 15px;">
                <label>Reference Start Date</label>
                <p style="font-size: 0.85em; opacity: 0.7; margin-top:0; margin-bottom:5px;">Pick any valid Pay Day.</p>
                <input type="date" id="pay-start-date" class="form-control">
            </div>

            <div id="freq-custom-options" class="hidden" style="margin-top: 15px; border-left: 3px solid var(--color-primary-element); padding-left: 15px;">
                <label style="margin-bottom:10px;">Recurring Pay Days</label>
                <div style="display: flex; gap: 15px;">
                    <div style="flex:1;">
                        <label style="font-size: 0.85em; opacity:0.8;">First Pay Day</label>
                        <select id="pay-date-1" class="form-control">
                            <?php for($i=1; $i<=28; $i++) echo "<option value='$i'>$i" . date("S", mktime(0,0,0,0,$i,0)) . "</option>"; ?>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label style="font-size: 0.85em; opacity:0.8;">Second Pay Day</label>
                        <select id="pay-date-2" class="form-control">
                            <?php for($i=1; $i<=28; $i++) echo "<option value='$i'>$i" . date("S", mktime(0,0,0,0,$i,0)) . "</option>"; ?>
                        </select>
                    </div>
                </div>
                <p style="font-size: 0.85em; opacity: 0.7; margin-top:10px;">Select the two days of the month employees are paid.</p>
            </div>

            <div class="input-group" style="margin-top: 20px;">
                <label>Payroll Overlay Color</label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="color" id="pay-color" value="#34495e" style="height: 36px; width: 60px; padding: 0; border: 1px solid var(--color-border); cursor: pointer;">
                    <input type="text" id="pay-color-text" value="#34495e" class="form-control" style="width: 100px; font-family: monospace;" maxlength="7">
                </div>
            </div>

            <div style="margin-top: 25px; display: flex; align-items: center; gap: 15px;">
                <button id="btn-save-payroll" class="primary-button">Save Settings</button>
                <span id="payroll-msg" style="color: var(--color-success); font-weight: bold; display: none;">Saved!</span>
            </div>
        </div>
    </div>
</div>