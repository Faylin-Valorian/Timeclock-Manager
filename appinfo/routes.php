<?php
return [
    'routes' => [
        // Frontend
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

        // Calendar Module (Read-Only Visualization)
        ['name' => 'calendar#getTimesheets', 'url' => '/api/timesheets', 'verb' => 'GET'],
        ['name' => 'calendar#getHolidays', 'url' => '/api/calendar/holidays', 'verb' => 'GET'],
        ['name' => 'analysis#getSummary', 'url' => '/api/analysis/summary', 'verb' => 'GET'],
        ['name' => 'analysis#getDetail', 'url' => '/api/analysis/detail', 'verb' => 'GET'],
        ['name' => 'admin#getMyAccess', 'url' => '/api/admin/access/me', 'verb' => 'GET'],
        ['name' => 'admin#getBootstrap', 'url' => '/api/admin/bootstrap', 'verb' => 'GET'],
        ['name' => 'admin#saveHoliday', 'url' => '/api/admin/holidays', 'verb' => 'POST'],
        ['name' => 'admin#saveAccess', 'url' => '/api/admin/access', 'verb' => 'POST'],

        // [NEW] Timesheet Module (Write/CRUD Operations)
        ['name' => 'timesheet#getAttributes', 'url' => '/api/attributes', 'verb' => 'GET'],
        ['name' => 'timesheet#getTimesheet', 'url' => '/api/timesheets/{id}', 'verb' => 'GET'],
        ['name' => 'timesheet#saveTimesheet', 'url' => '/api/timesheets', 'verb' => 'POST'],
        ['name' => 'timesheet#deleteTimesheet', 'url' => '/api/timesheets/{id}', 'verb' => 'DELETE'],
        ['name' => 'timesheet#restoreTimesheet', 'url' => '/api/timesheets/{id}/restore', 'verb' => 'POST'],
        
        // Helper for State/County dropdowns in the form
        ['name' => 'timesheet#getCounties', 'url' => '/api/locations/counties/{abbr}', 'verb' => 'GET'],
    ]
];
