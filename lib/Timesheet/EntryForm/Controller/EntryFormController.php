<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Timesheet\EntryForm\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IUserSession;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCA\TimeclockManager\Timesheet\EntryForm\Service\EntryFormService;
use OCA\TimeclockManager\Timesheet\EntryForm\Db\EntryFormMapper;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

class EntryFormController extends Controller {
    private $userSession;
    private $service;
    private $mapper;
    private $db; // Direct DB needed for transaction/inserts
    private $groupManager;

    public function __construct(IRequest $request, IUserSession $userSession, EntryFormService $service, EntryFormMapper $mapper, IDBConnection $db, IGroupManager $groupManager) {
        parent::__construct('timeclock-manager', $request);
        $this->userSession = $userSession;
        $this->service = $service;
        $this->mapper = $mapper;
        $this->db = $db;
        $this->groupManager = $groupManager;
    }

    private function getEffectiveUserId(): string {
        $currentUser = $this->userSession->getUser();
        if (!$currentUser) return ''; 
        $currentUid = $currentUser->getUID();
        $targetUid = $this->request->getParam('target_user');
        if ($targetUid && $targetUid !== $currentUid && $this->groupManager->isAdmin($currentUid)) {
            return $targetUid;
        }
        return $currentUid;
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getAttributes(): DataResponse {
        return new DataResponse($this->service->getAttributes());
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getCounties(string $abbr): DataResponse {
        return new DataResponse($this->mapper->getCountiesByState($abbr));
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getTimesheet(int $id): DataResponse {
        $uid = $this->getEffectiveUserId();
        $ts = $this->mapper->getTimesheetById($id, $uid);
        if (!$ts) return new DataResponse([], 404);
        $ts['activities'] = $this->mapper->getActivitiesByTimesheet($id);
        $currentUser = $this->userSession->getUser();
        $ts['is_admin'] = $currentUser && $this->groupManager->isAdmin($currentUser->getUID());
        return new DataResponse($ts);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function saveTimesheet(): DataResponse {
        $uid = $this->getEffectiveUserId();
        $data = $this->request->getParams();
        $date = $data['date'] ?? null;
        
        if (!$date) return new DataResponse(['error' => 'Date is required.'], 400);
        
        $values = [
            'userid' => $uid, 'timesheet_date' => $date,
            'time_in' => $data['time_in'] ?? null, 'time_out' => $data['time_out'] ?? null,
            'time_break' => (int)($data['break_min'] ?? 0), 'time_total' => (float)($data['total_hours'] ?? 0),
            'additional_comments' => $data['comments'] ?? '',
            'travel_per_diem' => isset($data['req_per_diem']) && $data['req_per_diem'] == 1 ? 1 : 0,
            'travel_road_scanning' => isset($data['road_scanning']) && $data['road_scanning'] == 1 ? 1 : 0,
            'travel_first_last_day' => isset($data['first_last_day']) && $data['first_last_day'] == 1 ? 1 : 0,
            'travel_overnight' => isset($data['overnight']) && $data['overnight'] == 1 ? 1 : 0,
            'travel_state' => $data['state'] ?? null, 'travel_county' => $data['county'] ?? null,
            'travel_miles' => (int)($data['miles'] ?? 0), 'travel_extra_expenses' => (float)($data['extra_expense'] ?? 0),
            'archive' => 0 
        ];

        try {
            $qb = $this->db->getQueryBuilder();
            if (!empty($data['timesheet_id'])) {
                $tid = (int)$data['timesheet_id'];
                $qb->update('stech_timesheets');
                foreach ($values as $col => $val) { if ($col !== 'userid') $qb->set($col, $qb->createNamedParameter($val)); }
                $qb->where($qb->expr()->eq('timesheet_id', $qb->createNamedParameter($tid)))->executeStatement();
            } else {
                $qb->insert('stech_timesheets');
                foreach ($values as $col => $val) { $qb->setValue($col, $qb->createNamedParameter($val)); }
                $qb->executeStatement();
                $tid = (int)$this->db->lastInsertId('*PREFIX*stech_timesheets');
            }

            if ($tid > 0) {
                $this->db->prepare("DELETE FROM `*PREFIX*stech_activity` WHERE `timesheet_id` = ?")->execute([$tid]);
                if (isset($data['work_desc']) && is_array($data['work_desc'])) {
                    $stmt = $this->db->prepare("INSERT INTO `*PREFIX*stech_activity` (`timesheet_id`, `activity_description`, `activity_percent`) VALUES (?, ?, ?)");
                    foreach ($data['work_desc'] as $idx => $desc) { 
                        if (!empty($desc)) $stmt->execute([$tid, $desc, (int)($data['work_percent'][$idx] ?? 0)]);
                    }
                }
            }
            return new DataResponse(['status' => 'success', 'id' => $tid]);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function deleteTimesheet(int $id): DataResponse {
        $uid = $this->getEffectiveUserId(); 
        $this->db->prepare("UPDATE `*PREFIX*stech_timesheets` SET `archive` = 1 WHERE `timesheet_id` = ? AND `userid` = ?")->execute([$id, $uid]);
        return new DataResponse(['status' => 'success']);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function restoreTimesheet(int $id): DataResponse {
        $uid = $this->getEffectiveUserId();
        $this->db->prepare("UPDATE `*PREFIX*stech_timesheets` SET `archive` = 0 WHERE `timesheet_id` = ? AND `userid` = ?")->execute([$id, $uid]);
        return new DataResponse(['status' => 'success']);
    }
}