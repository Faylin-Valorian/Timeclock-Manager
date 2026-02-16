<?php
namespace OCA\TimeclockManager\Timesheet\Service;

use OCA\TimeclockManager\Timesheet\Db\Timesheet;
use OCA\TimeclockManager\Timesheet\Db\TimesheetMapper;
use OCA\TimeclockManager\Timesheet\Db\Activity;
use OCA\TimeclockManager\Timesheet\Db\ActivityMapper;

use OCP\AppFramework\Db\DoesNotExistException;

class TimesheetService {

    private $mapper;
    private $activityMapper;

    public function __construct(TimesheetMapper $mapper, ActivityMapper $activityMapper) {
        $this->mapper = $mapper;
        $this->activityMapper = $activityMapper;
    }

    public function findAll(string $userId) {
        // Mapper already filters for archive = 0
        return $this->mapper->findAll($userId);
    }

    public function find(int $id, string $userId) {
        try {
            $timesheet = $this->mapper->find($id, $userId);
            
            // Attach Activities (Child Rows)
            $activities = $this->activityMapper->findAllForTimesheet($id);
            // Dynamic property attachment for JSON response
            $timesheet->activities = $activities;

            return $timesheet;
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function create(array $data, string $userId) {
        $timesheet = new Timesheet();
        $timesheet->setUserid($userId);
        
        $this->hydrate($timesheet, $data);
        
        // [CRITICAL] Ensure new records are visible
        $timesheet->setArchive(0); 

        // 1. Save Header
        // The Mapper's insert() will populate the ID on the entity
        $savedTimesheet = $this->mapper->insert($timesheet);

        // 2. Save Activities
        if (isset($data['activities']) && is_array($data['activities'])) {
            $this->processActivities($savedTimesheet->getTimesheetId(), $data['activities']);
        }

        return $savedTimesheet;
    }

    public function update(int $id, array $data, string $userId) {
        try {
            // 1. Load existing entity (ensures ownership)
            $timesheet = $this->mapper->find($id, $userId);
            
            // 2. Apply changes
            $this->hydrate($timesheet, $data);

            // 3. Update Header
            $updatedTimesheet = $this->mapper->update($timesheet);

            // 4. Update Activities (Delete All + Re-insert)
            if (isset($data['activities']) && is_array($data['activities'])) {
                $this->processActivities($id, $data['activities']);
            }

            return $updatedTimesheet;
        } catch (DoesNotExistException $e) {
            return null; 
        }
    }

    public function delete(int $id, string $userId) {
        try {
            $timesheet = $this->mapper->find($id, $userId);
            
            // [CRITICAL] Soft Delete (Archive) instead of Hard Delete
            $timesheet->setArchive(1);
            
            // We use update() to save the archive status
            $this->mapper->update($timesheet);
            
            return $timesheet;
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    // --- Helpers ---

    private function processActivities(int $timesheetId, array $items) {
        // Clear old items
        $this->activityMapper->deleteAllForTimesheet($timesheetId);

        foreach ($items as $row) {
            // Skip empty rows
            if (empty($row['description']) && empty($row['percent'])) continue;

            $activity = new Activity();
            $activity->setTimesheetId($timesheetId);
            
            // Support both camelCase (JSON) and snake_case (Legacy)
            $desc = $row['description'] ?? $row['activity_description'] ?? '';
            $pct = $row['percent'] ?? $row['activity_percent'] ?? 0;

            $activity->setActivityDescription($desc);
            $activity->setActivityPercent((int)$pct);
            
            $this->activityMapper->insert($activity);
        }
    }

    private function hydrate(Timesheet $timesheet, array $data) {
        // Map frontend params to Entity setters
        $timesheet->setTimesheetDate($data['date'] ?? null);
        $timesheet->setTimeIn($data['time_in'] ?? null);
        $timesheet->setTimeOut($data['time_out'] ?? null);
        $timesheet->setTimeBreak((int)($data['break_min'] ?? $data['time_break'] ?? 0));
        $timesheet->setTimeTotal((float)($data['time_total'] ?? 0.0));
        $timesheet->setAdditionalComments($data['comments'] ?? $data['additional_comments'] ?? '');
        
        $timesheet->setIsPto((int)($data['is_pto'] ?? 0));
        
        // Travel Toggles
        $timesheet->setTravelPerDiem((int)($data['travel_per_diem'] ?? 0));
        $timesheet->setTravelRoadScanning((int)($data['travel_road_scanning'] ?? 0));
        $timesheet->setTravelFirstLastDay((int)($data['travel_first_last_day'] ?? 0));
        $timesheet->setTravelOvernight((int)($data['travel_overnight'] ?? 0));
        
        // Travel Details
        $timesheet->setTravelState($data['travel_state'] ?? '');
        $timesheet->setTravelCounty($data['travel_county'] ?? '');
        $timesheet->setTravelMiles((int)($data['travel_miles'] ?? 0));
        $timesheet->setTravelExtraExpenses((float)($data['travel_extra_expenses'] ?? 0.0));
    }
}