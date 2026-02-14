<?php
use OCP\Util;

// Load the compiled Admin JavaScript and Styles
Util::addScript('timeclock-manager', 'admin'); 
Util::addStyle('timeclock-manager', 'admin');

// ---------------------------------------------------------------------------
// MODULE CONFIGURATION
// To completely remove a feature from the backend rendering, delete it here 
// OR simply delete the file in templates/admin/panels/
// ---------------------------------------------------------------------------
$panels = [
    'users'     => ['file' => 'users.php',     'perm' => 'can_access_users'],
    'access'    => ['file' => 'access.php',    'perm' => 'can_access_access'],
    'payroll'   => ['file' => 'payroll.php',   'perm' => 'can_access_payroll'],
    'holidays'  => ['file' => 'holidays.php',  'perm' => 'can_access_holidays'],
    'jobs'      => ['file' => 'jobs.php',      'perm' => 'can_access_jobs'],
    'locations' => ['file' => 'locations.php', 'perm' => 'can_access_locations']
];
?>

<div id="app">
    <?php print_unescaped($this->inc('components/sidebar', ['mode' => 'admin'])); ?>

    <div id="app-content">
        <?php foreach ($panels as $id => $panel): ?>
            <?php 
                // Check 1: Does the user have permission?
                if (!empty($_[$panel['perm']])):
                    try {
                        // Check 2: Try to load the module file. 
                        // If the file was deleted to "remove" the feature, this gracefully fails/skips.
                        print_unescaped($this->inc('admin/panels/' . pathinfo($panel['file'], PATHINFO_FILENAME))); 
                    } catch (\Exception $e) {
                        // Feature is missing or deleted. Do nothing.
                    }
                endif; 
            ?>
        <?php endforeach; ?>
    </div>
</div>