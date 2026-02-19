<?php
namespace OCA\TimeclockManager\Timesheet\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IGroupManager;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCA\TimeclockManager\Timesheet\Service\TimesheetService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

class TimesheetController extends Controller {

    private $service;
    private $userId;
    private $groupManager;
    private $db;

    public function __construct(
        $AppName,
        IRequest $request,
        TimesheetService $service,
        $UserId,
        IGroupManager $groupManager,
        IDBConnection $db
    ) {
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->userId = $UserId;
        $this->groupManager = $groupManager;
        $this->db = $db;
    }

    private function isAdminUser(): bool {
        if (empty($this->userId)) return false;
        return $this->groupManager->isAdmin($this->userId);
    }

    private function getEffectiveUserId(): string {
        $targetUid = (string)$this->request->getParam('target_user', '');
        if ($targetUid !== '' && $targetUid !== (string)$this->userId && $this->isAdminUser()) {
            return $targetUid;
        }
        return (string)$this->userId;
    }

    /**
     * Route: /api/attributes
     * Method: GET
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getAttributes() {
        $isAdmin = $this->isAdminUser();
        $qb = $this->db->getQueryBuilder();

        $jobs = $qb->select('job_id', 'job_name', 'is_pto')
            ->from('tm_jobs')
            ->where($qb->expr()->eq('job_archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
            ->orderBy('job_name', 'ASC')
            ->executeQuery()
            ->fetchAll();

        $qb2 = $this->db->getQueryBuilder();
        $states = $qb2->select('state_name', 'state_abbr', 'fips_code')
            ->from('tm_states')
            ->where($qb2->expr()->eq('is_enabled', $qb2->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
            ->orderBy('state_name', 'ASC')
            ->executeQuery()
            ->fetchAll();

        $ptoJob = '';
        foreach ($jobs as $job) {
            if ((int)($job['is_pto'] ?? 0) === 1) {
                $ptoJob = (string)$job['job_name'];
                break;
            }
        }

        return new DataResponse([
            'jobs' => $jobs,
            'states' => $states,
            'pto_job' => $ptoJob,
            'permissions' => [
                'can_view_archive' => true,
                'can_edit_archived' => $isAdmin,
                'can_restore_archived' => $isAdmin
            ]
        ]);
    }

    /**
     * Route: /api/timesheets/{id}
     * Method: GET
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getTimesheet(int $id) {
        return new DataResponse($this->service->find($id, $this->getEffectiveUserId()));
    }

    /**
     * Route: /api/timesheets
     * Method: POST
     * Handles both Create (New Entry) and Update (Existing Entry)
     */
    #[NoAdminRequired]
    public function saveTimesheet() {
        $data = $this->getParams();
        $effectiveUserId = $this->getEffectiveUserId();
        
        // [CRITICAL FIX] 
        // If ID exists, Update. If not, Create.
        if (!empty($data['id'])) {
            $existing = $this->service->find((int)$data['id'], $effectiveUserId);
            if (!$existing) {
                return new DataResponse(['error' => 'Timesheet not found.'], 404);
            }

            if ((int)$existing->getArchive() === 1 && !$this->isAdminUser()) {
                return new DataResponse(['error' => 'Archived records are read-only.'], 403);
            }

            return new DataResponse(
                $this->service->update((int)$data['id'], $data, $effectiveUserId, $this->isAdminUser())
            );
        } else {
            return new DataResponse($this->service->create($data, $effectiveUserId));
        }
    }

    /**
     * Route: /api/timesheets/{id}
     * Method: DELETE
     */
    #[NoAdminRequired]
    public function deleteTimesheet(int $id) {
        $effectiveUserId = $this->getEffectiveUserId();
        $existing = $this->service->find($id, $effectiveUserId);
        if (!$existing) {
            return new DataResponse(['error' => 'Timesheet not found.'], 404);
        }

        if ((int)$existing->getArchive() === 1 && !$this->isAdminUser()) {
            return new DataResponse(['error' => 'Archived records are read-only.'], 403);
        }

        return new DataResponse($this->service->delete($id, $effectiveUserId));
    }

    /**
     * Route: /api/timesheets/{id}/restore
     * Method: POST
     */
    #[NoAdminRequired]
    public function restoreTimesheet(int $id) {
        if (!$this->isAdminUser()) {
            return new DataResponse(['error' => 'Only admins can restore archived tabs.'], 403);
        }

        return new DataResponse($this->service->restore($id, $this->getEffectiveUserId()));
    }

    /**
     * Route: /api/locations/counties/{abbr}
     * Method: GET
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getCounties(string $abbr): DataResponse {
        $abbr = strtoupper(trim($abbr));
        if ($abbr === '') {
            return new DataResponse([]);
        }

        $stateQb = $this->db->getQueryBuilder();
        $state = $stateQb->select('fips_code')
            ->from('tm_states')
            ->where($stateQb->expr()->eq('state_abbr', $stateQb->createNamedParameter($abbr)))
            ->andWhere($stateQb->expr()->eq('is_enabled', $stateQb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
            ->executeQuery()
            ->fetch();

        if (!$state || empty($state['fips_code'])) {
            return new DataResponse([]);
        }

        $countyQb = $this->db->getQueryBuilder();
        $counties = $countyQb->select('county_name')
            ->from('tm_counties')
            ->where($countyQb->expr()->eq('state_fips', $countyQb->createNamedParameter((string)$state['fips_code'])))
            ->andWhere($countyQb->expr()->eq('is_enabled', $countyQb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
            ->orderBy('county_name', 'ASC')
            ->executeQuery()
            ->fetchAll();

        return new DataResponse($counties);
    }

    /**
     * Helper to extract parameters from request
     */
    private function getParams() {
        return [
            // This 'id' is sent by the patched frontend
            'id' => $this->request->getParam('id'), 
            
            'date' => $this->request->getParam('date'),
            'time_in' => $this->request->getParam('time_in'),
            'time_out' => $this->request->getParam('time_out'),
            'break_min' => $this->request->getParam('break_min'),
            'time_total' => $this->request->getParam('time_total'),
            'comments' => $this->request->getParam('comments'),
            
            'is_pto' => $this->request->getParam('is_pto'),
            
            'travel_per_diem' => $this->request->getParam('travel_per_diem'),
            'travel_road_scanning' => $this->request->getParam('travel_road_scanning'),
            'travel_first_last_day' => $this->request->getParam('travel_first_last_day'),
            'travel_overnight' => $this->request->getParam('travel_overnight'),
            
            'travel_state' => $this->request->getParam('travel_state'),
            'travel_county' => $this->request->getParam('travel_county'),
            'travel_miles' => $this->request->getParam('travel_miles'),
            'travel_extra_expenses' => $this->request->getParam('travel_extra_expenses'),
            
            'activities' => $this->request->getParam('activities', [])
        ];
    }
}
