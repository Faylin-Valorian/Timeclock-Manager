<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Payroll\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class PayrollMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'stech_admin_settings');
    }

    public function getSettings(): array {
        return $this->db->getQueryBuilder()
            ->select('*')
            ->from('stech_admin_settings')
            ->executeQuery()
            ->fetchAll();
    }

    public function saveSetting(string $key, string $value): void {
        $qb = $this->db->getQueryBuilder();
        $exists = $qb->select('setting_key')
                     ->from('stech_admin_settings')
                     ->where($qb->expr()->eq('setting_key', $qb->createNamedParameter($key)))
                     ->executeQuery()
                     ->fetch();

        if ($exists) {
            $qb = $this->db->getQueryBuilder();
            $qb->update('stech_admin_settings')
               ->set('setting_value', $qb->createNamedParameter($value))
               ->where($qb->expr()->eq('setting_key', $qb->createNamedParameter($key)))
               ->execute();
        } else {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('stech_admin_settings')
               ->values([
                   'setting_key' => $qb->createNamedParameter($key),
                   'setting_value' => $qb->createNamedParameter($value)
               ])->execute();
        }
    }
}