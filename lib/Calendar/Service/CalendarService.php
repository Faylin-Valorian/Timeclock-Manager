<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Calendar\Service;

use OCA\TimeclockManager\Calendar\Db\CalendarMapper;

class CalendarService {
    private $mapper;

    public function __construct(CalendarMapper $mapper) {
        $this->mapper = $mapper;
    }

    public function getHolidays($start, $end): array {
        return $this->mapper->getHolidaysForCalendar($start, $end);
    }

    public function getCalendarEvents(string $userId, string $start, string $end, int $archive = 0): array {
        $events = [];
        
        // 1. Add Payroll Markers (if active view)
        if ($archive === 0) {
            $settings = $this->mapper->getAdminSettings();
            $events = array_merge($events, $this->generatePayrollMarkers($settings, $start, $end));
        }

        // 2. Fetch Timesheets
        $results = $this->mapper->findRawEntries($userId, $start, $end, $archive);
        if (empty($results)) return $events;

        // 3. Helper Data
        $ids = array_column($results, 'timesheet_id');
        $activities = $this->mapper->getActivitiesGrouped($ids);
        $ptoJobMap = $this->mapper->getPtoJobMap();
        $today = date('Y-m-d');

        // 4. Process Rows
        foreach ($results as $row) {
            $tid = $row['timesheet_id'];
            $totalHours = (float)$row['time_total'];
            $date = $row['timesheet_date'];
            $isClosed = !empty($row['time_out']);
            
            // Archived Styling
            if ($archive === 1) {
                $title = $isClosed ? $totalHours . 'h (Archived)' : 'Incomplete (Archived)';
                $events[] = [
                    'id' => $tid, 'title' => $title, 'start' => $date, 'color' => '#777777', 
                    'extendedProps' => ['isClosed' => true, 'archive' => 1]
                ];
                continue; 
            }

            // Per Diem
            if (empty($row['time_in']) && $row['travel_per_diem'] == 1) {
                $events[] = ['id' => $tid, 'title' => 'Per Diem', 'start' => $date, 'color' => '#17a2b8', 'extendedProps' => ['isClosed' => true]];
                continue;
            }

            // Calculate Reg vs PTO
            $regHours = 0.0; 
            $ptoHours = 0.0;
            $acts = $activities[$tid] ?? [];

            if (empty($acts)) { 
                $regHours = $totalHours; 
            } else {
                foreach ($acts as $act) {
                    $jobName = $act['activity_description'];
                    $percent = (float)$act['activity_percent'];
                    $hours = $totalHours * ($percent / 100);
                    if (isset($ptoJobMap[$jobName]) && $ptoJobMap[$jobName] === 1) { 
                        $ptoHours += $hours; 
                    } else { 
                        $regHours += $hours; 
                    }
                }
            }

            // Work Event
            if ($regHours > 0.01 || !$isClosed || ($totalHours < 0.01 && $ptoHours < 0.01)) {
                $color = $isClosed ? '#28a745' : '#ffc107'; 
                $title = $isClosed ? round($regHours, 2) . ' hrs' : 'Active';
                
                if ($date < $today && !$isClosed) { 
                    $color = '#dc3545'; 
                    $title = 'Missing Out'; 
                }
                
                $events[] = ['id' => $tid, 'title' => $title, 'start' => $date, 'color' => $color, 'extendedProps' => ['isClosed' => $isClosed]];
            }

            // PTO Event
            if ($ptoHours > 0.01) {
                $events[] = ['id' => $tid, 'title' => 'Vacation ' . round($ptoHours, 2) . ' hrs', 'start' => $date, 'color' => '#9b59b6', 'extendedProps' => ['isClosed' => true]];
            }
        }
        return $events;
    }

    private function generatePayrollMarkers(array $settings, string $start, string $end): array {
        $markers = [];
        $startDateStr = $settings['pay_start_date'] ?? '2024-01-01';
        $payStart = new \DateTime($startDateStr);
        $freq = (int)($settings['pay_frequency'] ?? 14);
        if ($freq <= 0) $freq = 14; 
        
        $hexColor = $settings['pay_color'] ?? '#34495e';
        $rgba = $this->hex2rgba($hexColor, 0.35);

        $viewStart = new \DateTime($start); 
        $viewEnd = new \DateTime($end);

        $interval = $payStart->diff($viewStart); 
        $daysDiff = (int)$interval->format('%r%a');

        if ($daysDiff >= 0) { 
            $remainder = $daysDiff % $freq; 
            $daysToAdd = ($remainder === 0) ? 0 : ($freq - $remainder); 
            $nextPay = clone $viewStart; 
            $nextPay->modify("+$daysToAdd days"); 
        } else { 
            $nextPay = clone $payStart; 
            while ($nextPay > $viewStart) $nextPay->modify("-$freq days");
            while ($nextPay < $viewStart) $nextPay->modify("+$freq days");
        }

        $safeGuard = 0;
        while ($nextPay <= $viewEnd && $safeGuard < 50) {
            $markers[] = [
                'id' => 'paid-' . $nextPay->format('Ymd'), 
                'title' => 'Payroll', 
                'start' => $nextPay->format('Y-m-d'), 
                'display' => 'background',
                'backgroundColor' => $rgba, 
                'extendedProps' => ['isVisual' => true]
            ];
            $nextPay->modify("+$freq days");
            $safeGuard++;
        }
        return $markers;
    }

    private function hex2rgba($color, $opacity = false) {
        $default = 'rgb(0,0,0)';
        if(empty($color)) return $default; 
        if($color[0] == '#' ) { $color = substr( $color, 1 ); }
        if(strlen($color) == 6) { $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] ); }
        elseif( strlen( $color ) == 3 ) { $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] ); }
        else { return $default; }
        $rgb =  array_map('hexdec', $hex);
        return $opacity ? 'rgba('.implode(",",$rgb).','.$opacity.')' : 'rgb('.implode(",",$rgb).')';
    }
}