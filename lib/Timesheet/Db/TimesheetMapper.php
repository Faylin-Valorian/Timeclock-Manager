<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Timesheet\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

class TimesheetMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'tm_timesheets', Timesheet::class);
    }

    public function getById(int $id, string $uid): ?array {
        $qb = $this->db->getQueryBuilder();
        $res = $qb->select('*')
            ->from('tm_timesheets')
            ->where($qb->expr()->eq('timesheet_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('userid', $qb->createNamedParameter($uid)))
            ->executeQuery()
            ->fetch();
        return $res ?: null;
    }

    public function getActivities(int $id): array {
        $qb = $this->db->getQueryBuilder();
        return $qb->select('*')
            ->from('tm_activity')
            ->where($qb->expr()->eq('timesheet_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->executeQuery()
            ->fetchAll();
    }

    // --- Dropdown Data Helpers ---
    
    public function getActiveJobs(): array {
        $qb = $this->db->getQueryBuilder();
        return $qb->select('*')->from('tm_jobs')
            ->where($qb->expr()->eq('job_archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
            ->orderBy('job_name', 'ASC')
            ->executeQuery()
            ->fetchAll();
    }

    public function getStates(): array {
        $qb = $this->db->getQueryBuilder();
        return $qb->select('*')->from('tm_states')
            ->where($qb->expr()->eq('is_enabled', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
            ->orderBy('state_name', 'ASC')
            ->executeQuery()
            ->fetchAll();
    }

    public function getCounties(string $stateAbbr): array {
        $qb = $this->db->getQueryBuilder();
        $state = $qb->select('fips_code')->from('tm_states')
            ->where($qb->expr()->eq('state_abbr', $qb->createNamedParameter($stateAbbr)))
            ->executeQuery()
            ->fetch();
            
        if (!$state) return [];

        return $qb->select('*')->from('tm_counties')
            ->where($qb->expr()->eq('state_fips', $qb->createNamedParameter($state['fips_code'])))
            ->andWhere($qb->expr()->eq('is_enabled', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
            ->orderBy('county_name', 'ASC')
            ->executeQuery()
            ->fetchAll();
    }
}