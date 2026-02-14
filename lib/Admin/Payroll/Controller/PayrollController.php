<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Payroll\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\TimeclockManager\Admin\Payroll\Service\PayrollService;
use OCA\TimeclockManager\Service\AnalysisService;

class PayrollController extends Controller {
    private $service;
    private $analysisService;

    public function __construct(IRequest $request, 
                                PayrollService $service,
                                AnalysisService $analysisService) {
        parent::__construct('timeclock_manager', $request);
        $this->service = $service;
        $this->analysisService = $analysisService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getSettings(): DataResponse {
        // Read access allowed if user can see payroll OR has global admin access
        if (!$this->analysisService->checkAccess('admin_payroll') && 
            !$this->analysisService->checkAccess('admin_global_access')) {
             return new DataResponse([], 403);
        }
        return new DataResponse($this->service->getSettings());
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveSetting(): DataResponse {
        if (!$this->analysisService->checkAccess('admin_payroll')) {
            return new DataResponse(['error' => 'Access Denied'], 403);
        }
        
        $this->service->saveSettings($this->request->getParams());
        return new DataResponse(['status' => 'success']);
    }
}