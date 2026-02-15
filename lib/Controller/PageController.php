<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

class PageController extends Controller {
    public function __construct(IRequest $request) {
        parent::__construct('timeclock-manager', $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse {
        // Loads templates/calendar/calendar.php
        return new TemplateResponse('timeclock-manager', 'calendar/calendar');
    }
}