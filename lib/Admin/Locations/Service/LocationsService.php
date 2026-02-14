<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Locations\Service;

use OCA\TimeclockManager\Admin\Locations\Db\LocationsMapper;

class LocationsService {
    private $mapper;

    public function __construct(LocationsMapper $mapper) {
        $this->mapper = $mapper;
    }

    public function getStates(): array {
        return $this->mapper->getStates();
    }

    public function getCounties(string $abbr): array {
        return $this->mapper->getCountiesByStateAbbr($abbr);
    }

    public function toggleState(int $id): void {
        $this->mapper->toggleState($id);
    }

    public function toggleCounty(int $id): int {
        return $this->mapper->toggleCounty($id);
    }
}