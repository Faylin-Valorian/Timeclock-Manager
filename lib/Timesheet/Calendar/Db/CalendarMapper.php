<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Timesheet\Calendar\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;
// We reference the Entity from the EntryForm module since it defines the schema
use OCA\TimeclockManager\Timesheet\EntryForm\Db\Timesheet; 

class CalendarMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'tm_timesheets', Timesheet::class);
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
            $rows = $this->db->getQueryBuilder()->select('job_name', 'is_pto')->from('tm_jobs')->executeQuery()->fetchAll();
            foreach ($rows as $j) { $map[$j['job_name']] = (int)$j['is_pto']; }
        } catch (\Exception $e) {}
        return $map;
    }

    public function getAdminSettings(): array {
        $settings = [];
        try {
            $rows = $this->db->getQueryBuilder()->select('*')->from('tm_admin_settings')->executeQuery()->fetchAll();
            foreach ($rows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }
        } catch (\Exception $e) {}
        
        if (empty($settings)) {
            return ['pay_frequency' => 14, 'pay_start_date' => date('Y-01-01'), 'pay_color' => '#34495e'];
        }
        return $settings;
    }
}