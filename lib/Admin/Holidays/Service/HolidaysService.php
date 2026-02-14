<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Holidays\Service;

use OCA\TimeclockManager\Admin\Holidays\Db\HolidaysMapper;

class HolidaysService {
    private $mapper;

    public function __construct(HolidaysMapper $mapper) {
        $this->mapper = $mapper;
    }

    public function getAll(): array {
        return $this->mapper->getHolidays();
    }

    public function save(array $params): void {
        $this->mapper->saveHoliday($params);
    }

    public function toggle(int $id): void {
        $this->mapper->toggleHoliday($id);
    }

    public function delete(int $id): void {
        $this->mapper->deleteHoliday($id);
    }
}