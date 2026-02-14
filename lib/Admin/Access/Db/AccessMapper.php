<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Access\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class AccessMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'tm_access_rules');
    }

    public function getAccessRules(): array {
        return $this->db->getQueryBuilder()
            ->select('*')
            ->from('tm_access_rules')
            ->executeQuery()
            ->fetchAll();
    }

    public function saveAccessRule(string $key, string $jsonGroups): void {
        $qb = $this->db->getQueryBuilder();
        $exists = $qb->select('id')
                     ->from('tm_access_rules')
                     ->where($qb->expr()->eq('rule_key', $qb->createNamedParameter($key)))
                     ->executeQuery()
                     ->fetch();

        if ($exists) {
            $qb = $this->db->getQueryBuilder();
            $qb->update('tm_access_rules')
               ->set('allowed_groups', $qb->createNamedParameter($jsonGroups))
               ->where($qb->expr()->eq('rule_key', $qb->createNamedParameter($key)))
               ->execute();
        } else {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('tm_access_rules')
               ->values([
                   'rule_key' => $qb->createNamedParameter($key),
                   'allowed_groups' => $qb->createNamedParameter($jsonGroups)
               ])->execute();
        }
    }
}