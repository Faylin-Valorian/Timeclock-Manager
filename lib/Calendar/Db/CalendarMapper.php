<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Calendar\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

class CalendarMapper extends QBMapper {
    
    public function __construct(IDBConnection $db) {
        // Reads from the new 'tm_timesheets' table
        parent::__construct($db, 'tm_timesheets');
    }

    public function findRawEntries(string $userId, string $start, string $end, int $archive = 0): array {
        $qb = $this->db->getQueryBuilder();
        return $qb->select('*')
            ->from('tm_timesheets')
            ->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->gte('timesheet_date', $qb->createNamedParameter($start)))
            ->andWhere($qb->expr()->lte('timesheet_date', $qb->createNamedParameter($end)))
            ->andWhere($qb->expr()->eq('archive', $qb->createNamedParameter($archive, IQueryBuilder::PARAM_INT)))
            ->executeQuery()
            ->fetchAll();
    }

    public function getHolidaysForCalendar($start, $end): array {
        $qb = $this->db->getQueryBuilder();
        $query = $qb->select('*')
            ->from('tm_holidays')
            ->where($qb->expr()->eq('holiday_archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
            
        if ($start && $end) {
             $query->andWhere($qb->expr()->lte('holiday_start_date', $qb->createNamedParameter($end)))
                   ->andWhere($qb->expr()->gte('holiday_end_date', $qb->createNamedParameter($start)));
        }
        return $query->executeQuery()->fetchAll();
    }

    public function getHolidayForDate(string $date): ?array {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('*')
            ->from('tm_holidays')
            ->where($qb->expr()->eq('holiday_archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->lte('holiday_start_date', $qb->createNamedParameter($date)))
            ->andWhere($qb->expr()->gte('holiday_end_date', $qb->createNamedParameter($date)))
            ->orderBy('holiday_start_date', 'ASC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetch();

        return $row ?: null;
    }

    public function hasAutoHolidayTimesheetForDate(string $userId, string $date): bool {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('timesheet_id')
            ->from('tm_timesheets')
            ->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('timesheet_date', $qb->createNamedParameter($date)))
            ->andWhere($qb->expr()->eq('is_pto', $qb->createNamedParameter(2, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetch();

        return !!$row;
    }

    public function insertAutoHolidayTimesheet(string $userId, string $date): void {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('tm_timesheets')
            ->values([
                'userid' => $qb->createNamedParameter($userId),
                'timesheet_date' => $qb->createNamedParameter($date),
                'time_in' => $qb->createNamedParameter('08:00:00'),
                'time_out' => $qb->createNamedParameter('17:00:00'),
                'time_break' => $qb->createNamedParameter(60, IQueryBuilder::PARAM_INT),
                'time_total' => $qb->createNamedParameter(8.0),
                // Sentinel value for auto-holiday rows (distinct from PTO=1)
                'is_pto' => $qb->createNamedParameter(2, IQueryBuilder::PARAM_INT),
                'travel_road_scanning' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'travel_first_last_day' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'travel_overnight' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'travel_per_diem' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'travel_state' => $qb->createNamedParameter(''),
                'travel_county' => $qb->createNamedParameter(''),
                'travel_miles' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'travel_extra_expenses' => $qb->createNamedParameter(0.0),
                'additional_comments' => $qb->createNamedParameter(''),
                'archive' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
            ])
            ->executeStatement();
    }

    public function getActivitiesGrouped(array $ids): array {
        if (empty($ids)) return [];
        $qb = $this->db->getQueryBuilder();
        $acts = $qb->select('*')
                  ->from('tm_activity')
                  ->where($qb->expr()->in('timesheet_id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)))
                  ->executeQuery()
                  ->fetchAll();
        
        $grouped = [];
        foreach($acts as $a) { $grouped[$a['timesheet_id']][] = $a; }
        return $grouped;
    }

    public function getPtoJobMap(): array {
        $map = [];
        try {
            $qb = $this->db->getQueryBuilder();
            $rows = $qb->select('job_name', 'is_pto')
                ->from('tm_jobs')
                ->where($qb->expr()->eq('job_archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
                ->executeQuery()
                ->fetchAll();
            foreach ($rows as $j) { $map[$j['job_name']] = (int)$j['is_pto']; }
        } catch (\Exception $e) {}
        return $map;
    }

    public function getAdminSettings(): array {
        $settings = [];
        try {
            $qb = $this->db->getQueryBuilder();
            $rows = $qb->select('*')->from('tm_admin_settings')->executeQuery()->fetchAll();
            foreach ($rows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }
        } catch (\Exception $e) {}
        
        if (empty($settings)) {
            return ['pay_frequency' => 14, 'pay_start_date' => date('Y-01-01'), 'pay_color' => '#34495e'];
        }
        return $settings;
    }
}
