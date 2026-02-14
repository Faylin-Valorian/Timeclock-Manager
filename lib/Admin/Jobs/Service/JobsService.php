<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Jobs\Service;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCA\TimeclockManager\Admin\Jobs\Db\JobsMapper;

class JobsService {
    private $mapper;
    private $appData;

    public function __construct(JobsMapper $mapper, IAppData $appData) {
        $this->mapper = $mapper;
        $this->appData = $appData;
    }

    public function getAll(): array {
        return $this->mapper->getJobs();
    }

    public function save(array $data): void {
        $this->mapper->saveJob($data);
    }

    public function toggle(int $id): void {
        $this->mapper->toggleJob($id);
    }

    // --- Image Handling ---
    public function saveThumbnail(string $id, $resource): void {
        try {
            $folder = $this->appData->getFolder('thumbnails');
        } catch (NotFoundException $e) {
            $folder = $this->appData->newFolder('thumbnails');
        }

        $filename = "thumb_{$id}.jpg";
        try {
            $file = $folder->getFile($filename);
            $file->delete(); 
        } catch (NotFoundException $e) {}

        $folder->newFile($filename, stream_get_contents($resource));
    }
}