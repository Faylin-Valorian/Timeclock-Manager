<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Analysis\Controller;

use OCA\TimeclockManager\Analysis\Service\AnalysisService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

class AnalysisController extends Controller {

    private $userSession;
    private $groupManager;
    private $service;

    public function __construct(
        IRequest $request,
        IUserSession $userSession,
        IGroupManager $groupManager,
        AnalysisService $service
    ) {
        parent::__construct('timeclock-manager', $request);
        $this->userSession = $userSession;
        $this->groupManager = $groupManager;
        $this->service = $service;
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getSummary(): DataResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new DataResponse(['error' => 'Not authenticated'], 401);
        }

        $start = (string)$this->request->getParam('start', '');
        $end = (string)$this->request->getParam('end', '');
        if (!$this->isIsoDate($start) || !$this->isIsoDate($end)) {
            return new DataResponse(['error' => 'Invalid date range'], 400);
        }

        if ($start > $end) {
            return new DataResponse(['error' => 'Start date must be before end date'], 400);
        }

        $effectiveUid = $this->getEffectiveUserId($user->getUID());
        return new DataResponse($this->service->getSummary($effectiveUid, $start, $end));
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getDetail(): DataResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new DataResponse(['error' => 'Not authenticated'], 401);
        }

        $metric = (string)$this->request->getParam('metric', '');
        $start = (string)$this->request->getParam('start', '');
        $end = (string)$this->request->getParam('end', '');
        if (!$this->isIsoDate($start) || !$this->isIsoDate($end)) {
            return new DataResponse(['error' => 'Invalid date range'], 400);
        }
        if ($start > $end) {
            return new DataResponse(['error' => 'Start date must be before end date'], 400);
        }

        $allowed = [
            'total_hours',
            'pto_hours',
            'overtime_hours',
            'per_diem_requests',
            'road_scanning_days',
            'overnight_days'
        ];
        if (!in_array($metric, $allowed, true)) {
            return new DataResponse(['error' => 'Unsupported metric'], 400);
        }

        $effectiveUid = $this->getEffectiveUserId($user->getUID());
        return new DataResponse($this->service->getDetail($metric, $effectiveUid, $start, $end));
    }

    private function getEffectiveUserId(string $currentUid): string {
        $targetUid = (string)$this->request->getParam('target_user', '');
        if ($targetUid !== '' && $targetUid !== $currentUid && $this->groupManager->isAdmin($currentUid)) {
            return $targetUid;
        }
        return $currentUid;
    }

    private function isIsoDate(string $value): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        return $dt && $dt->format('Y-m-d') === $value;
    }
}
