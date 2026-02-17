<?php
declare(strict_types=1);

namespace OCA\TimeclockManager\Calendar\Service;

use OCA\TimeclockManager\Calendar\Db\CalendarMapper;

class CalendarService {
    private $mapper;
    private const AUTO_HOLIDAY_IS_PTO = 2;

    public function __construct(CalendarMapper $mapper) {
        $this->mapper = $mapper;
    }

    public function getHolidays($start, $end): array {
        return $this->mapper->getHolidaysForCalendar($start, $end);
    }

    public function getCalendarEvents(string $userId, string $start, string $end, int $archive = 0): array {
        $events = [];

        if ($archive === 0) {
            $this->ensureTodayHolidayTimesheet($userId);
        }

        $holidayColorByDate = $this->buildHolidayColorByDateMap($this->mapper->getHolidaysForCalendar($start, $end));

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
            $isAutoHoliday = (int)$row['is_pto'] === self::AUTO_HOLIDAY_IS_PTO;
            $isPerDiem = (int)$row['travel_per_diem'] === 1;
            
            // --- ARCHIVED VIEW ---
            if ($archive === 1) {
                if ($isAutoHoliday) {
                    $events[] = [
                        'id' => $tid . '-holiday-arch',
                        'title' => 'Holiday',
                        'start' => $date,
                        'color' => '#777777',
                        'extendedProps' => ['timesheet_id' => $tid, 'archive' => 1]
                    ];
                    continue;
                }

                // Keep archived colors gray, but preserve original tab titles.
                if ($isPerDiem) {
                    $events[] = [
                        'id' => $tid . '-pd-arch',
                        'title' => 'Per Diem',
                        'start' => $date,
                        'color' => '#777777',
                        'extendedProps' => ['timesheet_id' => $tid, 'archive' => 1]
                    ];
                }

                if ($isPto) {
                    $events[] = [
                        'id' => $tid . '-pto-arch',
                        'title' => 'Vacation',
                        'start' => $date,
                        'color' => '#777777',
                        'extendedProps' => ['timesheet_id' => $tid, 'archive' => 1]
                    ];
                } elseif ($hasTimeIn) {
                    if ($hasTimeOut) {
                        $events[] = [
                            'id' => $tid . '-work-arch',
                            'title' => round($totalHours, 2) . ' hrs',
                            'start' => $date,
                            'color' => '#777777',
                            'extendedProps' => ['timesheet_id' => $tid, 'archive' => 1]
                        ];
                    } else {
                        $events[] = [
                            'id' => $tid . '-missing-arch',
                            'title' => ($date === $today) ? 'Signed In' : 'Did Not Sign Out',
                            'start' => $date,
                            'color' => '#777777',
                            'extendedProps' => ['timesheet_id' => $tid, 'archive' => 1]
                        ];
                    }
                } elseif (!$isPerDiem) {
                    $events[] = [
                        'id' => $tid . '-no-time-in-arch',
                        'title' => 'No Sign In Time Entered',
                        'start' => $date,
                        'color' => '#777777',
                        'extendedProps' => ['timesheet_id' => $tid, 'archive' => 1]
                    ];
                }
                continue;
            }

            // --- ACTIVE VIEW ---
            if ($isAutoHoliday) {
                $events[] = [
                    'id' => $tid . '-holiday',
                    'title' => 'Holiday',
                    'start' => $date,
                    'color' => (string)($holidayColorByDate[$date] ?? '#e67e22'),
                    'extendedProps' => ['timesheet_id' => $tid]
                ];
                continue;
            }

            // A. Per Diem Tag (Independent Event)
            // Shows even if they also clocked in/out
            if ($isPerDiem) {
                $events[] = [
                    'id' => $tid . '-pd', 
                    'title' => 'Per Diem', 
                    'start' => $date, 
                    'color' => '#17a2b8', // Cyan
                    'extendedProps' => [
                        'timesheet_id' => $tid,
                        'per_diem_no_time' => (!$hasTimeIn && !$hasTimeOut) ? 1 : 0
                    ] // Link to real ID
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
                            'color' => 'var(--color-primary-element)',
                            'textColor' => 'var(--color-primary-text)',
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
            } elseif (!$isPerDiem) {
                // Non-archived records with no Time In should still be visible.
                // Exception: when Per Diem is toggled, only the Per Diem tag is shown.
                $events[] = [
                    'id' => $tid . '-no-time-in',
                    'title' => 'No Sign In Time Entered',
                    'start' => $date,
                    'color' => 'var(--color-warning, #b7791f)',
                    'textColor' => 'var(--color-main-text)',
                    'extendedProps' => [
                        'timesheet_id' => $tid,
                        'no_sign_in_time' => 1
                    ]
                ];
            }
        }
        return $events;
    }

    private function ensureTodayHolidayTimesheet(string $userId): void {
        if ($userId === '') return;

        $today = date('Y-m-d');
        $holiday = $this->mapper->getHolidayForDate($today);
        if (!$holiday) return;

        if ($this->mapper->hasAutoHolidayTimesheetForDate($userId, $today)) {
            return;
        }

        $this->mapper->insertAutoHolidayTimesheet($userId, $today);
    }

    private function buildHolidayColorByDateMap(array $holidays): array {
        $map = [];
        foreach ($holidays as $holiday) {
            $start = (string)($holiday['holiday_start_date'] ?? '');
            $end = (string)($holiday['holiday_end_date'] ?? '');
            if ($start === '' || $end === '') continue;

            try {
                $cursor = new \DateTime($start);
                $last = new \DateTime($end);
            } catch (\Exception $e) {
                continue;
            }

            while ($cursor <= $last) {
                $dateKey = $cursor->format('Y-m-d');
                if (!isset($map[$dateKey])) {
                    $map[$dateKey] = (string)($holiday['holiday_bg'] ?? '#e67e22');
                }
                $cursor->modify('+1 day');
            }
        }
        return $map;
    }

}
