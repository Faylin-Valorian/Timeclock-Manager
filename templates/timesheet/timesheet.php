<div id="timesheet-modal" class="modal-overlay" style="display: none;">
    
    <datalist id="time-options">
        <?php 
        for($h=0; $h<24; $h++) {
            foreach([0, 15, 30, 45] as $m) {
                $time = sprintf("%02d:%02d", $h, $m);
                // Format to AM/PM for display
                $display = date("g:i A", strtotime($time)); 
                echo "<option value=\"$display\">";
            }
        }
        ?>
    </datalist>

    <div class="modal-card">
        <form id="timesheet-form">
            <div class="modal-header">
                <div>
                    <h2 id="modal-date-title">Entry Details</h2>
                    <span class="modal-subtitle">Daily Work Record</span>
                </div>
                <button type="button" class="close-modal text-button" style="font-size: 24px;">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-section">
                    <div class="form-row-4">
                        <div class="input-group">
                            <label>Date</label>
                            <input type="date" id="timesheet-date" name="date" class="form-control" readonly>
                        </div>
                        
                        <div class="input-group">
                            <label>Time In</label>
                            <div class="time-widget-wrapper">
                                <input type="text" id="time-in" class="form-control smart-time-input" placeholder="e.g. 8:00 AM" autocomplete="off">
                                <div class="time-popover" style="display:none;">
                                    <select class="time-select-h"><?php for($i=1;$i<=12;$i++) echo "<option>".sprintf("%02d",$i)."</option>"; ?></select>
                                    <span class="colon">:</span>
                                    <select class="time-select-m"><?php for($i=0;$i<60;$i++) echo "<option>".sprintf("%02d",$i)."</option>"; ?></select>
                                    <select class="time-select-ampm"><option>AM</option><option>PM</option></select>
                                </div>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Time Out</label>
                            <div class="time-widget-wrapper">
                                <input type="text" id="time-out" class="form-control smart-time-input" placeholder="e.g. 5:00 PM" autocomplete="off">
                                <div class="time-popover" style="display:none;">
                                    <select class="time-select-h"><?php for($i=1;$i<=12;$i++) echo "<option>".sprintf("%02d",$i)."</option>"; ?></select>
                                    <span class="colon">:</span>
                                    <select class="time-select-m"><?php for($i=0;$i<60;$i++) echo "<option>".sprintf("%02d",$i)."</option>"; ?></select>
                                    <select class="time-select-ampm"><option>AM</option><option>PM</option></select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label>Break (min)</label>
                            <input type="number" id="break-min" class="form-control" value="0">
                        </div>
                    </div>

                    <div class="form-row-1" style="margin-top: 10px;">
                        <div class="input-group">
                            <label>Total Hours</label>
                            <input type="text" id="total-hours" class="form-control" readonly style="font-weight: bold; text-align: left;">
                        </div>
                    </div>
                </div>

                <div class="form-separator"></div>

                <div class="form-section">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; align-items:center;">
                        <h3>Work Breakdown</h3>
                        <button type="button" id="btn-add-row" class="text-button">+ Add Item</button>
                    </div>
                    <div id="work-rows-container"></div>
                </div>

                <div class="form-separator"></div>
                
                <div class="toggle-row-container">
                    <div class="toggle-wrapper"><input type="checkbox" id="toggle-travel"><label for="toggle-travel"><span>🚗</span> Travel / Expenses</label></div>
                </div>

                <div id="travel-fields-container" class="travel-box hidden-section">
                    <div class="travel-toggles-grid">
                        <div class="toggle-wrapper"><input type="checkbox" id="req-per-diem"><label for="req-per-diem">Per Diem</label></div>
                        <div class="toggle-wrapper"><input type="checkbox" id="road-scanning"><label for="road-scanning">Road Scanning</label></div>
                        <div class="toggle-wrapper"><input type="checkbox" id="first-last-day"><label for="first-last-day">First/Last Day</label></div>
                        <div class="toggle-wrapper"><input type="checkbox" id="overnight"><label for="overnight">Overnight</label></div>
                    </div>
                    
                    <div class="form-row-3">
                        <div class="input-group">
                            <label>State</label>
                            <input list="state-options" id="travel-state" class="form-control" placeholder="Select...">
                            <datalist id="state-options"></datalist>
                        </div>
                        
                        <div class="input-group">
                            <label>County</label>
                            <input list="county-options" id="travel-county" class="form-control" placeholder="Type or Select...">
                            <datalist id="county-options"></datalist>
                        </div>
                        
                        <div class="input-group">
                            <label>Miles</label>
                            <input type="number" id="travel-miles" class="form-control">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Extra Expenses ($)</label>
                        <input type="number" id="travel-extra-expense" class="form-control">
                    </div>
                </div>

                <div class="form-separator"></div>
                <div class="input-group">
                    <label>Comments</label>
                    <textarea id="additional-comments" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btn-delete" class="action-icon" style="color:red; margin-right:auto;">Delete</button>
                <button type="button" class="close-modal secondary-button">Cancel</button>
                <button type="submit" class="primary-button">Save</button>
            </div>
        </form>
    </div>
</div>