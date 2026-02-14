<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Users\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class UsersMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'stech_employees');
    }

    public function getEmployeeStatusMap(): array {
        try {
            $rows = $this->db->getQueryBuilder()
                ->select('*')
                ->from('stech_employees')
                ->executeQuery()
                ->fetchAll();
            $map = [];
            foreach($rows as $row) {
                $map[$row['uid']] = (int)$row['is_active'];
            }
            return $map;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function toggleUserStatus(string $uid, int $newStatus): void {
        $now = date('Y-m-d H:i:s');
        $qb = $this->db->getQueryBuilder();
        $exists = $qb->select('*')
                     ->from('stech_employees')
                     ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
                     ->executeQuery()
                     ->fetch();

        if ($exists) {
            $qb = $this->db->getQueryBuilder();
            $qb->update('stech_employees')
               ->set('is_active', $qb->createNamedParameter($newStatus))
               ->set('status_changed_at', $qb->createNamedParameter($now))
               ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
               ->execute();
        } else {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('stech_employees')
               ->values([
                   'uid' => $qb->createNamedParameter($uid),
                   'is_active' => $qb->createNamedParameter($newStatus),
                   'status_changed_at' => $qb->createNamedParameter($now)
               ])->execute();
        }
    }

    public function archiveUserHolidayEntries(string $uid): void {
        // NOTE: We use *PREFIX* when writing raw SQL, handled by Nextcloud
        $sql = "UPDATE `*PREFIX*stech_timesheets` AS t 
                SET t.`archive` = 1 
                WHERE t.`userid` = :uid 
                AND t.`timesheet_date` > :today 
                AND EXISTS (
                    SELECT 1 FROM `*PREFIX*stech_holidays` h 
                    WHERE t.`timesheet_date` BETWEEN h.`holiday_start_date` AND h.`holiday_end_date`
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $uid, 'today' => date('Y-m-d')]);
    }
}