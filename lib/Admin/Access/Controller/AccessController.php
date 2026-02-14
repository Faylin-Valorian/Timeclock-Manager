<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Access\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\TimeclockManager\Admin\Access\Service\AccessService;
use OCA\TimeclockManager\Service\AnalysisService;

class AccessController extends Controller {
    private $service;
    private $analysisService;

    public function __construct(IRequest $request, 
                                AccessService $service,
                                AnalysisService $analysisService) {
        parent::__construct('timeclock_manager', $request);
        $this->service = $service;
        $this->analysisService = $analysisService;
    }

    private function checkPerms(): void {
        if (!$this->analysisService->checkAccess('admin_access')) {
            throw new \Exception("Access Denied");
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getGroups(): DataResponse {
        try { 
            $this->checkPerms(); 
            return new DataResponse($this->service->getAllGroups());
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getAccess(): DataResponse {
        try { 
            $this->checkPerms(); 
            return new DataResponse($this->service->getRules());
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveAccess(): DataResponse {
        try { 
            $this->checkPerms(); 
            
            $data = $this->request->getParams();
            if (empty($data['rule_key'])) return new DataResponse(['error' => 'Missing key'], 400);
            
            // Logic for Add/Remove specific group or full replace
            // For simplicity (and matching old logic), we expect a full list or we handle add/remove here
            // The frontend sends {key, gid, action} usually in my previous JS code, 
            // OR the old controller expected 'allowed_groups' as array.
            
            // ADAPTING TO NEW JS STRUCTURE (action based):
            if (isset($data['action'])) {
                $currentRules = $this->service->getRules();
                $currentGroups = $currentRules[$data['rule_key']] ?? [];
                
                if ($data['action'] === 'add') {
                    if (!in_array($data['gid'], $currentGroups)) $currentGroups[] = $data['gid'];
                } else {
                    $currentGroups = array_diff($currentGroups, [$data['gid']]);
                }
                $groups = array_values($currentGroups);
            } else {
                // Fallback to full replace if sent that way
                $groups = $data['allowed_groups'] ?? [];
            }

            $this->service->saveRule($data['rule_key'], $groups);
            return new DataResponse(['status' => 'success']);
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }
}