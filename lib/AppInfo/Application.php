<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IUserSession;
use OCP\IGroupManager;

// Controllers
use OCA\TimeclockManager\Controller\PageController;
use OCA\TimeclockManager\Calendar\Controller\CalendarController;
use OCA\TimeclockManager\Timesheet\Controller\TimesheetController;

// Services & Mappers
use OCA\TimeclockManager\Calendar\Service\CalendarService;
use OCA\TimeclockManager\Calendar\Db\CalendarMapper;
use OCA\TimeclockManager\Timesheet\Service\TimesheetService;
use OCA\TimeclockManager\Timesheet\Db\TimesheetMapper;

class Application extends App implements IBootstrap {
    public const APP_ID = 'timeclock-manager';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        // --- 1. Page & Shared ---
        $context->registerService(PageController::class, function($c) {
            return new PageController($c->get(IRequest::class));
        });

        // --- 2. Calendar Module (Read) ---
        $context->registerService(CalendarMapper::class, function($c) { return new CalendarMapper($c->get(IDBConnection::class)); });
        $context->registerService(CalendarService::class, function($c) { return new CalendarService($c->get(CalendarMapper::class)); });
        $context->registerService('CalendarController', function($c) {
            return new CalendarController($c->get(IRequest::class), $c->get(IUserSession::class), $c->get(IGroupManager::class), $c->get(CalendarService::class));
        });

        // --- 3. [NEW] Timesheet Module (Write) ---
        $context->registerService(TimesheetMapper::class, function($c) { return new TimesheetMapper($c->get(IDBConnection::class)); });
        $context->registerService(TimesheetService::class, function($c) { return new TimesheetService($c->get(TimesheetMapper::class), $c->get(IDBConnection::class)); });
        $context->registerService('TimesheetController', function($c) {
            return new TimesheetController(
                $c->get(IRequest::class), 
                $c->get(IUserSession::class), 
                $c->get(IGroupManager::class), 
                $c->get(TimesheetService::class),
                $c->get(TimesheetMapper::class)
            );
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