<?php
namespace OCA\TimeclockManager\Timesheet\Db;

use OCP\AppFramework\Db\Entity;

class Activity extends Entity {

    protected $timesheetId;
    protected $activityDescription;
    protected $activityPercent;

    public function __construct() {
        $this->addType('timesheetId', 'integer');
        $this->addType('activityDescription', 'string');
        $this->addType('activityPercent', 'integer');
    }
}