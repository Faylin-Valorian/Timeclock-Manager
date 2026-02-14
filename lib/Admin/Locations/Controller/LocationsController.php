<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Locations\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\TimeclockManager\Admin\Locations\Service\LocationsService;
use OCA\TimeclockManager\Service\AnalysisService;

class LocationsController extends Controller {
    private $service;
    private $analysisService;

    public function __construct(IRequest $request, 
                                LocationsService $service,
                                AnalysisService $analysisService) {
        parent::__construct('timeclock-manager', $request);
        $this->service = $service;
        $this->analysisService = $analysisService;
    }

    private function checkPerms(): void {
        if (!$this->analysisService->checkAccess('admin_locations')) {
            throw new \Exception("Access Denied");
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getStates(): DataResponse { 
        try { 
            $this->checkPerms(); 
            return new DataResponse($this->service->getStates()); 
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getCounties(string $abbr): DataResponse {
        try { 
            $this->checkPerms(); 
            return new DataResponse($this->service->getCounties($abbr));
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function toggleState(int $id): DataResponse {
        try { 
            $this->checkPerms(); 
            $this->service->toggleState($id);
            return new DataResponse(['status' => 'success']);
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function toggleCounty(int $id): DataResponse {
        try { 
            $this->checkPerms(); 
            $new = $this->service->toggleCounty($id);
            return new DataResponse(['status' => 'success', 'new_state' => $new]);
        } catch(\Exception $e) { return new DataResponse([], 403); }
    }
}