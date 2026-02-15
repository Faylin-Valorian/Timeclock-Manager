<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Timesheet\Db;

use OCP\AppFramework\Db\Entity;

class Timesheet extends Entity {
    protected $userid;
    protected $timesheetDate;
    protected $timeIn;
    protected $timeOut;
    protected $timeBreak;
    protected $timeTotal;
    protected $additionalComments;
    protected $archive;
    
    // Travel
    protected $travel;
    protected $travelPerDiem;
    protected $travelRoadScanning;
    protected $travelFirstLastDay;
    protected $travelOvernight;
    protected $travelState;
    protected $travelCounty;
    protected $travelMiles;
    protected $travelExtraExpenses;

    public function __construct() {
        $this->addType('timeTotal', 'float');
        $this->addType('archive', 'integer');
        $this->addType('travel', 'integer');
        $this->addType('travelPerDiem', 'integer');
        $this->addType('travelRoadScanning', 'integer');
        $this->addType('travelFirstLastDay', 'integer');
        $this->addType('travelOvernight', 'integer');
        $this->addType('travelMiles', 'integer');
        $this->addType('travelExtraExpenses', 'float');
    }
}