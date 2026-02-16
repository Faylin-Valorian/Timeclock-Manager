<?php
namespace OCA\TimeclockManager\Timesheet\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\Entity; // [CRITICAL] Required for strict typing
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ActivityMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'tm_activity', Activity::class);
    }

    public function findAllForTimesheet(int $timesheetId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('tm_activity')
           ->where(
               $qb->expr()->eq('timesheet_id', $qb->createNamedParameter($timesheetId, IQueryBuilder::PARAM_INT))
           );

        return $this->findEntities($qb);
    }

    public function deleteAllForTimesheet(int $timesheetId) {
        $qb = $this->db->getQueryBuilder();

        $qb->delete('tm_activity')
           ->where(
               $qb->expr()->eq('timesheet_id', $qb->createNamedParameter($timesheetId, IQueryBuilder::PARAM_INT))
           );

        $qb->execute();
    }

    // [FIXED] Strict signature to match QBMapper
    public function insert(Entity $entity): Entity {
        /** @var Activity $entity */
        
        $sql = 'INSERT INTO `*PREFIX*tm_activity` 
                (timesheet_id, activity_description, activity_percent)
                VALUES (?, ?, ?)';

        $params = [
            $entity->getTimesheetId(),
            $entity->getActivityDescription(),
            $entity->getActivityPercent()
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $entity->setId($this->db->lastInsertId('*PREFIX*tm_activity'));
        return $entity;
    }
}