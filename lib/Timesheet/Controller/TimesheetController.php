<?php
namespace OCA\TimeclockManager\Timesheet\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCA\TimeclockManager\Timesheet\Service\TimesheetService;

class TimesheetController extends Controller {

    private $service;
    private $userId;

    public function __construct($AppName, IRequest $request, TimesheetService $service, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->userId = $UserId;
    }

    /**
     * Route: /api/attributes
     * Method: GET
     */
    public function getAttributes() {
        // [TODO] Wire up JobService and LocationService here
        // Returning empty structures to prevent frontend crashes
        return new DataResponse([
            'jobs' => [],
            'states' => []
        ]);
    }

    /**
     * Route: /api/timesheets/{id}
     * Method: GET
     */
    public function getTimesheet(int $id) {
        return new DataResponse($this->service->find($id, $this->userId));
    }

    /**
     * Route: /api/timesheets
     * Method: POST
     * Handles both Create and Update based on presence of 'id'
     */
    public function saveTimesheet() {
        $data = $this->getParams();
        
        // [CRITICAL] Check 'id' to distinguish Update vs Create
        if (!empty($data['id'])) {
            return new DataResponse($this->service->update((int)$data['id'], $data, $this->userId));
        } else {
            return new DataResponse($this->service->create($data, $this->userId));
        }
    }

    /**
     * Route: /api/timesheets/{id}
     * Method: DELETE
     */
    public function deleteTimesheet(int $id) {
        return new DataResponse($this->service->delete($id, $this->userId));
    }

    /**
     * Route: /api/timesheets/{id}/restore
     * Method: POST
     */
    public function restoreTimesheet(int $id) {
        // Placeholder for future restore logic
        return new DataResponse(['status' => 'success', 'message' => 'Not implemented']);
    }

    /**
     * Route: /api/locations/counties/{abbr}
     * Method: GET
     */
    public function getCounties(string $abbr) {
        // Placeholder for county lookup
        return new DataResponse([]);
    }

    /**
     * Extract parameters from the request
     */
    private function getParams() {
        return [
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