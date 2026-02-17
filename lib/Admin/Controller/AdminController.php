<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Controller;

use OCA\TimeclockManager\Admin\Service\AdminService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

class AdminController extends Controller {

    private $userSession;
    private $service;

    public function __construct(
        IRequest $request,
        IUserSession $userSession,
        AdminService $service
    ) {
        parent::__construct('timeclock-manager', $request);
        $this->userSession = $userSession;
        $this->service = $service;
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getMyAccess(): DataResponse {
        $user = $this->userSession->getUser();
        if (!$user) return new DataResponse(['error' => 'Not authenticated'], 401);

        return new DataResponse($this->service->getMyAccess($user));
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getBootstrap(): DataResponse {
        $user = $this->userSession->getUser();
        if (!$user || !$this->service->canManageAdmin($user)) {
            return new DataResponse(['error' => 'Admin access required'], 403);
        }
        return new DataResponse($this->service->getBootstrap());
    }

    #[NoAdminRequired]
    public function saveHoliday(): DataResponse {
        $user = $this->userSession->getUser();
        if (!$user || !$this->service->canManageAdmin($user)) {
            return new DataResponse(['error' => 'Admin access required'], 403);
        }

        $result = $this->service->saveHoliday([
            'id' => $this->request->getParam('id', 0),
            'name' => $this->request->getParam('name', ''),
            'start' => $this->request->getParam('start', ''),
            'end' => $this->request->getParam('end', ''),
            'bg' => $this->request->getParam('bg', '#95a5a6'),
            'archive' => $this->request->getParam('archive', 0),
        ]);

        if (!$result['ok']) {
            return new DataResponse(['error' => $result['error'] ?? 'Invalid holiday payload'], (int)($result['status'] ?? 400));
        }
        return new DataResponse(['ok' => true]);
    }

    #[NoAdminRequired]
    public function saveAccess(): DataResponse {
        $user = $this->userSession->getUser();
        if (!$user || !$this->service->canManageAdmin($user)) {
            return new DataResponse(['error' => 'Admin access required'], 403);
        }

        $result = $this->service->saveAccess([
            'rule_key' => $this->request->getParam('rule_key', ''),
            'allowed_groups' => $this->request->getParam('allowed_groups', []),
        ]);
        if (!$result['ok']) {
            return new DataResponse(['error' => $result['error'] ?? 'Invalid access payload'], (int)($result['status'] ?? 400));
        }
        return new DataResponse(['ok' => true]);
    }

}
