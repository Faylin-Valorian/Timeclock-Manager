<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\Files\IAppData;
use OCP\INavigationManager;

// --- Core Frontend Controllers ---
use OCA\TimeclockManager\Controller\PageController;
use OCA\TimeclockManager\Controller\AdminController;

// --- Timesheet Modules (Calendar & Form) ---
use OCA\TimeclockManager\Timesheet\Calendar\Controller\CalendarController;
use OCA\TimeclockManager\Timesheet\Calendar\Service\CalendarService;
use OCA\TimeclockManager\Timesheet\Calendar\Db\CalendarMapper;

use OCA\TimeclockManager\Timesheet\EntryForm\Controller\EntryFormController;
use OCA\TimeclockManager\Timesheet\EntryForm\Service\EntryFormService;
use OCA\TimeclockManager\Timesheet\EntryForm\Db\EntryFormMapper;

// --- Analysis Module (Optional) ---
use OCA\TimeclockManager\Controller\AnalysisController;
use OCA\TimeclockManager\Service\AnalysisService;
use OCA\TimeclockManager\Db\AnalysisMapper;
use OCA\TimeclockManager\Db\TimesheetMapper; // Legacy Mapper if needed by Analysis

// --- Admin Modules ---
use OCA\TimeclockManager\Admin\Users\Controller\UsersController;
use OCA\TimeclockManager\Admin\Access\Controller\AccessController;
use OCA\TimeclockManager\Admin\Payroll\Controller\PayrollController;
use OCA\TimeclockManager\Admin\Holidays\Controller\HolidaysController;
use OCA\TimeclockManager\Admin\Jobs\Controller\JobsController;
use OCA\TimeclockManager\Admin\Locations\Controller\LocationsController;

class Application extends App implements IBootstrap {

    public const APP_ID = 'timeclock-manager';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        
        // =====================================================================
        // 1. TIMESHEET MODULES
        // =====================================================================
        
        // --- Calendar (Read Only) ---
        $context->registerService(CalendarMapper::class, function($c) { 
            return new CalendarMapper($c->get(IDBConnection::class)); 
        });
        $context->registerService(CalendarService::class, function($c) { 
            return new CalendarService($c->get(CalendarMapper::class)); 
        });
        // Alias 'CalendarController' to match route 'calendar#method'
        $context->registerService('CalendarController', function($c) {
             return new CalendarController(
                 $c->get(IRequest::class), 
                 $c->get(IUserSession::class), 
                 $c->get(CalendarService::class), 
                 $c->get(CalendarMapper::class), 
                 $c->get(IGroupManager::class)
             );
        });

        // --- Entry Form (CRUD) ---
        $context->registerService(EntryFormMapper::class, function($c) { 
            return new EntryFormMapper($c->get(IDBConnection::class)); 
        });
        $context->registerService(EntryFormService::class, function($c) { 
            return new EntryFormService($c->get(EntryFormMapper::class)); 
        });
        // Alias 'EntryFormController' to match route 'entry_form#method'
        $context->registerService('EntryFormController', function($c) {
             return new EntryFormController(
                 $c->get(IRequest::class), 
                 $c->get(IUserSession::class), 
                 $c->get(EntryFormService::class), 
                 $c->get(EntryFormMapper::class), 
                 $c->get(IDBConnection::class), 
                 $c->get(IGroupManager::class)
             );
        });


        // =====================================================================
        // 2. OPTIONAL ANALYSIS MODULE
        // =====================================================================
        if (class_exists(AnalysisMapper::class)) {
            $context->registerService(AnalysisMapper::class, function($c) { 
                return new AnalysisMapper($c->get(IDBConnection::class)); 
            });
        }
        
        // We still register the legacy TimesheetMapper if Analysis needs it for complex queries
        if (class_exists(TimesheetMapper::class)) {
            $context->registerService(TimesheetMapper::class, function($c) { 
                return new TimesheetMapper($c->get(IDBConnection::class)); 
            });
        }

        if (class_exists(AnalysisService::class)) {
            $context->registerService(AnalysisService::class, function($c) {
                return new AnalysisService(
                    $c->get(AnalysisMapper::class),
                    $c->get(TimesheetMapper::class),
                    $c->get(IGroupManager::class),
                    $c->get(IUserSession::class)
                );
            });
        }

        if (class_exists(AnalysisController::class)) {
            $context->registerService('AnalysisController', function($c) {
                return new AnalysisController(
                    $c->get(IRequest::class), 
                    $c->get(AnalysisService::class), 
                    $c->get(TimesheetMapper::class), 
                    $c->get(AnalysisMapper::class), 
                    $c->get(IUserSession::class), 
                    $c->get(IUserManager::class)
                );
            });
        }


        // =====================================================================
        // 3. ADMIN MODULES (Safe Loading)
        // =====================================================================
        
        // Users
        if (class_exists('\OCA\TimeclockManager\Admin\Users\Service\UsersService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Users\Db\UsersMapper::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Users\Db\UsersMapper($c->get(IDBConnection::class)); 
            });
            $context->registerService(\OCA\TimeclockManager\Admin\Users\Service\UsersService::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Users\Service\UsersService($c->get(IUserManager::class), $c->get(\OCA\TimeclockManager\Admin\Users\Db\UsersMapper::class)); 
            });
            $context->registerService('AdminUsersController', function($c) { 
                return new UsersController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Users\Service\UsersService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); 
            });
        }

        // Access
        if (class_exists('\OCA\TimeclockManager\Admin\Access\Service\AccessService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Access\Db\AccessMapper::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Access\Db\AccessMapper($c->get(IDBConnection::class)); 
            });
            $context->registerService(\OCA\TimeclockManager\Admin\Access\Service\AccessService::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Access\Service\AccessService($c->get(IGroupManager::class), $c->get(\OCA\TimeclockManager\Admin\Access\Db\AccessMapper::class)); 
            });
            $context->registerService('AdminAccessController', function($c) { 
                return new AccessController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Access\Service\AccessService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); 
            });
        }

        // Payroll
        if (class_exists('\OCA\TimeclockManager\Admin\Payroll\Service\PayrollService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Payroll\Db\PayrollMapper::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Payroll\Db\PayrollMapper($c->get(IDBConnection::class)); 
            });
            $context->registerService(\OCA\TimeclockManager\Admin\Payroll\Service\PayrollService::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Payroll\Service\PayrollService($c->get(\OCA\TimeclockManager\Admin\Payroll\Db\PayrollMapper::class)); 
            });
            $context->registerService('AdminPayrollController', function($c) { 
                return new PayrollController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Payroll\Service\PayrollService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); 
            });
        }

        // Holidays
        if (class_exists('\OCA\TimeclockManager\Admin\Holidays\Service\HolidaysService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Holidays\Db\HolidaysMapper::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Holidays\Db\HolidaysMapper($c->get(IDBConnection::class)); 
            });
            $context->registerService(\OCA\TimeclockManager\Admin\Holidays\Service\HolidaysService::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Holidays\Service\HolidaysService($c->get(\OCA\TimeclockManager\Admin\Holidays\Db\HolidaysMapper::class)); 
            });
            $context->registerService('AdminHolidaysController', function($c) { 
                return new HolidaysController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Holidays\Service\HolidaysService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); 
            });
        }

        // Jobs
        if (class_exists('\OCA\TimeclockManager\Admin\Jobs\Service\JobsService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Jobs\Db\JobsMapper::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Jobs\Db\JobsMapper($c->get(IDBConnection::class)); 
            });
            $context->registerService(\OCA\TimeclockManager\Admin\Jobs\Service\JobsService::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Jobs\Service\JobsService($c->get(\OCA\TimeclockManager\Admin\Jobs\Db\JobsMapper::class), $c->get(IAppData::class)); 
            });
            $context->registerService('AdminJobsController', function($c) { 
                return new JobsController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Jobs\Service\JobsService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); 
            });
        }

        // Locations
        if (class_exists('\OCA\TimeclockManager\Admin\Locations\Service\LocationsService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Locations\Db\LocationsMapper::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Locations\Db\LocationsMapper($c->get(IDBConnection::class)); 
            });
            $context->registerService(\OCA\TimeclockManager\Admin\Locations\Service\LocationsService::class, function($c) { 
                return new \OCA\TimeclockManager\Admin\Locations\Service\LocationsService($c->get(\OCA\TimeclockManager\Admin\Locations\Db\LocationsMapper::class)); 
            });
            $context->registerService('AdminLocationsController', function($c) { 
                return new LocationsController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Locations\Service\LocationsService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); 
            });
        }


        // =====================================================================
        // 4. FRONTEND CONTROLLERS
        // =====================================================================
        
        $context->registerService(PageController::class, function($c) {
            // Pass AnalysisService ONLY if it was successfully registered above
            $analysis = $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null;
            return new PageController(
                $c->get(IRequest::class), 
                $c->get(IUserSession::class), 
                $c->get(IGroupManager::class), 
                $analysis
            );
        });

        $context->registerService(AdminController::class, function($c) {
            return new AdminController($c->get(IRequest::class));
        });
    }

    public function boot(IBootContext $context): void {
        $context->injectFn(function(INavigationManager $navigationManager) {
            $navigationManager->add(function() {
                return [
                    'id' => self::APP_ID,
                    'order' => 10,
                    'href' => \OC::$server->getURLGenerator()->linkToRoute(self::APP_ID . '.page.index'),
                    'icon' => \OC::$server->getURLGenerator()->imagePath(self::APP_ID, 'app.svg'),
                    'name' => 'Timesheet',
                ];
            });
        });
    }
}