<?php
use OCP\Util;

// Scripts
Util::addScript('timeclock-manager', 'admin');
Util::addScript('timeclock-manager', 'calendar'); 

// Styles
Util::addStyle('timeclock-manager', 'sidebar'); 
Util::addStyle('timeclock-manager', 'calendar');
Util::addStyle('timeclock-manager', 'admin');
Util::addStyle('timeclock-manager', 'timesheet');
?>

<div id="app">
    <?php print_unescaped($this->inc('components/sidebar', ['mode' => 'calendar'])); ?>

    <div id="app-content">
        <div id="app-content-wrapper">
            <div id="calendar-container">
                <div id="impersonation-banner" style="display:none;">
                    <span id="impersonation-text"></span>
                    <button type="button" id="impersonation-clear">End Impersonation</button>
                </div>
                <div id="archive-mode-banner" style="display:none;">
                    <span id="archive-mode-text">Viewing archive records.</span>
                </div>
                <div id="calendar"></div>
                
                <?php print_unescaped($this->inc('timesheet/timesheet')); ?>
                <?php print_unescaped($this->inc('analysis/overlay')); ?>
                <?php print_unescaped($this->inc('admin/overlay')); ?>
            </div>
        </div>
    </div>
</div>
