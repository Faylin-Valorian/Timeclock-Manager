<?php
namespace OCA\TimeclockManager\Timesheet\Db;

use OCP\AppFramework\Db\Entity;

class Timesheet extends Entity implements \JsonSerializable {

    // Custom Primary Key
    protected $timesheetId;
    
    // Standard Fields
    protected $userid;
    protected $timesheetDate;
    protected $timeIn;
    protected $timeOut;
    protected $timeBreak;
    protected $timeTotal;
    protected $isPto;
    
    // Legacy Column (from migration)
    protected $travel; 

    // Travel Details
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
        $this->addType('timesheetId', 'integer');
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

    /**
     * [CRITICAL PATCH] Sync Database Column (timesheet_id) with Entity ID
     * This enables update() and delete() to work correctly in the Mapper.
     */
    public function setTimesheetId(int $id) {
        $this->timesheetId = $id;
        $this->setId($id); 
    }
    
    public function getTimesheetId(): int {
        return (int)$this->timesheetId;
    }

    public function setArchive(int $archive) {
        $this->archive = $archive;
    }

    public function getArchive(): int {
        return (int)$this->archive;
    }

    /**
     * [CRITICAL PATCH] Map camelCase properties to snake_case for Frontend
     */
    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(), 
            'timesheet_id' => $this->timesheetId,
            'date' => $this->timesheetDate,
            'userid' => $this->userid,
            
            'time_in' => $this->timeIn,
            'time_out' => $this->timeOut,
            'time_break' => $this->timeBreak, 
            'time_total' => $this->timeTotal,
            'additional_comments' => $this->additionalComments,
            
            'is_pto' => $this->isPto,
            
            'travel_per_diem' => $this->travelPerDiem,
            'travel_road_scanning' => $this->travelRoadScanning,
            'travel_first_last_day' => $this->travelFirstLastDay,
            'travel_overnight' => $this->travelOvernight,
            'travel_state' => $this->travelState,
            'travel_county' => $this->travelCounty,
            'travel_miles' => $this->travelMiles,
            'travel_extra_expenses' => $this->travelExtraExpenses,
            
            // Dynamic properties attached by Service
            'activities' => $this->activities ?? [],
            'archive' => $this->archive
        ];
    }
}