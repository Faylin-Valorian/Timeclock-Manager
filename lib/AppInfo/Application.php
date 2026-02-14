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

// Core
use OCA\TimeclockManager\Controller\PageController;
use OCA\TimeclockManager\Controller\AdminController;
use OCA\TimeclockManager\Controller\TimesheetController;
use OCA\TimeclockManager\Controller\AnalysisController;
use OCA\TimeclockManager\Service\TimesheetService;
use OCA\TimeclockManager\Service\AnalysisService;
use OCA\TimeclockManager\Db\TimesheetMapper;
use OCA\TimeclockManager\Db\AnalysisMapper;
use OCA\TimeclockManager\Db\AdminMapper;

// Modules (We verify these exist before loading)
use OCA\TimeclockManager\Admin\Users\Controller\UsersController;
use OCA\TimeclockManager\Admin\Access\Controller\AccessController;
use OCA\TimeclockManager\Admin\Payroll\Controller\PayrollController;
use OCA\TimeclockManager\Admin\Holidays\Controller\HolidaysController;
use OCA\TimeclockManager\Admin\Jobs\Controller\JobsController;
use OCA\TimeclockManager\Admin\Locations\Controller\LocationsController;

class Application extends App implements IBootstrap {

    public const APP_ID = 'timeclock_manager';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        
        // --- 1. CORE DEPENDENCIES (Always Required) ---
        $context->registerService(TimesheetMapper::class, function($c) { return new TimesheetMapper($c->get(IDBConnection::class)); });
        $context->registerService(AdminMapper::class, function($c) { return new AdminMapper($c->get(IDBConnection::class)); });
        
        // Timesheet Service
        if (class_exists(TimesheetService::class)) {
            $context->registerService(TimesheetService::class, function($c) { return new TimesheetService($c->get(TimesheetMapper::class), $c->get(IDBConnection::class)); });
        }

        // Analysis Mapper & Service (OPTIONAL)
        if (class_exists(AnalysisMapper::class)) {
            $context->registerService(AnalysisMapper::class, function($c) { return new AnalysisMapper($c->get(IDBConnection::class)); });
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

        // --- 2. ADMIN MODULES (Safe Loading) ---
        // We check if the Class exists before registering. If you delete the folder, these blocks just skip.
        
        // Users
        if (class_exists('\OCA\TimeclockManager\Admin\Users\Service\UsersService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Users\Db\UsersMapper::class, function($c) { return new \OCA\TimeclockManager\Admin\Users\Db\UsersMapper($c->get(IDBConnection::class)); });
            $context->registerService(\OCA\TimeclockManager\Admin\Users\Service\UsersService::class, function($c) { return new \OCA\TimeclockManager\Admin\Users\Service\UsersService($c->get(IUserManager::class), $c->get(\OCA\TimeclockManager\Admin\Users\Db\UsersMapper::class)); });
            $context->registerService('AdminUsersController', function($c) { return new UsersController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Users\Service\UsersService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); });
        }

        // Access
        if (class_exists('\OCA\TimeclockManager\Admin\Access\Service\AccessService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Access\Db\AccessMapper::class, function($c) { return new \OCA\TimeclockManager\Admin\Access\Db\AccessMapper($c->get(IDBConnection::class)); });
            $context->registerService(\OCA\TimeclockManager\Admin\Access\Service\AccessService::class, function($c) { return new \OCA\TimeclockManager\Admin\Access\Service\AccessService($c->get(IGroupManager::class), $c->get(\OCA\TimeclockManager\Admin\Access\Db\AccessMapper::class)); });
            $context->registerService('AdminAccessController', function($c) { return new AccessController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Access\Service\AccessService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); });
        }

        // Payroll
        if (class_exists('\OCA\TimeclockManager\Admin\Payroll\Service\PayrollService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Payroll\Db\PayrollMapper::class, function($c) { return new \OCA\TimeclockManager\Admin\Payroll\Db\PayrollMapper($c->get(IDBConnection::class)); });
            $context->registerService(\OCA\TimeclockManager\Admin\Payroll\Service\PayrollService::class, function($c) { return new \OCA\TimeclockManager\Admin\Payroll\Service\PayrollService($c->get(\OCA\TimeclockManager\Admin\Payroll\Db\PayrollMapper::class)); });
            $context->registerService('AdminPayrollController', function($c) { return new PayrollController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Payroll\Service\PayrollService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); });
        }

        // Holidays
        if (class_exists('\OCA\TimeclockManager\Admin\Holidays\Service\HolidaysService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Holidays\Db\HolidaysMapper::class, function($c) { return new \OCA\TimeclockManager\Admin\Holidays\Db\HolidaysMapper($c->get(IDBConnection::class)); });
            $context->registerService(\OCA\TimeclockManager\Admin\Holidays\Service\HolidaysService::class, function($c) { return new \OCA\TimeclockManager\Admin\Holidays\Service\HolidaysService($c->get(\OCA\TimeclockManager\Admin\Holidays\Db\HolidaysMapper::class)); });
            $context->registerService('AdminHolidaysController', function($c) { return new HolidaysController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Holidays\Service\HolidaysService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); });
        }

        // Jobs
        if (class_exists('\OCA\TimeclockManager\Admin\Jobs\Service\JobsService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Jobs\Db\JobsMapper::class, function($c) { return new \OCA\TimeclockManager\Admin\Jobs\Db\JobsMapper($c->get(IDBConnection::class)); });
            $context->registerService(\OCA\TimeclockManager\Admin\Jobs\Service\JobsService::class, function($c) { return new \OCA\TimeclockManager\Admin\Jobs\Service\JobsService($c->get(\OCA\TimeclockManager\Admin\Jobs\Db\JobsMapper::class), $c->get(IAppData::class)); });
            $context->registerService('AdminJobsController', function($c) { return new JobsController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Jobs\Service\JobsService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); });
        }

        // Locations
        if (class_exists('\OCA\TimeclockManager\Admin\Locations\Service\LocationsService')) {
            $context->registerService(\OCA\TimeclockManager\Admin\Locations\Db\LocationsMapper::class, function($c) { return new \OCA\TimeclockManager\Admin\Locations\Db\LocationsMapper($c->get(IDBConnection::class)); });
            $context->registerService(\OCA\TimeclockManager\Admin\Locations\Service\LocationsService::class, function($c) { return new \OCA\TimeclockManager\Admin\Locations\Service\LocationsService($c->get(\OCA\TimeclockManager\Admin\Locations\Db\LocationsMapper::class)); });
            $context->registerService('AdminLocationsController', function($c) { return new LocationsController($c->get(IRequest::class), $c->get(\OCA\TimeclockManager\Admin\Locations\Service\LocationsService::class), $c->has(AnalysisService::class) ? $c->get(AnalysisService::class) : null); });
        }


        // --- 3. CORE CONTROLLERS (Resilient Loading) ---
        
        $context->registerService(PageController::class, function($c) {
            // Pass null if AnalysisService is missing
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

        // Timesheet Controller
        if (class_exists(TimesheetController::class)) {
            $context->registerService(TimesheetController::class, function($c) {
                 return new TimesheetController(
                    $c->get(IRequest::class), $c->get(IUserSession::class), $c->get(TimesheetService::class),
                    $c->get(TimesheetMapper::class), $c->get(IDBConnection::class), $c->get(IGroupManager::class)
                );
            });
        }
        
        // Analysis Controller
        if (class_exists(AnalysisController::class)) {
            $context->registerService(AnalysisController::class, function($c) {
                return new AnalysisController(
                    $c->get(IRequest::class), $c->get(AnalysisService::class), $c->get(TimesheetMapper::class), 
                    $c->get(AnalysisMapper::class), $c->get(IUserSession::class), $c->get(IUserManager::class)
                );
            });
        }
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