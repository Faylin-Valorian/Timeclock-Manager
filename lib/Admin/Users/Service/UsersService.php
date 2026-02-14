<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Users\Service;

use OCP\IUserManager;
use OCA\TimeclockManager\Admin\Users\Db\UsersMapper;

class UsersService {
    private $userManager;
    private $mapper;

    public function __construct(IUserManager $userManager, UsersMapper $mapper) {
        $this->userManager = $userManager;
        $this->mapper = $mapper;
    }

    public function getAllUsers(): array {
        $ncUsers = $this->userManager->search('');
        $statusMap = $this->mapper->getEmployeeStatusMap();

        $list = [];
        foreach ($ncUsers as $user) {
            $uid = $user->getUID();
            $isActive = isset($statusMap[$uid]) ? (int)$statusMap[$uid] : 1;
            
            $list[] = [
                'uid' => $uid,
                'displayname' => $user->getDisplayName(), 
                'email' => $user->getEmailAddress(),
                'is_active' => $isActive
            ];
        }
        return $list;
    }

    public function toggleUserStatus(string $uid): int {
        $map = $this->mapper->getEmployeeStatusMap();
        $current = isset($map[$uid]) ? (int)$map[$uid] : 1;
        $new = ($current === 1) ? 0 : 1;
        
        $this->mapper->toggleUserStatus($uid, $new);
        
        // If deactivating, cleanup future holidays
        if ($new === 0) {
            $this->mapper->archiveUserHolidayEntries($uid);
        }
        
        return $new;
    }
}