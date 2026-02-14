<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Access\Service;

use OCP\IGroupManager;
use OCA\TimeclockManager\Admin\Access\Db\AccessMapper;

class AccessService {
    private $groupManager;
    private $mapper;

    public function __construct(IGroupManager $groupManager, AccessMapper $mapper) {
        $this->groupManager = $groupManager;
        $this->mapper = $mapper;
    }

    public function getAllGroups(): array {
        $groups = $this->groupManager->search('');
        $list = [];
        foreach ($groups as $g) { 
            $list[] = ['gid' => $g->getGID(), 'displayName' => $g->getDisplayName()]; 
        }
        return $list;
    }

    public function getRules(): array {
        $rules = [];
        foreach($this->mapper->getAccessRules() as $row) { 
            $rules[$row['rule_key']] = json_decode($row['allowed_groups'] ?? '[]', true); 
        }
        return $rules;
    }

    public function saveRule(string $key, array $groups): void {
        $this->mapper->saveAccessRule($key, json_encode($groups));
    }
}