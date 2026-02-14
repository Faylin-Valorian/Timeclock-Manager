<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Holidays\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class HolidaysMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'tm_holidays');
    }

    public function getHolidays(): array {
        return $this->db->getQueryBuilder()
            ->select('*')
            ->from('tm_holidays')
            ->orderBy('holiday_start_date', 'DESC')
            ->executeQuery()
            ->fetchAll();
    }

    public function saveHoliday(array $data): void {
        $qb = $this->db->getQueryBuilder();
        $bg = $data['color'] ?? ($data['bg'] ?? '#e67e22'); // Handle frontend key var

        if (!empty($data['id'])) { 
            $qb->update('tm_holidays')
            ->set('holiday_name', $qb->createNamedParameter($data['name']))
            ->set('holiday_start_date', $qb->createNamedParameter($data['start_date'] ?? $data['start']))
            ->set('holiday_end_date', $qb->createNamedParameter($data['end_date'] ?? $data['end']))
            ->set('holiday_bg', $qb->createNamedParameter($bg))
            ->where($qb->expr()->eq('holiday_id', $qb->createNamedParameter($data['id'])))
            ->execute();
        } else {
            $qb->insert('tm_holidays')
            ->values([
                'holiday_name' => $qb->createNamedParameter($data['name']),
                'holiday_start_date' => $qb->createNamedParameter($data['start_date'] ?? $data['start']),
                'holiday_end_date' => $qb->createNamedParameter($data['end_date'] ?? $data['end']),
                'holiday_bg' => $qb->createNamedParameter($bg),
                'holiday_archive' => $qb->createNamedParameter(0)
            ])->execute();
        }
    }

    public function toggleHoliday(int $id): void {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('holiday_archive')
            ->from('tm_holidays')
            ->where($qb->expr()->eq('holiday_id', $qb->createNamedParameter($id)))
            ->executeQuery()
            ->fetch();

        if ($row) {
            $newStatus = ((int)$row['holiday_archive'] === 1) ? 0 : 1;
            $qbUpdate = $this->db->getQueryBuilder();
            $qbUpdate->update('tm_holidays')
                ->set('holiday_archive', $qbUpdate->createNamedParameter($newStatus))
                ->where($qbUpdate->expr()->eq('holiday_id', $qbUpdate->createNamedParameter($id)))
                ->execute();
        }
    }

    public function deleteHoliday(int $id): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('tm_holidays')
           ->where($qb->expr()->eq('holiday_id', $qb->createNamedParameter($id)))
           ->execute();
    }
}