<?php
require_once dirname(__DIR__) . '/includes/HijriDate.php';

class AgendaLogic {
    public static function processEvents($pdo, $start, $end) {
        $events = [];

        // 1. Fetch Non-Recurring Events in Range
        // Use Overlap Logic: Start <= EndRange AND End >= StartRange
        $sql = "SELECT a.*, k.nama_kategori, k.warna_bg, k.warna_teks, k.icon_class 
                FROM agenda_sekolah a 
                JOIN agenda_kategori k ON a.kategori_id = k.id 
                WHERE a.is_recurring = 0 
                AND a.tgl_mulai <= :end_range 
                AND a.tgl_selesai >= :start_range";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['start_range' => $start, 'end_range' => $end]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $events[] = self::formatEvent($row);
        }

        // 2. Fetch Recurring Events (All)
        $sqlRec = "SELECT a.*, k.nama_kategori, k.warna_bg, k.warna_teks, k.icon_class 
                   FROM agenda_sekolah a 
                   JOIN agenda_kategori k ON a.kategori_id = k.id 
                   WHERE a.is_recurring = 1";
        $stmtRec = $pdo->query($sqlRec);
        $recurringRows = $stmtRec->fetchAll();

        // Process Recurrence
        foreach ($recurringRows as $row) {
            if ($row['tipe_kalender'] === 'masehi') {
                $events = array_merge($events, self::processMasehiRecurrence($row, $start, $end));
            } else if ($row['tipe_kalender'] === 'hijriyah') {
                $events = array_merge($events, self::processHijriRecurrence($row, $start, $end));
            }
        }

        // Sort by start date
        usort($events, function($a, $b) {
            return strtotime($a['start']) - strtotime($b['start']);
        });

        return $events;
    }

    private static function formatEvent($row, $overrideStart = null, $overrideEnd = null) {
        return [
            'id' => $row['id'],
            'title' => $row['judul'],
            'description' => $row['deskripsi'],
            'start' => $overrideStart ? $overrideStart : $row['tgl_mulai'],
            'end' => $overrideEnd ? $overrideEnd : $row['tgl_selesai'],
            'backgroundColor' => $row['warna_bg'],
            'borderColor' => $row['warna_bg'],
            'textColor' => $row['warna_teks'],
            'allDay' => $row['is_full_day'] == 1,
            'extendedProps' => [
                'kategori' => $row['nama_kategori'],
                'icon' => $row['icon_class'],
                'tipe_kalender' => $row['tipe_kalender'],
                'is_recurring' => $row['is_recurring'] == 1
            ]
        ];
    }

    private static function processMasehiRecurrence($row, $viewStart, $viewEnd) {
        $generated = [];
        $startYear = date('Y', strtotime($viewStart));
        $endYear = date('Y', strtotime($viewEnd));

        // Original Day/Month
        $origStart = strtotime($row['tgl_mulai']);
        $origEnd = strtotime($row['tgl_selesai']);
        $m = date('m', $origStart);
        $d = date('d', $origStart);
        
        // Duration in seconds
        $duration = $origEnd - $origStart;

        // Iterate years in view
        for ($y = $startYear; $y <= $endYear; $y++) {
            $newStartDateStr = "$y-$m-$d " . date('H:i:s', $origStart);
            $newStartTime = strtotime($newStartDateStr);
            $newEndTime = $newStartTime + $duration;
            $newEndDateStr = date('Y-m-d H:i:s', $newEndTime);

            // Check if overlaps with view
            if ($newEndDateStr >= $viewStart && $newStartDateStr <= $viewEnd) {
                // Ensure date is valid (e.g. Feb 29 on non-leap year)
                if (checkdate($m, $d, $y)) {
                    $generated[] = self::formatEvent($row, $newStartDateStr, $newEndDateStr);
                }
            }
        }
        return $generated;
    }

    private static function processHijriRecurrence($row, $viewStart, $viewEnd) {
        $generated = [];
        
        $targetHijri = HijriDate::convert($row['tgl_mulai']); 
        $tM = $targetHijri['month'];
        $tD = $targetHijri['day'];

        $current = strtotime($viewStart);
        $endTs = strtotime($viewEnd);

        while ($current <= $endTs) {
            $dateStr = date('Y-m-d', $current);
            $hDate = HijriDate::convert($dateStr);

            if ($hDate['month'] == $tM && $hDate['day'] == $tD) {
                // FOUND MATCH
                $origStart = strtotime($row['tgl_mulai']);
                $origEnd = strtotime($row['tgl_selesai']);
                $duration = $origEnd - $origStart;
                
                $newStartStr = date('Y-m-d H:i:s', $current + (date('H', $origStart)*3600) + (date('i', $origStart)*60));
                $newEndStr = date('Y-m-d H:i:s', strtotime($newStartStr) + $duration);

                $generated[] = self::formatEvent($row, $newStartStr, $newEndStr);
            }
             
            $current = strtotime('+1 day', $current);
        }

        return $generated;
    }

    public static function getNextEvents($pdo, $limit = 5) {
        // Look ahead 60 days to capture upcoming events (including recurring)
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59', strtotime('+60 days'));
        
        $events = self::processEvents($pdo, $start, $end);
        
        // Filter out past events (processEvents might include an event that started earlier in the day but effectively "current" is fine, but strictly > now for "Up next")
        $now = time();
        $upcoming = array_filter($events, function($e) use ($now) {
            return strtotime($e['end']) >= $now;
        });

        // Slice
        return array_slice($upcoming, 0, $limit);
    }
}
?>
