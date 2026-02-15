<?php
return [
    'routes' => [
        // Frontend
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

        // Calendar Module (Read-Only Visualization)
        ['name' => 'calendar#getTimesheets', 'url' => '/api/timesheets', 'verb' => 'GET'],
        ['name' => 'calendar#getHolidays', 'url' => '/api/calendar/holidays', 'verb' => 'GET'],

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