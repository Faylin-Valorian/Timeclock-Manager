<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Users\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\TimeclockManager\Admin\Users\Service\UsersService;
use OCA\TimeclockManager\Service\AnalysisService; // Used for permission checks

class UsersController extends Controller {
    private $service;
    private $analysisService;

    public function __construct(IRequest $request, 
                                UsersService $service,
                                AnalysisService $analysisService) {
        parent::__construct('timeclock-manager', $request);
        $this->service = $service;
        $this->analysisService = $analysisService;
    }

    private function checkPerms(): void {
        if (!$this->analysisService->checkAccess('admin_users')) {
            throw new \Exception("Access Denied");
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUsers(): DataResponse { 
        try { 
            $this->checkPerms();
            return new DataResponse($this->service->getAllUsers());
        } catch(\Exception $e) { 
            return new DataResponse(['error' => $e->getMessage()], 403); 
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function toggleUser(): DataResponse {
        try { 
            $this->checkPerms();
            
            $uid = $this->request->getParam('uid');
            if (!$uid) return new DataResponse(['error' => 'No UID'], 400);
            
            $newState = $this->service->toggleUserStatus($uid);
            return new DataResponse(['status' => 'success', 'new_state' => $newState]);
        } catch(\Exception $e) { 
            return new DataResponse(['error' => $e->getMessage()], 403); 
        }
    }
}