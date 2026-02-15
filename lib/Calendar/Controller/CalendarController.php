<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Calendar\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCA\TimeclockManager\Calendar\Service\CalendarService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

class CalendarController extends Controller {
    private $userSession;
    private $groupManager;
    private $service;

    public function __construct(IRequest $request, 
                                IUserSession $userSession, 
                                IGroupManager $groupManager,
                                CalendarService $service) {
        parent::__construct('timeclock-manager', $request);
        $this->userSession = $userSession;
        $this->groupManager = $groupManager;
        $this->service = $service;
    }

    private function getEffectiveUserId(): string {
        $currentUser = $this->userSession->getUser();
        if (!$currentUser) return ''; 
        
        $currentUid = $currentUser->getUID();
        $targetUid = $this->request->getParam('target_user');

        if ($targetUid && $targetUid !== $currentUid) {
            if ($this->groupManager->isAdmin($currentUid)) {
                return $targetUid;
            }
        }
        return $currentUid;
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getTimesheets(): DataResponse {
        $start = $this->request->getParam('start');
        $end = $this->request->getParam('end');
        $archive = (int)$this->request->getParam('archive', 0);
        
        $uid = $this->getEffectiveUserId(); 
        return new DataResponse($this->service->getCalendarEvents($uid, $start, $end, $archive));
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getHolidays(): DataResponse {
        $start = $this->request->getParam('start');
        $end = $this->request->getParam('end');
        return new DataResponse($this->service->getHolidays($start, $end));
    }
}