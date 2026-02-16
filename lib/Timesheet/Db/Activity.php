<?php
namespace OCA\TimeclockManager\Timesheet\Db;

use OCP\AppFramework\Db\Entity;

class Activity extends Entity implements \JsonSerializable {

    protected $activityId;
    protected $timesheetId;
    protected $activityDescription;
    protected $activityPercent;

    public function __construct() {
        $this->addType('activityId', 'integer');
        $this->addType('timesheetId', 'integer');
        $this->addType('activityDescription', 'string');
        $this->addType('activityPercent', 'integer');
    }

    public function setActivityId(int $id) {
        $this->activityId = $id;
        $this->setId($id);
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'activity_id' => $this->activityId,
            'timesheet_id' => $this->timesheetId,
            
            // Frontend likely uses 'description' and 'percent' for the rows
            'description' => $this->activityDescription,
            'percent' => $this->activityPercent,
            
            // Keep original snake_case just in case
            'activity_description' => $this->activityDescription,
            'activity_percent' => $this->activityPercent,
        ];
    }
}