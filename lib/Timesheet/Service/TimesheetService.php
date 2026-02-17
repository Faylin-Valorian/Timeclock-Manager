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
        // Mapper filters out archived (deleted) records automatically
        return $this->mapper->findAll($userId);
    }

    public function find(int $id, string $userId) {
        try {
            $timesheet = $this->mapper->find($id, $userId);
            
            // Attach child rows (activities)
            $timesheet->activities = $this->activityMapper->findAllForTimesheet($id);

            return $timesheet;
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function create(array $data, string $userId) {
        $timesheet = new Timesheet();
        $timesheet->setUserid($userId);
        
        $this->hydrate($timesheet, $data);
        
        // [CRITICAL] New records are visible (archive = 0)
        $timesheet->setArchive(0); 

        // Insert Header
        $savedTimesheet = $this->mapper->insert($timesheet);

        // Insert Activities
        if (isset($data['activities']) && is_array($data['activities'])) {
            $this->processActivities($savedTimesheet->getTimesheetId(), $data['activities']);
        }

        return $savedTimesheet;
    }

    public function update(int $id, array $data, string $userId, bool $allowArchivedEdit = false) {
        try {
            // 1. Fetch existing record to ensure ownership
            $timesheet = $this->mapper->find($id, $userId);

            if ((int)$timesheet->getArchive() === 1 && !$allowArchivedEdit) {
                return null;
            }
            
            // 2. Update fields
            $this->hydrate($timesheet, $data);

            // 3. Save Header
            $updatedTimesheet = $this->mapper->update($timesheet);

            // 4. Replace Activities
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
            
            // [CRITICAL] Soft Delete: Mark as archived
            $timesheet->setArchive(1);
            
            // Perform Update instead of Delete
            return $this->mapper->update($timesheet);
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    public function restore(int $id, string $userId) {
        try {
            $timesheet = $this->mapper->find($id, $userId);
            $timesheet->setArchive(0);
            return $this->mapper->update($timesheet);
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    // --- Helpers ---

    private function processActivities(int $timesheetId, array $items) {
        // Clear old items
        $this->activityMapper->deleteAllForTimesheet($timesheetId);

        foreach ($items as $row) {
            $descRaw = $row['description'] ?? $row['activity_description'] ?? '';
            $desc = $this->normalizeText($descRaw);
            $pctRaw = $row['percent'] ?? $row['activity_percent'] ?? 0;
            $pct = (int)$pctRaw;

            if ($desc === '' && $pct === 0) continue;

            $activity = new Activity();
            $activity->setTimesheetId($timesheetId);

            $activity->setActivityDescription($desc);
            $activity->setActivityPercent($pct);
            
            $this->activityMapper->insert($activity);
        }
    }

    private function hydrate(Timesheet $timesheet, array $data) {
        $timesheet->setTimesheetDate($this->normalizeText($data['date'] ?? ''));
        $timesheet->setTimeIn($this->normalizeTime($data['time_in'] ?? null));
        $timesheet->setTimeOut($this->normalizeTime($data['time_out'] ?? null));
        
        // Handle variations (time_break vs break_min)
        $break = $data['break_min'] ?? $data['time_break'] ?? 0;
        $timesheet->setTimeBreak((int)$break);
        
        $timesheet->setTimeTotal((float)($data['time_total'] ?? 0.0));
        
        $comments = $data['comments'] ?? $data['additional_comments'] ?? '';
        $timesheet->setAdditionalComments($this->normalizeText($comments));
        
        $timesheet->setIsPto((int)($data['is_pto'] ?? 0));
        
        // Travel Toggles
        $timesheet->setTravelPerDiem((int)($data['travel_per_diem'] ?? 0));
        $timesheet->setTravelRoadScanning((int)($data['travel_road_scanning'] ?? 0));
        $timesheet->setTravelFirstLastDay((int)($data['travel_first_last_day'] ?? 0));
        $timesheet->setTravelOvernight((int)($data['travel_overnight'] ?? 0));
        
        // Travel Details
        $timesheet->setTravelState($this->normalizeText($data['travel_state'] ?? ''));
        $timesheet->setTravelCounty($this->normalizeText($data['travel_county'] ?? ''));
        $timesheet->setTravelMiles((int)($data['travel_miles'] ?? 0));
        $timesheet->setTravelExtraExpenses((float)($data['travel_extra_expenses'] ?? 0.0));
    }

    private function normalizeText($value): string {
        if ($value === null) return '';
        $text = trim((string)$value);
        if ($text === '' || strtolower($text) === 'null') {
            return '';
        }
        return $text;
    }

    private function normalizeTime($value): ?string {
        if ($value === null) return null;
        $text = trim((string)$value);
        if ($text === '' || strtolower($text) === 'null') {
            return null;
        }
        return $text;
    }
}
