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
        
        // 1. Add Payroll Markers (only in Active view)
        if ($archive === 0) {
            $settings = $this->mapper->getAdminSettings();
            $events = array_merge($events, $this->generatePayrollMarkers($settings, $start, $end));
        }

        // 2. Fetch Timesheets
        $results = $this->mapper->findRawEntries($userId, $start, $end, $archive);
        if (empty($results)) return $events;

        $today = date('Y-m-d');

        // 3. Process Rows
        foreach ($results as $row) {
            $tid = $row['timesheet_id'];
            $date = $row['timesheet_date'];
            $totalHours = (float)$row['time_total'];
            
            $hasTimeIn = !empty($row['time_in']);
            $hasTimeOut = !empty($row['time_out']);
            $isPto = (int)$row['is_pto'] === 1;
            $isPerDiem = (int)$row['travel_per_diem'] === 1;
            
            // --- ARCHIVED VIEW ---
            if ($archive === 1) {
                $isClosed = $hasTimeOut;
                $title = $isClosed ? $totalHours . 'h (Archived)' : 'Incomplete (Archived)';
                $events[] = [
                    'id' => $tid . '-arch', 
                    'title' => $title, 
                    'start' => $date, 
                    'color' => '#777777', 
                    'extendedProps' => ['timesheet_id' => $tid, 'archive' => 1]
                ];
                continue; 
            }

            // --- ACTIVE VIEW ---

            // A. Per Diem Tag (Independent Event)
            // Shows even if they also clocked in/out
            if ($isPerDiem) {
                $events[] = [
                    'id' => $tid . '-pd', 
                    'title' => 'Per Diem', 
                    'start' => $date, 
                    'color' => '#17a2b8', // Cyan
                    'extendedProps' => ['timesheet_id' => $tid] // Link to real ID
                ];
            }

            // B. Main Status Tag (Vacation OR Work)
            if ($isPto) {
                // Vacation Tag (Priority over work hours)
                $events[] = [
                    'id' => $tid . '-pto',
                    'title' => 'Vacation', 
                    'start' => $date, 
                    'color' => '#9b59b6', // Purple
                    'extendedProps' => ['timesheet_id' => $tid]
                ];
            } elseif ($hasTimeIn) {
                // Work Tag logic
                if ($hasTimeOut) {
                    // Completed Day
                    $events[] = [
                        'id' => $tid . '-work',
                        'title' => round($totalHours, 2) . ' hrs',
                        'start' => $date,
                        'color' => '#28a745', // Green
                        'extendedProps' => ['timesheet_id' => $tid]
                    ];
                } else {
                    // Incomplete Day
                    if ($date === $today) {
                        // Currently working
                        $events[] = [
                            'id' => $tid . '-active',
                            'title' => 'Signed In',
                            'start' => $date,
                            'color' => '#ffc107', // Yellow/Orange
                            'textColor' => '#000000', // Black text
                            'extendedProps' => ['timesheet_id' => $tid]
                        ];
                    } else {
                        // Forgot to clock out (Past Date)
                        $events[] = [
                            'id' => $tid . '-missing',
                            'title' => 'Did Not Sign Out',
                            'start' => $date,
                            'color' => '#dc3545', // Red
                            'extendedProps' => ['timesheet_id' => $tid]
                        ];
                    }
                }
            }
        }
        return $events;
    }

    private function generatePayrollMarkers(array $settings, string $start, string $end): array {
        $markers = [];
        $startDateStr = $settings['pay_start_date'] ?? '2024-01-01';
        
        try {
            $payStart = new \DateTime($startDateStr);
            $viewStart = new \DateTime($start); 
            $viewEnd = new \DateTime($end);
        } catch (\Exception $e) {
            return [];
        }

        $freq = (int)($settings['pay_frequency'] ?? 14);
        if ($freq <= 0) $freq = 14; 
        
        $hexColor = $settings['pay_color'] ?? '#34495e';
        $rgba = $this->hex2rgba($hexColor, 0.35);

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