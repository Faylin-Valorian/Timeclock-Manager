<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Timesheet\Service;

use OCA\TimeclockManager\Timesheet\Db\TimesheetMapper;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

class TimesheetService {
    private $mapper;
    private $db;

    public function __construct(TimesheetMapper $mapper, IDBConnection $db) {
        $this->mapper = $mapper;
        $this->db = $db;
    }

    public function getAttributes(): array {
        return [
            'jobs' => $this->mapper->getActiveJobs(),
            'states' => $this->mapper->getStates()
        ];
    }

    public function deleteTimesheet(int $id, string $uid): void {
        // We perform a Soft Delete (Archive = 1)
        $qb = $this->db->getQueryBuilder();
        $qb->update('tm_timesheets')
           ->set('archive', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
           ->where($qb->expr()->eq('timesheet_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
           ->andWhere($qb->expr()->eq('userid', $qb->createNamedParameter($uid)))
           ->execute();
    }

    public function restoreTimesheet(int $id, string $uid): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('tm_timesheets')
           ->set('archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT))
           ->where($qb->expr()->eq('timesheet_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
           ->andWhere($qb->expr()->eq('userid', $qb->createNamedParameter($uid)))
           ->execute();
    }

    public function saveTimesheet(array $data, string $uid): int {
        // 1. Validation
        if (empty($data['date'])) {
            throw new \Exception('Date is required');
        }

        // 2. Prepare Data
        $date = $data['date'];
        $timeIn = $data['time_in'] ?: null;
        $timeOut = $data['time_out'] ?: null;
        $break = (int)($data['break_min'] ?? 0);
        $total = (float)($data['time_total'] ?? 0);
        $comments = $data['comments'] ?? '';
        
        // Travel Data
        $travelPerDiem = (int)($data['travel_per_diem'] ?? 0);
        $travelScanning = (int)($data['travel_road_scanning'] ?? 0);
        $travelFirstLast = (int)($data['travel_first_last_day'] ?? 0);
        $travelOvernight = (int)($data['travel_overnight'] ?? 0);
        $travelState = $data['travel_state'] ?? '';
        $travelCounty = $data['travel_county'] ?? '';
        $travelMiles = (int)($data['travel_miles'] ?? 0);
        $travelExpenses = (float)($data['travel_extra_expenses'] ?? 0);

        // Calculate "Travel Exists" flag
        $travel = ($travelState || $travelMiles > 0 || $travelPerDiem || $travelExpenses > 0) ? 1 : 0;

        $id = isset($data['timesheet_id']) ? (int)$data['timesheet_id'] : null;

        // 3. Transaction for Atomicity
        $this->db->beginTransaction();
        try {
            $qb = $this->db->getQueryBuilder();

            if ($id) {
                // UPDATE
                $qb->update('tm_timesheets')
                   ->set('timesheet_date', $qb->createNamedParameter($date))
                   ->set('time_in', $qb->createNamedParameter($timeIn))
                   ->set('time_out', $qb->createNamedParameter($timeOut))
                   ->set('time_break', $qb->createNamedParameter($break))
                   ->set('time_total', $qb->createNamedParameter($total))
                   ->set('additional_comments', $qb->createNamedParameter($comments))
                   ->set('travel', $qb->createNamedParameter($travel))
                   ->set('travel_per_diem', $qb->createNamedParameter($travelPerDiem))
                   ->set('travel_road_scanning', $qb->createNamedParameter($travelScanning))
                   ->set('travel_first_last_day', $qb->createNamedParameter($travelFirstLast))
                   ->set('travel_overnight', $qb->createNamedParameter($travelOvernight))
                   ->set('travel_state', $qb->createNamedParameter($travelState))
                   ->set('travel_county', $qb->createNamedParameter($travelCounty))
                   ->set('travel_miles', $qb->createNamedParameter($travelMiles))
                   ->set('travel_extra_expenses', $qb->createNamedParameter($travelExpenses))
                   ->where($qb->expr()->eq('timesheet_id', $qb->createNamedParameter($id)))
                   ->andWhere($qb->expr()->eq('userid', $qb->createNamedParameter($uid)))
                   ->execute();
            } else {
                // INSERT
                $qb->insert('tm_timesheets')
                   ->values([
                       'userid' => $qb->createNamedParameter($uid),
                       'timesheet_date' => $qb->createNamedParameter($date),
                       'time_in' => $qb->createNamedParameter($timeIn),
                       'time_out' => $qb->createNamedParameter($timeOut),
                       'time_break' => $qb->createNamedParameter($break),
                       'time_total' => $qb->createNamedParameter($total),
                       'additional_comments' => $qb->createNamedParameter($comments),
                       'archive' => $qb->createNamedParameter(0),
                       'travel' => $qb->createNamedParameter($travel),
                       'travel_per_diem' => $qb->createNamedParameter($travelPerDiem),
                       'travel_road_scanning' => $qb->createNamedParameter($travelScanning),
                       'travel_first_last_day' => $qb->createNamedParameter($travelFirstLast),
                       'travel_overnight' => $qb->createNamedParameter($travelOvernight),
                       'travel_state' => $qb->createNamedParameter($travelState),
                       'travel_county' => $qb->createNamedParameter($travelCounty),
                       'travel_miles' => $qb->createNamedParameter($travelMiles),
                       'travel_extra_expenses' => $qb->createNamedParameter($travelExpenses)
                   ])
                   ->execute();
                $id = (int)$this->db->lastInsertId();
            }

            // 4. Handle Activities (Work Breakdown)
            // Delete old activities first
            $qbDel = $this->db->getQueryBuilder();
            $qbDel->delete('tm_activity')
                  ->where($qbDel->expr()->eq('timesheet_id', $qbDel->createNamedParameter($id)))
                  ->execute();

            // Insert new activities
            $descriptions = $data['work_desc'] ?? [];
            $percents = $data['work_percent'] ?? [];

            if (is_array($descriptions)) {
                foreach ($descriptions as $index => $desc) {
                    if (empty($desc)) continue;
                    $pct = isset($percents[$index]) ? (int)$percents[$index] : 0;
                    
                    $qbIns = $this->db->getQueryBuilder();
                    $qbIns->insert('tm_activity')
                          ->values([
                              'timesheet_id' => $qbIns->createNamedParameter($id),
                              'activity_description' => $qbIns->createNamedParameter($desc),
                              'activity_percent' => $qbIns->createNamedParameter($pct)
                          ])
                          ->execute();
                }
            }

            $this->db->commit();
            return $id;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}