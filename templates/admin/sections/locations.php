<div id="view-locations" class="admin-view hidden">
    <div class="view-header"><h2>Locations</h2><p>Enable or disable states and counties.</p></div>
    <div class="split-layout">
        
        <div class="split-panel left">
            <div class="panel-header-row">
                <span class="panel-title">States</span>
                <div class="search-filter-wrapper" style="width: auto;">
                    <button id="state-filter-btn" class="btn-filter-icon"><span class="icon-filter"></span></button>
                    <div id="state-filter-menu" class="filter-menu hidden">
                        <label><input type="radio" name="state-status" value="enabled" checked> Active States</label>
                        <label><input type="radio" name="state-status" value="disabled"> Inactive States</label>
                    </div>
                </div>
            </div>
            <input type="text" id="state-search-input" class="form-control" placeholder="Search states..." style="margin-bottom: 10px;">
            <div id="state-list" class="scroll-list"></div>
        </div>

        <div class="split-panel right">
            <div class="panel-header-row">
                <span id="county-header" class="panel-title">Counties (Select a State)</span>
                <div class="search-filter-wrapper" style="width: auto;">
                    <button id="county-filter-btn" class="btn-filter-icon"><span class="icon-filter"></span></button>
                    <div id="county-filter-menu" class="filter-menu hidden">
                        <label><input type="radio" name="county-status" value="enabled" checked> Active Counties</label>
                        <label><input type="radio" name="county-status" value="disabled"> Inactive Counties</label>
                    </div>
                </div>
            </div>
            <input type="text" id="county-search-input" class="form-control" placeholder="Search counties..." style="margin-bottom: 10px;" disabled>
            <div id="county-list" class="scroll-list"></div>
        </div>
    </div>
</div>