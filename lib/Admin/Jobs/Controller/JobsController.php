<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Jobs\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\TimeclockManager\Admin\Jobs\Service\JobsService;
use OCA\TimeclockManager\Service\AnalysisService;

class JobsController extends Controller {
    private $service;
    private $analysisService;

    public function __construct(IRequest $request, 
                                JobsService $service,
                                AnalysisService $analysisService) {
        parent::__construct('timeclock_manager', $request);
        $this->service = $service;
        $this->analysisService = $analysisService;
    }

    private function checkPerms(): void {
        if (!$this->analysisService->checkAccess('admin_jobs')) {
            throw new \Exception("Access Denied");
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getJobs(): DataResponse { 
        try { 
            $this->checkPerms(); 
            return new DataResponse($this->service->getAll()); 
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveJob(): DataResponse {
        try { 
            $this->checkPerms(); 
            $this->service->save($this->request->getParams());
            return new DataResponse(['status' => 'success']);
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function toggleJob(int $id): DataResponse {
        try { 
            $this->checkPerms(); 
            $this->service->toggle($id);
            return new DataResponse(['status' => 'success']);
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }
}