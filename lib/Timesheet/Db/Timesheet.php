<?php
namespace OCA\TimeclockManager\Timesheet\Db;

use OCP\AppFramework\Db\Entity;

class Timesheet extends Entity {

    protected $userid;
    protected $timesheetDate;
    protected $timeIn;
    protected $timeOut;
    protected $timeBreak;
    protected $timeTotal;
    protected $isPto;
    
    // Travel Columns
    protected $travelRoadScanning;
    protected $travelFirstLastDay;
    protected $travelOvernight;
    protected $travelPerDiem;
    protected $travelState;
    protected $travelCounty;
    protected $travelMiles;
    protected $travelExtraExpenses;
    
    protected $additionalComments;
    protected $archive;

    public function __construct() {
        $this->addType('userid', 'string');
        $this->addType('timesheetDate', 'string');
        $this->addType('timeIn', 'string');
        $this->addType('timeOut', 'string');
        $this->addType('timeBreak', 'integer');
        $this->addType('timeTotal', 'float');
        $this->addType('isPto', 'integer');
        
        $this->addType('travelRoadScanning', 'integer');
        $this->addType('travelFirstLastDay', 'integer');
        $this->addType('travelOvernight', 'integer');
        $this->addType('travelPerDiem', 'integer');
        
        $this->addType('travelState', 'string');
        $this->addType('travelCounty', 'string');
        $this->addType('travelMiles', 'integer');
        $this->addType('travelExtraExpenses', 'float');
        
        $this->addType('additionalComments', 'string');
        $this->addType('archive', 'integer');
    }
}