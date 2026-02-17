<div id="app-navigation" data-mode="<?php p($_['mode'] ?? 'default'); ?>">
    <ul class="with-icon">
        <li class="nav-section-header">
            <div class="date-controls">
                <button id="nav-prev" class="icon-action" title="Previous"></button>
                <div id="date-selector-container">
                    <span id="current-date-label">Loading...</span>
                    <input type="month" id="date-picker-input">
                </div>
                <button id="nav-next" class="icon-action" title="Next"></button>
            </div>
        </li>

        <li class="nav-section-views">
            <div class="view-buttons">
                <button id="view-month" class="primary-button active">Month</button>
                <button id="view-week" class="primary-button">Week</button>
                <button id="view-today" class="secondary-button">Today</button>
                <button id="toggle-archive-view" class="secondary-button" title="Show Archived" style="margin-left: 10px;">
                    <span class="icon-filter"></span>
                </button>
            </div>
        </li>

        <div class="app-navigation-separator"></div>
        
        <li class="nav-item">
            <a class="nav-link active" href="#" id="nav-link-calendar">
                <span class="icon-history"></span><span>Calendar</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="#" id="nav-link-analysis">
                <span class="icon-category-monitoring"></span><span>Time Analysis</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="#" id="nav-link-admin">
                <span class="icon-settings-dark"></span><span>Admin Panel</span>
            </a>
        </li>
    </ul>
</div>
