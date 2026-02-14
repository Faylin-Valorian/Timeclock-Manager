<?php

return [
    'routes' => [
        // =========================================================================
        // 1. PAGE ROUTES (Frontend Views)
        // Handled by lib/Controller/PageController.php
        // =========================================================================
        
        // Main Timesheet App (Calendar & Entry)
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        
        // Modular Admin Panel Shell
        ['name' => 'page#admin_index', 'url' => '/admin', 'verb' => 'GET'],
        
        // Analysis / Reporting Dashboard
        ['name' => 'page#analysis_index', 'url' => '/analysis', 'verb' => 'GET'],


        // =========================================================================
        // 2. CORE API (Shared Data)
        // =========================================================================
        
        // Get initial startup data (Job codes, State lists, User config)
        // Handled by lib/Timesheet/EntryForm/Controller/EntryFormController.php
        ['name' => 'timesheet_entry#getAttributes', 'url' => '/api/attributes', 'verb' => 'GET'],


        // =========================================================================
        // 3. TIMESHEET API (Calendar & Form Operations)
        // =========================================================================
        
        // Fetch Calendar Events (Timesheets)
        // Handled by lib/Timesheet/Calendar/Controller/CalendarController.php
        ['name' => 'timesheet_calendar#getTimesheets', 'url' => '/api/timesheets', 'verb' => 'GET'],
        ['name' => 'timesheet_calendar#getHolidays', 'url' => '/api/calendar/holidays', 'verb' => 'GET'],

        // Form Operations (Save/Delete/Restore)
        // Handled by lib/Timesheet/EntryForm/Controller/EntryFormController.php
        ['name' => 'timesheet_entry#saveTimesheet', 'url' => '/api/timesheets', 'verb' => 'POST'],
        ['name' => 'timesheet_entry#getTimesheet', 'url' => '/api/timesheets/{id}', 'verb' => 'GET'],
        ['name' => 'timesheet_entry#deleteTimesheet', 'url' => '/api/timesheets/{id}', 'verb' => 'DELETE'],
        ['name' => 'timesheet_entry#restoreTimesheet', 'url' => '/api/timesheets/{id}/restore', 'verb' => 'POST'],


        // =========================================================================
        // 4. ADMIN MODULES API
        // =========================================================================

        // --- Module: USERS ---
        // Handled by lib/Admin/Users/Controller/UsersController.php
        ['name' => 'admin_users#getUsers', 'url' => '/api/admin/users', 'verb' => 'GET'],
        ['name' => 'admin_users#toggleUser', 'url' => '/api/admin/users/toggle', 'verb' => 'POST'],

        // --- Module: ACCESS CONTROL ---
        // Handled by lib/Admin/Access/Controller/AccessController.php
        ['name' => 'admin_access#getGroups', 'url' => '/api/admin/groups', 'verb' => 'GET'],
        ['name' => 'admin_access#getAccess', 'url' => '/api/admin/access', 'verb' => 'GET'],
        ['name' => 'admin_access#saveAccess', 'url' => '/api/admin/access', 'verb' => 'POST'],

        // --- Module: PAYROLL ---
        // Handled by lib/Admin/Payroll/Controller/PayrollController.php
        ['name' => 'admin_payroll#getSettings', 'url' => '/api/admin/settings', 'verb' => 'GET'],
        ['name' => 'admin_payroll#saveSetting', 'url' => '/api/admin/settings', 'verb' => 'POST'],
        // Optional: Background upload route if you decide to keep it
        ['name' => 'admin_payroll#uploadPayrollBg', 'url' => '/api/admin/payroll/bg', 'verb' => 'POST'],

        // --- Module: HOLIDAYS ---
        // Handled by lib/Admin/Holidays/Controller/HolidaysController.php
        ['name' => 'admin_holidays#getHolidays', 'url' => '/api/admin/holidays', 'verb' => 'GET'],
        ['name' => 'admin_holidays#saveHoliday', 'url' => '/api/admin/holidays', 'verb' => 'POST'],
        ['name' => 'admin_holidays#toggleHoliday', 'url' => '/api/admin/holidays/{id}/toggle', 'verb' => 'POST'],

        // --- Module: JOBS ---
        // Handled by lib/Admin/Jobs/Controller/JobsController.php
        ['name' => 'admin_jobs#getJobs', 'url' => '/api/admin/jobs', 'verb' => 'GET'],
        ['name' => 'admin_jobs#saveJob', 'url' => '/api/admin/jobs', 'verb' => 'POST'],
        ['name' => 'admin_jobs#toggleJob', 'url' => '/api/admin/jobs/{id}/toggle', 'verb' => 'POST'],

        // --- Module: LOCATIONS ---
        // Handled by lib/Admin/Locations/Controller/LocationsController.php
        ['name' => 'admin_locations#getStates', 'url' => '/api/admin/states', 'verb' => 'GET'],
        ['name' => 'admin_locations#toggleState', 'url' => '/api/admin/states/{id}/toggle', 'verb' => 'POST'],
        ['name' => 'admin_locations#getCounties', 'url' => '/api/admin/counties/{abbr}', 'verb' => 'GET'],
        ['name' => 'admin_locations#toggleCounty', 'url' => '/api/admin/counties/{id}/toggle', 'verb' => 'POST'],


        // =========================================================================
        // 5. ANALYSIS API
        // =========================================================================
        
        // Common Filters
        // Handled by lib/Analysis/Overview/Controller/OverviewController.php (or shared AnalysisController)
        ['name' => 'analysis_overview#getFilters', 'url' => '/api/analysis/filters', 'verb' => 'GET'],

        // Module: Overview
        // Handled by lib/Analysis/Overview/Controller/OverviewController.php
        ['name' => 'analysis_overview#getStats', 'url' => '/api/analysis/stats', 'verb' => 'GET'],

        // Module: Dashboard
        // Handled by lib/Analysis/Dashboard/Controller/DashboardController.php
        ['name' => 'analysis_dashboard#getData', 'url' => '/api/analysis/dashboard', 'verb' => 'GET'],

        // Module: Job Breakdown
        // Handled by lib/Analysis/JobBreakdown/Controller/JobBreakdownController.php
        ['name' => 'analysis_job_breakdown#getData', 'url' => '/api/analysis/job_breakdown', 'verb' => 'GET'],

        // Module: Job Profitability
        // Handled by lib/Analysis/JobProfitability/Controller/JobProfitabilityController.php
        ['name' => 'analysis_job_profitability#getData', 'url' => '/api/analysis/job_profitability', 'verb' => 'GET'],

        // Module: Travel
        // Handled by lib/Analysis/Travel/Controller/TravelController.php
        ['name' => 'analysis_travel#getData', 'url' => '/api/analysis/travel', 'verb' => 'GET'],
    ]
];