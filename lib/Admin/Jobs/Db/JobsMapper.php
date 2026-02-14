<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Jobs\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class JobsMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'tm_jobs');
    }

    public function getJobs(): array {
        return $this->db->getQueryBuilder()
            ->select('*')
            ->from('tm_jobs')
            ->orderBy('job_name', 'ASC')
            ->executeQuery()
            ->fetchAll();
    }

    public function saveJob(array $data): void {
        $qb = $this->db->getQueryBuilder();
        
        $fields = [
            'job_name' => $data['job_name'] ?? $data['name'],
            'job_description' => $data['job_description'] ?? ($data['description'] ?? ''),
            'is_pto' => isset($data['is_pto']) && ($data['is_pto'] === 'true' || $data['is_pto'] === true || $data['is_pto'] === 1) ? 1 : 0,
            'job_revenue' => (float)($data['job_revenue'] ?? ($data['revenue'] ?? 0)),
            'job_expense_budget' => (float)($data['job_expense_budget'] ?? ($data['expense'] ?? 0)),
            'job_hourly_cost' => (float)($data['job_hourly_cost'] ?? ($data['hourly'] ?? 0))
        ];

        // Handle inconsistent ID naming from frontend (id vs job_id)
        $id = !empty($data['id']) ? $data['id'] : (!empty($data['job_id']) ? $data['job_id'] : null);

        if ($id) {
            $qb->update('tm_jobs');
            foreach ($fields as $col => $val) {
                $qb->set($col, $qb->createNamedParameter($val));
            }
            $qb->where($qb->expr()->eq('job_id', $qb->createNamedParameter($id)))
               ->execute();
        } else {
            $qb->insert('tm_jobs');
            foreach ($fields as $col => $val) {
                $qb->setValue($col, $qb->createNamedParameter($val));
            }
            $qb->execute();
        }
    }

    public function toggleJob(int $id): void {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('job_archive')
            ->from('tm_jobs')
            ->where($qb->expr()->eq('job_id', $qb->createNamedParameter($id)))
            ->executeQuery()
            ->fetch();

        if ($row) {
            $newStatus = ((int)$row['job_archive'] === 1) ? 0 : 1;
            $qbUpdate = $this->db->getQueryBuilder();
            $qbUpdate->update('tm_jobs')
                ->set('job_archive', $qbUpdate->createNamedParameter($newStatus))
                ->where($qbUpdate->expr()->eq('job_id', $qbUpdate->createNamedParameter($id)))
                ->execute();
        }
    }
}