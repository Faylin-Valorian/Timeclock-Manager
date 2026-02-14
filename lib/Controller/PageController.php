<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IUserSession;
use OCP\IGroupManager;

class PageController extends Controller {
    private $userSession;
    private $groupManager;
    private $analysisService; // Can be null now

    public function __construct(IRequest $request, 
                                IUserSession $userSession, 
                                IGroupManager $groupManager,
                                $analysisService = null) { // No type hint to prevent crash
        parent::__construct('timeclock_manager', $request);
        $this->userSession = $userSession;
        $this->groupManager = $groupManager;
        $this->analysisService = $analysisService;
    }

    private function hasPerm(string $key, bool $isAdmin): bool {
        // Fallback: If service is missing, only Admins get access
        if (!$this->analysisService) return $isAdmin;
        return $isAdmin || $this->analysisService->checkAccess($key);
    }

    /** @NoAdminRequired @NoCSRFRequired */
    public function index(): TemplateResponse {
        $user = $this->userSession->getUser();
        $uid = $user ? $user->getUID() : '';
        $isAdmin = $user && $this->groupManager->isAdmin($uid);

        $params = [
            'mode' => 'timesheet',
            'user_id' => $uid,
            'is_admin' => $isAdmin,
            'can_view_analysis' => $this->hasPerm('can_view_analysis', $isAdmin),
            'can_view_admin' => $this->hasPerm('can_view_admin', $isAdmin),
            'can_toggle_archive' => $this->hasPerm('can_toggle_archive', $isAdmin),
            'target_user' => $this->request->getParam('target_user', '')
        ];

        $response = new TemplateResponse('timeclock_manager', 'timesheet/index');
        $response->setParams($params);
        return $response;
    }

    /** @NoAdminRequired @NoCSRFRequired */
    public function adminIndex(): TemplateResponse {
        $user = $this->userSession->getUser();
        $isAdmin = $user && $this->groupManager->isAdmin($user->getUID());
        
        if (!$this->hasPerm('can_view_admin', $isAdmin)) {
             return new TemplateResponse('timeclock_manager', 'error', ['msg' => 'Access Denied'], 403);
        }

        // Sidebar Permissions
        $perms = [
            'mode' => 'admin',
            'can_access_users'     => $this->hasPerm('admin_users', $isAdmin),
            'can_access_payroll'   => $this->hasPerm('admin_payroll', $isAdmin),
            'can_access_holidays'  => $this->hasPerm('admin_holidays', $isAdmin),
            'can_access_jobs'      => $this->hasPerm('admin_jobs', $isAdmin),
            'can_access_locations' => $this->hasPerm('admin_locations', $isAdmin),
            'can_access_access'    => $this->hasPerm('admin_access', $isAdmin),
        ];

        $response = new TemplateResponse('timeclock_manager', 'admin/index');
        $response->setParams($perms);
        return $response;
    }

    /** @NoAdminRequired @NoCSRFRequired */
    public function analysisIndex(): TemplateResponse {
        $user = $this->userSession->getUser();
        $isAdmin = $user && $this->groupManager->isAdmin($user->getUID());

        if (!$this->hasPerm('can_view_analysis', $isAdmin)) {
            return new TemplateResponse('timeclock_manager', 'error', ['msg' => 'Access Denied'], 403);
        }

        $perms = [
            'mode' => 'analysis',
            'can_view_others'              => $this->hasPerm('analysis_view_others', $isAdmin),
            'can_view_travel_analytics'    => $this->hasPerm('analysis_travel', $isAdmin),
            'can_view_financial_analytics' => $this->hasPerm('analysis_financial', $isAdmin),
            'can_view_location_analytics'  => $this->hasPerm('analysis_location', $isAdmin),
            'can_view_job_breakdown'       => ($this->hasPerm('analysis_financial', $isAdmin) || $this->hasPerm('analysis_jobs', $isAdmin))
        ];

        $response = new TemplateResponse('timeclock_manager', 'analysis/index');
        $response->setParams($perms);
        return $response;
    }
}