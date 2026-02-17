<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\BackgroundJob;

use OCA\TimeclockManager\Admin\Service\AdminService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class HolidayAutoSeedJob extends TimedJob {

    private $adminService;

    public function __construct(ITimeFactory $timeFactory, AdminService $adminService) {
        parent::__construct($timeFactory);
        $this->adminService = $adminService;
        $this->setInterval(3600);
    }

    protected function run($argument): void {
        $this->adminService->seedDueHolidayTimesheets();
    }
}

