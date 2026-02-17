<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Admin\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class AdminMapper {

    private $db;
    private const AUTO_HOLIDAY_IS_PTO = 2;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    public function getHolidays(): array {
        $qb = $this->db->getQueryBuilder();
        return $qb->select('*')
            ->from('tm_holidays')
            ->orderBy('holiday_start_date', 'DESC')
            ->executeQuery()
            ->fetchAll();
    }

    public function getActiveHolidays(): array {
        $qb = $this->db->getQueryBuilder();
        return $qb->select('*')
            ->from('tm_holidays')
            ->where($qb->expr()->eq('holiday_archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
            ->orderBy('holiday_start_date', 'ASC')
            ->executeQuery()
            ->fetchAll();
    }

    public function upsertHoliday(int $id, string $name, string $start, string $end, string $bg, int $archive): void {
        if ($id > 0) {
            $qb = $this->db->getQueryBuilder();
            $qb->update('tm_holidays')
                ->set('holiday_name', $qb->createNamedParameter($name))
                ->set('holiday_start_date', $qb->createNamedParameter($start))
                ->set('holiday_end_date', $qb->createNamedParameter($end))
                ->set('holiday_bg', $qb->createNamedParameter($bg))
                ->set('holiday_archive', $qb->createNamedParameter($archive, IQueryBuilder::PARAM_INT))
                ->where($qb->expr()->eq('holiday_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
                ->executeStatement();
            return;
        }

        $qb = $this->db->getQueryBuilder();
        $qb->insert('tm_holidays')
            ->values([
                'holiday_name' => $qb->createNamedParameter($name),
                'holiday_start_date' => $qb->createNamedParameter($start),
                'holiday_end_date' => $qb->createNamedParameter($end),
                'holiday_bg' => $qb->createNamedParameter($bg),
                'holiday_archive' => $qb->createNamedParameter($archive, IQueryBuilder::PARAM_INT),
            ])
            ->executeStatement();
    }

    public function getAccessRules(array $ruleKeys): array {
        $qb = $this->db->getQueryBuilder();
        $rows = $qb->select('rule_key', 'allowed_groups')
            ->from('tm_access_rules')
            ->where($qb->expr()->in('rule_key', $qb->createNamedParameter($ruleKeys, IQueryBuilder::PARAM_STR_ARRAY)))
            ->executeQuery()
            ->fetchAll();

        $map = [];
        foreach ($ruleKeys as $key) {
            $map[$key] = [];
        }
        foreach ($rows as $row) {
            $decoded = json_decode((string)($row['allowed_groups'] ?? '[]'), true);
            $map[(string)$row['rule_key']] = is_array($decoded) ? $decoded : [];
        }
        return $map;
    }

    public function getRuleAllowedGroups(string $ruleKey): array {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('allowed_groups')
            ->from('tm_access_rules')
            ->where($qb->expr()->eq('rule_key', $qb->createNamedParameter($ruleKey)))
            ->executeQuery()
            ->fetch();

        if (!$row) return [];
        $decoded = json_decode((string)($row['allowed_groups'] ?? '[]'), true);
        return is_array($decoded) ? $decoded : [];
    }

    public function saveAccessRule(string $ruleKey, array $allowedGroups): void {
        $json = json_encode($allowedGroups);

        $qb = $this->db->getQueryBuilder();
        $existing = $qb->select('id')
            ->from('tm_access_rules')
            ->where($qb->expr()->eq('rule_key', $qb->createNamedParameter($ruleKey)))
            ->executeQuery()
            ->fetch();

        if ($existing && isset($existing['id'])) {
            $up = $this->db->getQueryBuilder();
            $up->update('tm_access_rules')
                ->set('allowed_groups', $up->createNamedParameter($json))
                ->where($up->expr()->eq('id', $up->createNamedParameter((int)$existing['id'], IQueryBuilder::PARAM_INT)))
                ->executeStatement();
            return;
        }

        $ins = $this->db->getQueryBuilder();
        $ins->insert('tm_access_rules')
            ->values([
                'rule_key' => $ins->createNamedParameter($ruleKey),
                'allowed_groups' => $ins->createNamedParameter($json),
            ])
            ->executeStatement();
    }

    public function hasAutoHolidayTimesheet(string $userId, string $date): bool {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('timesheet_id')
            ->from('tm_timesheets')
            ->where($qb->expr()->eq('userid', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('timesheet_date', $qb->createNamedParameter($date)))
            ->andWhere($qb->expr()->eq('archive', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('is_pto', $qb->createNamedParameter(self::AUTO_HOLIDAY_IS_PTO, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetch();

        return !!$row;
    }

    public function insertAutoHolidayTimesheet(string $userId, string $date): void {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('tm_timesheets')
            ->values([
                'userid' => $qb->createNamedParameter($userId),
                'timesheet_date' => $qb->createNamedParameter($date),
                'time_in' => $qb->createNamedParameter('08:00:00'),
                'time_out' => $qb->createNamedParameter('17:00:00'),
                'time_break' => $qb->createNamedParameter(60, IQueryBuilder::PARAM_INT),
                'time_total' => $qb->createNamedParameter(8.0),
                'is_pto' => $qb->createNamedParameter(self::AUTO_HOLIDAY_IS_PTO, IQueryBuilder::PARAM_INT),
                'travel_road_scanning' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'travel_first_last_day' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'travel_overnight' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'travel_per_diem' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'travel_state' => $qb->createNamedParameter(''),
                'travel_county' => $qb->createNamedParameter(''),
                'travel_miles' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'travel_extra_expenses' => $qb->createNamedParameter(0.0),
                'additional_comments' => $qb->createNamedParameter(''),
                'archive' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
            ])
            ->executeStatement();
    }
}
