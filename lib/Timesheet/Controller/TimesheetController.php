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
     * @NoAdminRequired
     */
    public function index() {
        return new DataResponse($this->service->findAll($this->userId));
    }

    /**
     * @NoAdminRequired
     */
    public function show(int $id) {
        return new DataResponse($this->service->find($id, $this->userId));
    }

    /**
     * @NoAdminRequired
     */
    public function create() {
        $data = $this->getParams();
        return new DataResponse($this->service->create($data, $this->userId));
    }

    /**
     * @NoAdminRequired
     */
    public function update(int $id) {
        $data = $this->getParams();
        return new DataResponse($this->service->update($id, $data, $this->userId));
    }

    /**
     * @NoAdminRequired
     */
    public function destroy(int $id) {
        return new DataResponse($this->service->delete($id, $this->userId));
    }

    /**
     * Extract parameters from the request
     */
    private function getParams() {
        return [
            // Standard Time Fields
            'date' => $this->request->getParam('date'),
            'time_in' => $this->request->getParam('time_in'),
            'time_out' => $this->request->getParam('time_out'),
            'break_min' => $this->request->getParam('break_min'),
            'time_total' => $this->request->getParam('time_total'),
            'comments' => $this->request->getParam('comments'),
            
            // PTO Toggle
            'is_pto' => $this->request->getParam('is_pto'),
            
            // Travel Toggles
            'travel_per_diem' => $this->request->getParam('travel_per_diem'),
            'travel_road_scanning' => $this->request->getParam('travel_road_scanning'),
            'travel_first_last_day' => $this->request->getParam('travel_first_last_day'),
            'travel_overnight' => $this->request->getParam('travel_overnight'),
            
            // Travel Location Data
            'travel_state' => $this->request->getParam('travel_state'),
            'travel_county' => $this->request->getParam('travel_county'),
            'travel_miles' => $this->request->getParam('travel_miles'),
            'travel_extra_expenses' => $this->request->getParam('travel_extra_expenses'),
            
            // Child Rows (Activities)
            // Expects array of objects: [{ description: "...", percent: 50 }, ...]
            'activities' => $this->request->getParam('activities', [])
        ];
    }
}