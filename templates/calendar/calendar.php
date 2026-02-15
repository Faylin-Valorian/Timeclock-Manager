<<<<<<< HEAD
<?php
use OCP\Util;

// Scripts
Util::addScript('timeclock-manager', 'calendar'); 

// Styles
Util::addStyle('timeclock-manager', 'sidebar'); 
Util::addStyle('timeclock-manager', 'calendar');
Util::addStyle('timeclock-manager', 'timesheet');
?>

<div id="app">
    <?php print_unescaped($this->inc('components/sidebar', ['mode' => 'calendar'])); ?>

    <div id="app-content">
        <div id="app-content-wrapper">
            <div id="calendar-container">
                <div id="calendar"></div>
                
                <?php print_unescaped($this->inc('timesheet/timesheet')); ?>
            </div>
        </div>
    </div>
=======
<?php
use OCP\Util;

// Scripts
Util::addScript('timeclock-manager', 'calendar'); 

// Styles
Util::addStyle('timeclock-manager', 'sidebar'); 
Util::addStyle('timeclock-manager', 'calendar');
Util::addStyle('timeclock-manager', 'timesheet');
?>

<div id="app">
    <?php print_unescaped($this->inc('components/sidebar', ['mode' => 'calendar'])); ?>

    <div id="app-content">
        <div id="app-content-wrapper">
            <div id="calendar-container">
                <div id="calendar"></div>
                
                <?php print_unescaped($this->inc('timesheet/timesheet')); ?>
            </div>
        </div>
    </div>
>>>>>>> 6edf4541514ac05df3faedfa2f961d65a5495869
</div>