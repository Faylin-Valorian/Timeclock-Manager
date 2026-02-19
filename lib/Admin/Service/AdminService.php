<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Service;

use OCA\TimeclockManager\Admin\Db\AdminMapper;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;

class AdminService {

    private $mapper;
    private $groupManager;
    private $userManager;

    public const ACCESS_RULE_KEYS = [
        'admin_access',
        'analysis_tab',
        'view_archive_toggle',
        'admin_holidays',
        'admin_users'
    ];
    private const DEVELOPER_GROUP = 'Developer';

    public function __construct(
        AdminMapper $mapper,
        IGroupManager $groupManager,
        IUserManager $userManager
    ) {
        $this->mapper = $mapper;
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
    }

    public function getMyAccess(IUser $user): array {
        $groups = $this->getUserGroupIds($user);
        if ($this->isDeveloperGroupMember($groups)) {
            return [
                'admin_nav' => true,
                'analysis_nav' => true,
                'archive_toggle' => true,
            ];
        }

        return [
            'admin_nav' => $this->isAllowed('admin_access', $groups),
            'analysis_nav' => $this->isAllowed('analysis_tab', $groups),
            'archive_toggle' => $this->isAllowed('view_archive_toggle', $groups),
        ];
    }

    public function canManageAdmin(IUser $user): bool {
        $groups = $this->getUserGroupIds($user);
        if ($this->isDeveloperGroupMember($groups)) return true;
        return $this->isAllowed('admin_access', $groups);
    }

    public function getBootstrap(): array {
        $access = $this->mapper->getAccessRules(self::ACCESS_RULE_KEYS);
        foreach (self::ACCESS_RULE_KEYS as $key) {
            $access[$key] = $this->withDeveloperGroup($access[$key] ?? []);
        }

        return [
            'holidays' => $this->mapper->getHolidays(),
            'access' => $access,
            'groups' => $this->getAllGroupIds(),
            'users' => $this->getUsers(),
        ];
    }

    public function saveHoliday(array $payload): array {
        $id = (int)($payload['id'] ?? 0);
        $name = trim((string)($payload['name'] ?? ''));
        $start = (string)($payload['start'] ?? '');
        $end = (string)($payload['end'] ?? '');
        $bg = (string)($payload['bg'] ?? '#95a5a6');
        $archive = (int)($payload['archive'] ?? 0);

        if ($name === '' || !$this->isIsoDate($start) || !$this->isIsoDate($end)) {
            return ['ok' => false, 'error' => 'Invalid holiday payload', 'status' => 400];
        }

        $this->mapper->upsertHoliday($id, $name, $start, $end, $bg, $archive);
        if ($archive === 0) {
            // Only seed rows that are due today or in the past.
            // Future holiday dates are inserted by the scheduled job when those dates arrive.
            $this->seedDueHolidayTimesheets();
        }

        return ['ok' => true];
    }

    public function seedDueHolidayTimesheets(?string $today = null): void {
        $todayDate = $today ?: date('Y-m-d');
        if (!$this->isIsoDate($todayDate)) {
            return;
        }

        $userIds = array_map(static function (array $u): string {
            return (string)($u['uid'] ?? '');
        }, $this->getUsers());
        $userIds = array_values(array_filter($userIds, static function (string $uid): bool {
            return $uid !== '';
        }));
        if (empty($userIds)) {
            return;
        }

        $holidays = $this->mapper->getActiveHolidays();
        if (empty($holidays)) {
            return;
        }

        foreach ($holidays as $holiday) {
            $start = (string)($holiday['holiday_start_date'] ?? '');
            $end = (string)($holiday['holiday_end_date'] ?? '');
            if (!$this->isIsoDate($start) || !$this->isIsoDate($end)) {
                continue;
            }
            if ($start > $todayDate) {
                continue;
            }
            $effectiveEnd = $end > $todayDate ? $todayDate : $end;
            $this->seedHolidayTimesheetsForRange($start, $effectiveEnd, $userIds);
        }
    }

    public function saveAccess(array $payload): array {
        $ruleKey = trim((string)($payload['rule_key'] ?? ''));
        $groups = $payload['allowed_groups'] ?? [];
        if (!in_array($ruleKey, self::ACCESS_RULE_KEYS, true) || !is_array($groups)) {
            return ['ok' => false, 'error' => 'Invalid access payload', 'status' => 400];
        }

        $cleanGroups = array_values(array_filter(array_map('strval', $groups), static function (string $g): bool {
            return $g !== '';
        }));
        $cleanGroups = $this->withDeveloperGroup($cleanGroups);
        $this->mapper->saveAccessRule($ruleKey, $cleanGroups);

        return ['ok' => true];
    }

    private function getAllGroupIds(): array {
        $groups = $this->groupManager->search('');
        $ids = [];
        foreach ($groups as $group) {
            if ($group instanceof IGroup) {
                $ids[] = $group->getGID();
            }
        }
        if (!$this->isDeveloperGroupMember($ids)) {
            $ids[] = self::DEVELOPER_GROUP;
        }
        sort($ids);
        return $ids;
    }

    private function getUserGroupIds(IUser $user): array {
        $groups = $this->groupManager->getUserGroups($user);
        $ids = [];
        foreach ($groups as $group) {
            if ($group instanceof IGroup) {
                $ids[] = $group->getGID();
            }
        }
        return $ids;
    }

    private function getUsers(): array {
        $users = [];
        $seen = [];

        $append = function (iterable $rows) use (&$users, &$seen): void {
            foreach ($rows as $u) {
                if (!$u instanceof IUser || !$this->isUserActive($u)) {
                    continue;
                }
                $uid = (string)$u->getUID();
                if ($uid === '' || isset($seen[$uid])) {
                    continue;
                }
                $seen[$uid] = true;
                $display = (string)$u->getDisplayName();
                $users[] = [
                    'uid' => $uid,
                    'display_name' => $display !== '' ? $display : $uid,
                ];
            }
        };

        if (method_exists($this->userManager, 'searchDisplayName')) {
            try {
                $result = $this->userManager->searchDisplayName('', 500, 0);
                if (is_iterable($result)) {
                    $append($result);
                }
            } catch (\Throwable $e) {
                try {
                    $result = $this->userManager->searchDisplayName('');
                    if (is_iterable($result)) {
                        $append($result);
                    }
                } catch (\Throwable $e2) {
                }
            }
        }

        if (method_exists($this->userManager, 'search')) {
            try {
                $result = $this->userManager->search('', 500, 0);
                if (is_iterable($result)) {
                    $append($result);
                }
            } catch (\Throwable $e) {
                try {
                    $result = $this->userManager->search('');
                    if (is_iterable($result)) {
                        $append($result);
                    }
                } catch (\Throwable $e2) {
                }
            }
        }

        if (empty($users) && method_exists($this->userManager, 'callForAllUsers')) {
            try {
                $this->userManager->callForAllUsers(function ($u) use (&$users, &$seen): void {
                    if (!$u instanceof IUser || !$this->isUserActive($u)) {
                        return;
                    }
                    $uid = (string)$u->getUID();
                    if ($uid === '' || isset($seen[$uid])) {
                        return;
                    }
                    $seen[$uid] = true;
                    $display = (string)$u->getDisplayName();
                    $users[] = [
                        'uid' => $uid,
                        'display_name' => $display !== '' ? $display : $uid,
                    ];
                });
            } catch (\Throwable $e) {
            }
        }

        if (empty($users) && method_exists($this->userManager, 'callForSeenUsers')) {
            try {
                $this->userManager->callForSeenUsers(function ($u) use (&$users, &$seen): void {
                    if (!$u instanceof IUser || !$this->isUserActive($u)) {
                        return;
                    }
                    $uid = (string)$u->getUID();
                    if ($uid === '' || isset($seen[$uid])) {
                        return;
                    }
                    $seen[$uid] = true;
                    $display = (string)$u->getDisplayName();
                    $users[] = [
                        'uid' => $uid,
                        'display_name' => $display !== '' ? $display : $uid,
                    ];
                });
            } catch (\Throwable $e) {
            }
        }

        usort($users, static function (array $a, array $b): int {
            return strcasecmp((string)$a['display_name'], (string)$b['display_name']);
        });

        return $users;
    }

    private function seedHolidayTimesheetsForRange(string $start, string $end, array $userIds): void {
        if (empty($userIds)) {
            return;
        }
        try {
            $cursor = new \DateTime($start);
            $last = new \DateTime($end);
        } catch (\Throwable $e) {
            return;
        }

        while ($cursor <= $last) {
            $date = $cursor->format('Y-m-d');
            foreach ($userIds as $uid) {
                if ($this->mapper->hasAutoHolidayTimesheet($uid, $date)) {
                    continue;
                }
                $this->mapper->insertAutoHolidayTimesheet($uid, $date);
            }
            $cursor->modify('+1 day');
        }
    }

    private function isAllowed(string $ruleKey, array $userGroups): bool {
        if ($this->isDeveloperGroupMember($userGroups)) return true;
        $allowed = $this->mapper->getRuleAllowedGroups($ruleKey);
        if (empty($allowed)) return false;
        return count(array_intersect($userGroups, $allowed)) > 0;
    }

    private function isDeveloperGroupMember(array $groupIds): bool {
        foreach ($groupIds as $gid) {
            if (strcasecmp((string)$gid, self::DEVELOPER_GROUP) === 0) {
                return true;
            }
        }
        return false;
    }

    private function withDeveloperGroup(array $groupIds): array {
        $out = array_values(array_filter(array_map('strval', $groupIds), static function (string $g): bool {
            return $g !== '';
        }));
        if (!$this->isDeveloperGroupMember($out)) {
            $out[] = self::DEVELOPER_GROUP;
        }
        return $out;
    }

    private function isUserActive(IUser $user): bool {
        try {
            return (bool)$user->isEnabled();
        } catch (\Throwable $e) {
            return true;
        }
    }

    private function isIsoDate(string $value): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return false;
        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        return $dt && $dt->format('Y-m-d') === $value;
    }

}
