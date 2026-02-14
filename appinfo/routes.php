<?php

return [
    'routes' => [
        // =========================================================================
        // 1. FRONTEND PAGES (Views)
        // Handled by PageController
        // =========================================================================
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#admin_index', 'url' => '/admin', 'verb' => 'GET'],
        ['name' => 'page#analysis_index', 'url' => '/analysis', 'verb' => 'GET'],


        // =========================================================================
        // 2. TIMESHEET - CALENDAR MODULE (Read-Only Visualization)
        // Handled by CalendarController
        // =========================================================================
        ['name' => 'calendar#getTimesheets', 'url' => '/api/timesheets', 'verb' => 'GET'],
        ['name' => 'calendar#getHolidays', 'url' => '/api/calendar/holidays', 'verb' => 'GET'],


        // =========================================================================
        // 3. TIMESHEET - ENTRY FORM MODULE (Write/CRUD Operations)
        // Handled by EntryFormController
        // =========================================================================
        ['name' => 'entry_form#getAttributes', 'url' => '/api/attributes', 'verb' => 'GET'],
        ['name' => 'entry_form#saveTimesheet', 'url' => '/api/timesheets', 'verb' => 'POST'],
        ['name' => 'entry_form#getTimesheet', 'url' => '/api/timesheets/{id}', 'verb' => 'GET'],
        ['name' => 'entry_form#deleteTimesheet', 'url' => '/api/timesheets/{id}', 'verb' => 'DELETE'],
        ['name' => 'entry_form#restoreTimesheet', 'url' => '/api/timesheets/{id}/restore', 'verb' => 'POST'],
        ['name' => 'entry_form#getCounties', 'url' => '/api/admin/counties/{abbr}', 'verb' => 'GET'],


        // =========================================================================
        // 4. ADMIN MODULES API
        // Handled by specific Admin Controllers
        // =========================================================================

        // --- Module: USERS ---
        ['name' => 'admin_users#getUsers', 'url' => '/api/admin/users', 'verb' => 'GET'],
        ['name' => 'admin_users#toggleUser', 'url' => '/api/admin/users/toggle', 'verb' => 'POST'],

        // --- Module: ACCESS CONTROL ---
        ['name' => 'admin_access#getGroups', 'url' => '/api/admin/groups', 'verb' => 'GET'],
        ['name' => 'admin_access#getAccess', 'url' => '/api/admin/access', 'verb' => 'GET'],
        ['name' => 'admin_access#saveAccess', 'url' => '/api/admin/access', 'verb' => 'POST'],

        // --- Module: PAYROLL ---
        ['name' => 'admin_payroll#getSettings', 'url' => '/api/admin/settings', 'verb' => 'GET'],
        ['name' => 'admin_payroll#saveSetting', 'url' => '/api/admin/settings', 'verb' => 'POST'],

        // --- Module: HOLIDAYS (Management) ---
        ['name' => 'admin_holidays#getHolidays', 'url' => '/api/admin/holidays', 'verb' => 'GET'],
        ['name' => 'admin_holidays#saveHoliday', 'url' => '/api/admin/holidays', 'verb' => 'POST'],
        ['name' => 'admin_holidays#toggleHoliday', 'url' => '/api/admin/holidays/{id}/toggle', 'verb' => 'POST'],

        // --- Module: JOBS ---
        ['name' => 'admin_jobs#getJobs', 'url' => '/api/admin/jobs', 'verb' => 'GET'],
        ['name' => 'admin_jobs#saveJob', 'url' => '/api/admin/jobs', 'verb' => 'POST'],
        ['name' => 'admin_jobs#toggleJob', 'url' => '/api/admin/jobs/{id}/toggle', 'verb' => 'POST'],

        // --- Module: LOCATIONS ---
        ['name' => 'admin_locations#getStates', 'url' => '/api/admin/states', 'verb' => 'GET'],
        ['name' => 'admin_locations#toggleState', 'url' => '/api/admin/states/{id}/toggle', 'verb' => 'POST'],
        ['name' => 'admin_locations#getCounties', 'url' => '/api/admin/counties/{abbr}', 'verb' => 'GET'],
        ['name' => 'admin_locations#toggleCounty', 'url' => '/api/admin/counties/{id}/toggle', 'verb' => 'POST'],


        // =========================================================================
        // 5. ANALYSIS API (Optional)
        // Handled by AnalysisController (if enabled)
        // =========================================================================
        ['name' => 'analysis#getStats', 'url' => '/api/analysis/stats', 'verb' => 'GET'],
    ]
];