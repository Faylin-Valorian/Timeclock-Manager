<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Timesheet\EntryForm\Service;

use OCA\TimeclockManager\Timesheet\EntryForm\Db\EntryFormMapper;

class EntryFormService {
    private $mapper;

    public function __construct(EntryFormMapper $mapper) {
        $this->mapper = $mapper;
    }

    public function getAttributes(): array {
        return [
            'jobs' => $this->mapper->getActiveJobs(),
            'states' => $this->mapper->getEnabledStates()
        ];
    }
    
    // Note: Complex Save logic often stays in Controller in lightweight apps, 
    // or we move it here. For strict modularity, we leave it in the Controller 
    // to keep the service purely for business logic, unless we want to inject DB there.
    // For this refactor, I will keep the complex write logic in the Controller 
    // to match the original pattern, but use this service for reading attributes.
}