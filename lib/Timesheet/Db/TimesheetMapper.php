<?php
namespace OCA\TimeclockManager\Timesheet\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\Entity;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class TimesheetMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'tm_timesheets', Timesheet::class);
    }

    public function find(int $id, string $userId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('tm_timesheets')
           ->where(
               $qb->expr()->eq('timesheet_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
           )
           ->andWhere(
               $qb->expr()->eq('userid', $qb->createNamedParameter($userId))
           );

        return $this->findEntity($qb);
    }

    public function findAll(string $userId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('tm_timesheets')
           ->where(
               $qb->expr()->eq('userid', $qb->createNamedParameter($userId))
           )
           ->andWhere(
               $qb->expr()->eq('archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT))
           )
           ->orderBy('timesheet_date', 'DESC');

        return $this->findEntities($qb);
    }

    /**
     * @param Entity|Timesheet $timesheet
     */
    public function insert(Entity $timesheet): Entity {
        $sql = 'INSERT INTO `*PREFIX*tm_timesheets` 
                (userid, timesheet_date, time_in, time_out, time_break, time_total, is_pto, 
                 travel_road_scanning, travel_first_last_day, travel_overnight, travel_per_diem, 
                 travel_state, travel_county, travel_miles, travel_extra_expenses, 
                 additional_comments, archive)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $params = [
            $timesheet->getUserid(),
            $timesheet->getTimesheetDate(),
            $timesheet->getTimeIn(),
            $timesheet->getTimeOut(),
            $timesheet->getTimeBreak(),
            $timesheet->getTimeTotal(),
            $timesheet->getIsPto(),
            
            $timesheet->getTravelRoadScanning(),
            $timesheet->getTravelFirstLastDay(),
            $timesheet->getTravelOvernight(),
            $timesheet->getTravelPerDiem(),
            
            $timesheet->getTravelState(),
            $timesheet->getTravelCounty(),
            $timesheet->getTravelMiles(),
            $timesheet->getTravelExtraExpenses(),
            
            $timesheet->getAdditionalComments(),
            $timesheet->getArchive() ?? 0
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // [CRITICAL] Set the custom ID on the entity so it knows it is saved
        $id = $this->db->lastInsertId('*PREFIX*tm_timesheets');
        $timesheet->setTimesheetId((int)$id);
        
        return $timesheet;
    }

    /**
     * @param Entity|Timesheet $timesheet
     */
    public function update(Entity $timesheet): Entity {
        $sql = 'UPDATE `*PREFIX*tm_timesheets` SET 
                timesheet_date = ?, 
                time_in = ?, 
                time_out = ?, 
                time_break = ?, 
                time_total = ?, 
                is_pto = ?, 
                travel_road_scanning = ?, 
                travel_first_last_day = ?, 
                travel_overnight = ?, 
                travel_per_diem = ?, 
                travel_state = ?, 
                travel_county = ?, 
                travel_miles = ?, 
                travel_extra_expenses = ?, 
                additional_comments = ?, 
                archive = ?
                WHERE timesheet_id = ? AND userid = ?';

        $params = [
            $timesheet->getTimesheetDate(),
            $timesheet->getTimeIn(),
            $timesheet->getTimeOut(),
            $timesheet->getTimeBreak(),
            $timesheet->getTimeTotal(),
            $timesheet->getIsPto(),
            
            $timesheet->getTravelRoadScanning(),
            $timesheet->getTravelFirstLastDay(),
            $timesheet->getTravelOvernight(),
            $timesheet->getTravelPerDiem(),
            
            $timesheet->getTravelState(),
            $timesheet->getTravelCounty(),
            $timesheet->getTravelMiles(),
            $timesheet->getTravelExtraExpenses(),
            
            $timesheet->getAdditionalComments(),
            $timesheet->getArchive(),
            
            $timesheet->getId(), // This works because setTimesheetId() syncs it
            $timesheet->getUserid()
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $timesheet;
    }

    public function delete(Entity $entity): Entity {
        $sql = 'DELETE FROM `*PREFIX*tm_timesheets` WHERE timesheet_id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entity->getId()]);
        
        return $entity;
    }
}