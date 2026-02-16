<?php
namespace OCA\TimeclockManager\Timesheet\Db;

use OCP\AppFramework\Db\Entity;

class Timesheet extends Entity implements \JsonSerializable {

    // [CRITICAL] matches database column 'timesheet_id'
    protected $timesheetId;
    
    protected $userid;
    protected $timesheetDate;
    protected $timeIn;
    protected $timeOut;
    protected $timeBreak;
    protected $timeTotal;
    protected $isPto;
    
    // Legacy support for 'travel' column if it exists
    protected $travel; 

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
        // map 'timesheetId' property to integer type
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
     * [CRITICAL FIX]
     * Nextcloud calls this setter when hydration from DB happens.
     * We MUST sync it to the parent Entity's ID.
     */
    public function setTimesheetId(int $id) {
        $this->timesheetId = $id;
        $this->setId($id); // This makes $this->getId() work for Updates!
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
     * Format data for the Frontend
     */
    public function jsonSerialize(): array {
        return [
            // Send BOTH to ensure frontend can find one
            'id' => $this->getId(), 
            'timesheet_id' => $this->timesheetId,
            
            'date' => $this->timesheetDate,
            'userid' => $this->userid,
            
            // Standardize snake_case for frontend
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
            
            'activities' => $this->activities ?? [],
            'archive' => $this->archive
        ];
    }
}