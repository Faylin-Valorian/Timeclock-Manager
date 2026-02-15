<<<<<<< HEAD
<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Timesheet\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCA\TimeclockManager\Timesheet\Service\TimesheetService;
use OCA\TimeclockManager\Timesheet\Db\TimesheetMapper;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

class TimesheetController extends Controller {
    private $userSession;
    private $groupManager;
    private $service;
    private $mapper;

    public function __construct(IRequest $request, 
                                IUserSession $userSession, 
                                IGroupManager $groupManager,
                                TimesheetService $service,
                                TimesheetMapper $mapper) {
        parent::__construct('timeclock-manager', $request);
        $this->userSession = $userSession;
        $this->groupManager = $groupManager;
        $this->service = $service;
        $this->mapper = $mapper;
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
        return new DataResponse($this->mapper->getCounties($abbr));
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getTimesheet(int $id): DataResponse {
        $uid = $this->getEffectiveUserId();
        $ts = $this->mapper->getById($id, $uid);
        
        if (!$ts) return new DataResponse([], 404);
        
        $ts['activities'] = $this->mapper->getActivities($id);
        
        $currentUser = $this->userSession->getUser();
        $ts['is_admin'] = $currentUser && $this->groupManager->isAdmin($currentUser->getUID());
        
        return new DataResponse($ts);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function deleteTimesheet(int $id): DataResponse {
        try {
            $uid = $this->getEffectiveUserId();
            $this->service->deleteTimesheet($id, $uid);
            return new DataResponse(['status' => 'success']);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function restoreTimesheet(int $id): DataResponse {
        try {
            $uid = $this->getEffectiveUserId();
            $this->service->restoreTimesheet($id, $uid);
            return new DataResponse(['status' => 'success']);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function saveTimesheet(): DataResponse {
        try {
            $uid = $this->getEffectiveUserId();
            $data = $this->request->getParams();
            $id = $this->service->saveTimesheet($data, $uid);
            return new DataResponse(['status' => 'success', 'id' => $id]);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }
=======
<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Timesheet\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCA\TimeclockManager\Timesheet\Service\TimesheetService;
use OCA\TimeclockManager\Timesheet\Db\TimesheetMapper;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

class TimesheetController extends Controller {
    private $userSession;
    private $groupManager;
    private $service;
    private $mapper;

    public function __construct(IRequest $request, 
                                IUserSession $userSession, 
                                IGroupManager $groupManager,
                                TimesheetService $service,
                                TimesheetMapper $mapper) {
        parent::__construct('timeclock-manager', $request);
        $this->userSession = $userSession;
        $this->groupManager = $groupManager;
        $this->service = $service;
        $this->mapper = $mapper;
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
        return new DataResponse($this->mapper->getCounties($abbr));
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getTimesheet(int $id): DataResponse {
        $uid = $this->getEffectiveUserId();
        $ts = $this->mapper->getById($id, $uid);
        
        if (!$ts) return new DataResponse([], 404);
        
        $ts['activities'] = $this->mapper->getActivities($id);
        
        $currentUser = $this->userSession->getUser();
        $ts['is_admin'] = $currentUser && $this->groupManager->isAdmin($currentUser->getUID());
        
        return new DataResponse($ts);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function deleteTimesheet(int $id): DataResponse {
        try {
            $uid = $this->getEffectiveUserId();
            $this->service->deleteTimesheet($id, $uid);
            return new DataResponse(['status' => 'success']);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function restoreTimesheet(int $id): DataResponse {
        try {
            $uid = $this->getEffectiveUserId();
            $this->service->restoreTimesheet($id, $uid);
            return new DataResponse(['status' => 'success']);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function saveTimesheet(): DataResponse {
        try {
            $uid = $this->getEffectiveUserId();
            $data = $this->request->getParams();
            $id = $this->service->saveTimesheet($data, $uid);
            return new DataResponse(['status' => 'success', 'id' => $id]);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }
>>>>>>> 6edf4541514ac05df3faedfa2f961d65a5495869
}