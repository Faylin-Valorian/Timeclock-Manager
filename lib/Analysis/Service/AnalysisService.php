<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Analysis\Service;

use OCA\TimeclockManager\Analysis\Db\AnalysisMapper;

class AnalysisService {
    // Hard-coded overtime anchor for this release.
    // Update this date in one place when payroll anchor changes.
    private const OVERTIME_ANCHOR_DATE = '2026-01-01';

    private $mapper;

    public function __construct(AnalysisMapper $mapper) {
        $this->mapper = $mapper;
    }

    public function getSummary(string $userId, string $start, string $end): array {
        $rows = $this->mapper->findForRange($userId, $start, $end);
        $overtimeByTimesheet = $this->buildOvertimeHoursByTimesheet($rows);

        $summary = [
            'total_hours' => 0.0,
            'pto_hours' => 0.0,
            'overtime_hours' => 0.0,
            'per_diem_requests' => 0,
            'road_scanning_days' => 0,
            'overnight_days' => 0,
            'total_miles' => 0,
            'extra_expenses' => 0.0,
        ];

        foreach ($rows as $row) {
            $hours = (float)($row['time_total'] ?? 0);
            $isPto = (int)($row['is_pto'] ?? 0) === 1;

            $summary['total_hours'] += $hours;
            if ($isPto) {
                $summary['pto_hours'] += $hours;
            }
            $tid = (int)($row['timesheet_id'] ?? 0);
            $summary['overtime_hours'] += (float)($overtimeByTimesheet[$tid] ?? 0.0);

            if ((int)($row['travel_per_diem'] ?? 0) === 1) {
                $summary['per_diem_requests']++;
            }
            if ((int)($row['travel_road_scanning'] ?? 0) === 1) {
                $summary['road_scanning_days']++;
            }
            if ((int)($row['travel_overnight'] ?? 0) === 1) {
                $summary['overnight_days']++;
            }

            $summary['total_miles'] += max(0, (int)($row['travel_miles'] ?? 0));
            $summary['extra_expenses'] += max(0.0, (float)($row['travel_extra_expenses'] ?? 0));
        }

        $summary['total_hours'] = round($summary['total_hours'], 2);
        $summary['pto_hours'] = round($summary['pto_hours'], 2);
        $summary['overtime_hours'] = round($summary['overtime_hours'], 2);
        $summary['extra_expenses'] = round($summary['extra_expenses'], 2);

        return $summary;
    }

    public function getDetail(string $metric, string $userId, string $start, string $end): array {
        $rows = $this->mapper->findRowsForDetail($userId, $start, $end);
        $metric = trim($metric);

        if ($metric === 'total_hours') {
            return [
                'type' => 'jobs',
                'items' => $this->buildJobHours($rows, false),
            ];
        }

        if ($metric === 'overtime_hours') {
            $overtimeByTimesheet = $this->buildOvertimeHoursByTimesheet($rows);
            return [
                'type' => 'jobs',
                'items' => $this->buildJobHours($rows, true, $overtimeByTimesheet),
            ];
        }

        if ($metric === 'pto_hours') {
            return ['type' => 'date_hours', 'items' => $this->collectPtoDateHours($rows)];
        }

        if ($metric === 'per_diem_requests') {
            return ['type' => 'dates', 'items' => $this->collectDatesForToggle($rows, 'travel_per_diem')];
        }
        if ($metric === 'road_scanning_days') {
            return ['type' => 'dates', 'items' => $this->collectDatesForToggle($rows, 'travel_road_scanning')];
        }
        if ($metric === 'overnight_days') {
            return ['type' => 'dates', 'items' => $this->collectDatesForToggle($rows, 'travel_overnight')];
        }

        return ['type' => 'none', 'items' => []];
    }

    private function buildJobHours(array $rows, bool $overtimeOnly, array $overtimeByTimesheet = []): array {
        $timesheetIds = array_values(array_unique(array_map(static function (array $r): int {
            return (int)($r['timesheet_id'] ?? 0);
        }, $rows)));
        $timesheetIds = array_values(array_filter($timesheetIds, static function (int $id): bool {
            return $id > 0;
        }));

        $activitiesByTid = $this->mapper->getActivitiesForTimesheetIds($timesheetIds);
        $totals = [];

        foreach ($rows as $row) {
            $tid = (int)($row['timesheet_id'] ?? 0);
            $baseHours = (float)($row['time_total'] ?? 0);
            $hours = $overtimeOnly
                ? max(0.0, (float)($overtimeByTimesheet[$tid] ?? 0.0))
                : max(0.0, $baseHours);
            if ($hours <= 0.0) continue;

            $acts = $activitiesByTid[$tid] ?? [];
            if (empty($acts)) {
                $totals['Unspecified'] = ($totals['Unspecified'] ?? 0.0) + $hours;
                continue;
            }

            $allocated = 0.0;
            foreach ($acts as $act) {
                $pct = (int)($act['activity_percent'] ?? 0);
                $pct = max(0, min(100, $pct));
                if ($pct <= 0) continue;

                $labelRaw = trim((string)($act['activity_description'] ?? ''));
                $label = $labelRaw !== '' ? $labelRaw : 'Unspecified';
                $part = $hours * ($pct / 100);
                $allocated += $part;
                $totals[$label] = ($totals[$label] ?? 0.0) + $part;
            }

            $remainder = max(0.0, $hours - $allocated);
            if ($remainder > 0.0001) {
                $totals['Unspecified'] = ($totals['Unspecified'] ?? 0.0) + $remainder;
            }
        }

        arsort($totals);
        $items = [];
        foreach ($totals as $label => $value) {
            $items[] = [
                'label' => (string)$label,
                'hours' => round((float)$value, 2),
            ];
        }
        return $items;
    }

    private function buildOvertimeHoursByTimesheet(array $rows): array {
        if (empty($rows)) return [];

        $anchorTs = strtotime(self::OVERTIME_ANCHOR_DATE . ' 00:00:00');
        if ($anchorTs === false) return [];

        $periodTotals = [];
        $periodRows = [];

        foreach ($rows as $row) {
            $tid = (int)($row['timesheet_id'] ?? 0);
            $hours = max(0.0, (float)($row['time_total'] ?? 0.0));
            $date = (string)($row['timesheet_date'] ?? '');
            $isPto = (int)($row['is_pto'] ?? 0) === 1;
            if ($tid <= 0 || $hours <= 0.0 || $date === '' || $isPto) continue;

            $periodKey = $this->getBiweeklyPeriodKey($date, $anchorTs);
            if ($periodKey === null) continue;

            $periodTotals[$periodKey] = ($periodTotals[$periodKey] ?? 0.0) + $hours;
            $periodRows[$periodKey][] = [
                'timesheet_id' => $tid,
                'hours' => $hours,
            ];
        }

        $overtimeByTid = [];
        foreach ($periodRows as $key => $entries) {
            $periodTotal = (float)($periodTotals[$key] ?? 0.0);
            if ($periodTotal <= 40.0) continue;

            $periodOvertime = $periodTotal - 40.0;
            $factor = min(1.0, $periodOvertime / $periodTotal);
            foreach ($entries as $entry) {
                $tid = (int)$entry['timesheet_id'];
                $hours = (float)$entry['hours'];
                $overtimeByTid[$tid] = ($overtimeByTid[$tid] ?? 0.0) + ($hours * $factor);
            }
        }

        return $overtimeByTid;
    }

    private function getBiweeklyPeriodKey(string $date, int $anchorTs): ?string {
        $ts = strtotime($date . ' 00:00:00');
        if ($ts === false) return null;

        $diffDays = (int)floor(($ts - $anchorTs) / 86400);
        if ($diffDays >= 0) {
            $period = intdiv($diffDays, 14);
        } else {
            $period = -intdiv(abs($diffDays) + 13, 14);
        }
        return (string)$period;
    }

    private function collectDatesForToggle(array $rows, string $field): array {
        $dates = [];
        foreach ($rows as $row) {
            if ((int)($row[$field] ?? 0) === 1) {
                $date = (string)($row['timesheet_date'] ?? '');
                if ($date !== '') $dates[$date] = true;
            }
        }
        $list = array_keys($dates);
        sort($list);
        return $list;
    }

    private function collectPtoDateHours(array $rows): array {
        $totalsByDate = [];
        foreach ($rows as $row) {
            if ((int)($row['is_pto'] ?? 0) !== 1) continue;
            $date = (string)($row['timesheet_date'] ?? '');
            if ($date === '') continue;
            $hours = max(0.0, (float)($row['time_total'] ?? 0.0));
            $totalsByDate[$date] = ($totalsByDate[$date] ?? 0.0) + $hours;
        }

        ksort($totalsByDate);
        $items = [];
        foreach ($totalsByDate as $date => $hours) {
            $items[] = [
                'date' => $date,
                'hours' => round((float)$hours, 2),
            ];
        }
        return $items;
    }
}
