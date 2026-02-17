<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Analysis\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class AnalysisMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'tm_timesheets');
    }

    public function findForRange(string $userId, string $start, string $end): array {
        $qb = $this->db->getQueryBuilder();

        return $qb->select(
                'timesheet_id',
                'timesheet_date',
                'time_total',
                'is_pto',
                'travel_per_diem',
                'travel_road_scanning',
                'travel_overnight',
                'travel_miles',
                'travel_extra_expenses'
            )
            ->from('tm_timesheets')
            ->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->gte('timesheet_date', $qb->createNamedParameter($start)))
            ->andWhere($qb->expr()->lte('timesheet_date', $qb->createNamedParameter($end)))
            ->andWhere($qb->expr()->eq('archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
            ->executeQuery()
            ->fetchAll();
    }

    public function findRowsForDetail(string $userId, string $start, string $end): array {
        $qb = $this->db->getQueryBuilder();

        return $qb->select(
                'timesheet_id',
                'timesheet_date',
                'time_total',
                'is_pto',
                'travel_per_diem',
                'travel_road_scanning',
                'travel_overnight'
            )
            ->from('tm_timesheets')
            ->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->gte('timesheet_date', $qb->createNamedParameter($start)))
            ->andWhere($qb->expr()->lte('timesheet_date', $qb->createNamedParameter($end)))
            ->andWhere($qb->expr()->eq('archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
            ->executeQuery()
            ->fetchAll();
    }

    public function getActivitiesForTimesheetIds(array $ids): array {
        if (empty($ids)) return [];

        $qb = $this->db->getQueryBuilder();
        $rows = $qb->select('timesheet_id', 'activity_description', 'activity_percent')
            ->from('tm_activity')
            ->where($qb->expr()->in('timesheet_id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)))
            ->executeQuery()
            ->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $tid = (int)($row['timesheet_id'] ?? 0);
            if ($tid <= 0) continue;
            $grouped[$tid][] = $row;
        }
        return $grouped;
    }
}
