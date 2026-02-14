<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Locations\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class LocationsMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'tm_states');
    }

    public function getStates(): array {
        return $this->db->getQueryBuilder()
            ->select('*')
            ->from('tm_states')
            ->orderBy('state_name', 'ASC')
            ->executeQuery()
            ->fetchAll();
    }

    public function getCountiesByStateAbbr(string $abbr): array {
        $qb = $this->db->getQueryBuilder();
        $state = $qb->select('fips_code')
           ->from('tm_states')
           ->where($qb->expr()->eq('state_abbr', $qb->createNamedParameter($abbr)))
           ->executeQuery()
           ->fetch();

        if (!$state) return []; 

        $fips = $state['fips_code'];
        $qb2 = $this->db->getQueryBuilder();
        return $qb2->select('*')
                ->from('tm_counties')
                ->where($qb2->expr()->eq('state_fips', $qb2->createNamedParameter($fips)))
                ->orderBy('county_name', 'ASC')
                ->executeQuery()
                ->fetchAll();
    }

    public function toggleState(int $id): void {
        $qb = $this->db->getQueryBuilder();
        $state = $qb->select('is_enabled', 'fips_code')
                    ->from('tm_states')
                    ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
                    ->executeQuery()
                    ->fetch();

        if (!$state) return;

        $newState = ((int)$state['is_enabled'] === 1) ? 0 : 1;
        
        // Toggle State
        $qbState = $this->db->getQueryBuilder();
        $qbState->update('tm_states')
                ->set('is_enabled', $qbState->createNamedParameter($newState))
                ->where($qbState->expr()->eq('id', $qbState->createNamedParameter($id)))
                ->execute();

        // Toggle Linked Counties
        $qbCounty = $this->db->getQueryBuilder();
        $qbCounty->update('tm_counties')
                 ->set('is_enabled', $qbCounty->createNamedParameter($newState))
                 ->where($qbCounty->expr()->eq('state_fips', $qbCounty->createNamedParameter($state['fips_code'])))
                 ->execute();
    }

    public function toggleCounty(int $id): int {
        $qb = $this->db->getQueryBuilder();
        $county = $qb->select('is_enabled')->from('tm_counties')->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))->executeQuery()->fetch();
        
        $newState = 0;
        if($county) {
            $newState = ((int)$county['is_enabled'] === 1) ? 0 : 1;
            $qbUpdate = $this->db->getQueryBuilder();
            $qbUpdate->update('tm_counties')
               ->set('is_enabled', $qbUpdate->createNamedParameter($newState))
               ->where($qbUpdate->expr()->eq('id', $qbUpdate->createNamedParameter($id)))
               ->execute();
        }
        return $newState;
    }
}