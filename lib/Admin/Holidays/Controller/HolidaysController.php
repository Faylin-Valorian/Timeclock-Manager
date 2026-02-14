<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Holidays\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\TimeclockManager\Admin\Holidays\Service\HolidaysService;
use OCA\TimeclockManager\Service\AnalysisService;

class HolidaysController extends Controller {
    private $service;
    private $analysisService;

    public function __construct(IRequest $request, 
                                HolidaysService $service,
                                AnalysisService $analysisService) {
        parent::__construct('timeclock-manager', $request);
        $this->service = $service;
        $this->analysisService = $analysisService;
    }

    private function checkPerms(): void {
        if (!$this->analysisService->checkAccess('admin_holidays')) {
            throw new \Exception("Access Denied");
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getHolidays(): DataResponse { 
        try { 
            $this->checkPerms(); 
            return new DataResponse($this->service->getAll()); 
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveHoliday(): DataResponse {
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
    public function toggleHoliday(int $id): DataResponse {
        try { 
            $this->checkPerms(); 
            $this->service->toggle($id);
            return new DataResponse(['status' => 'success']);
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }
}