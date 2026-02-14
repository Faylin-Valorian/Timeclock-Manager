<?php
/**
 * Modular Sidebar Component
 * * @var array $_
 * Variables expected:
 * - mode: 'timesheet' | 'admin' | 'analysis'
 * - permissions: can_access_users, can_access_payroll, etc.
 */

// Configuration for Admin Modules
// To disable a module manually, set 'active' => false.
$adminModules = [
    'users' => [
        'id' => 'nav-users',
        'label' => 'Employees',
        'icon' => 'icon-user',
        'perm' => 'can_access_users',
        'active' => true
    ],
    'access' => [
        'id' => 'nav-access',
        'label' => 'Access Control',
        'icon' => 'icon-password',
        'perm' => 'can_access_access',
        'active' => true
    ],
    'payroll' => [
        'id' => 'nav-payroll',
        'label' => 'Payroll Settings',
        'icon' => 'icon-money',
        'perm' => 'can_access_payroll',
        'active' => true
    ],
    'holidays' => [
        'id' => 'nav-holidays',
        'label' => 'Holidays',
        'icon' => 'icon-calendar-dark',
        'perm' => 'can_access_holidays',
        'active' => true
    ],
    'jobs' => [
        'id' => 'nav-jobs',
        'label' => 'Jobs / Codes',
        'icon' => 'icon-category-office',
        'perm' => 'can_access_jobs',
        'active' => true
    ],
    'locations' => [
        'id' => 'nav-locations',
        'label' => 'Locations',
        'icon' => 'icon-address',
        'perm' => 'can_access_locations',
        'active' => true
    ]
];
?>

<div id="app-navigation">
    <ul class="with-icon">
        
        <?php if ($_['mode'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('timeclock-manager.page.index')); ?>">
                    <span class="icon-history"></span>
                    <span>Back to Timesheet</span>
                </a>
            </li>
            <div class="app-navigation-separator"></div>
            <li class="nav-section-header"><span>Management</span></li>

            <?php foreach ($adminModules as $key => $module): ?>
                <?php if ($module['active'] && !empty($_[$module['perm']])): ?>
                    <li class="nav-item" data-module="<?php p($key); ?>">
                        <a class="nav-link" href="#" id="<?php p($module['id']); ?>">
                            <span class="<?php p($module['icon']); ?>"></span>
                            <span><?php p($module['label']); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>

        <?php else: ?>
            <li class="nav-item">
                <a class="nav-link active" href="#">
                    <span class="icon-history"></span><span>Timesheet</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('timeclock-manager.admin.index')); ?>">
                    <span class="icon-settings-dark"></span><span>Admin Panel</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>