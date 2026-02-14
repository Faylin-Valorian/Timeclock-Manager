<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;

class AdminController extends Controller {

    public function __construct(IRequest $request) {
        parent::__construct('timeclock_manager', $request);
    }

    /**
     * @NoAdminRequired 
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse { 
        return new TemplateResponse('timeclock_manager', 'admin'); 
    }
}