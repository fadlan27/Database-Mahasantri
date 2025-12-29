<?php
class HijriDate {
    /**
     * Convert Gregorian date to Hijri dates (Umm al-Qura compatible approximation).
     * Since exact Umm al-Qura depends on sighting, pure algorithms are approximations.
     * However, the "Kuwaiti Algorithm" or standard tabular Islamic calendars are often used as "Eternal" versions.
     * 
     * For "Saudi" specific without external libs, we try IntlDateFormatter first (if available and reliable),
     * otherwise we use a robust calculation.
     */

    public static function convert($date_str) {
        $timestamp = strtotime($date_str);
        
        // SKIP IntlDateFormatter - not reliable across different servers/hosting
        // Always use pure PHP algorithm (Kuwaiti/Tabular Islamic Calendar) for consistency

        // FALLBACK: Tabular Islamic Calendar (Kuwaiti Algorithm)
        // This is "Eternal" and mathematical.
        $date = getdate($timestamp);
        $m = $date['mon'];
        $d = $date['mday'];
        $y = $date['year'];

        return self::gregorianToHijri($m, $d, $y);
    }

    // Pure PHP Implementation (Kuwaiti Algorithm / Tabular Islamic Calendar)
    // "Rumus Abadi" yang menggunakan siklus 30 tahun.
    // Dalam 30 tahun (10631 hari), terdapat 11 tahun kabisat (355 hari) dan 19 tahun basalah (354 hari).
    // Algoritma ini secara otomatis menangani variasi bulan 29/30 hari dan tahun kabisat.
    private static function gregorianToHijri($m, $d, $y) {
        // Konversi ke Julian Day Number (JD)
        if (($y > 1582) || (($y == 1582) && ($m > 10)) || (($y == 1582) && ($m == 10) && ($d > 14))) {
            $jd = self::intPart((1461 * ($y + 4800 + self::intPart(($m - 14) / 12))) / 4) +
                  self::intPart((367 * ($m - 2 - 12 * (self::intPart(($m - 14) / 12)))) / 12) -
                  self::intPart((3 * self::intPart(($y + 4900 + self::intPart(($m - 14) / 12)) / 100)) / 4) +
                  $d - 32075;
        } else {
            $jd = 367 * $y - self::intPart((7 * ($y + 5001 + self::intPart(($m - 9) / 7))) / 4) +
                  self::intPart((275 * $m) / 9) + $d + 1729777;
        }

        // Kalkulasi Hijriah dari Julian Day
        // 10631 adalah jumlah hari dalam siklus 30 tahun Hijriah
        $l = $jd - 1948440 + 10632;
        $n = self::intPart(($l - 1) / 10631); 
        $l = $l - 10631 * $n + 354;
        $j = (self::intPart((10985 - $l) / 5316)) * (self::intPart((50 * $l) / 17719)) +
             (self::intPart($l / 5670)) * (self::intPart((43 * $l) / 15238));
        $l = $l - (self::intPart((30 - $j) / 15)) * (self::intPart((17719 * $j) / 50)) -
             (self::intPart($j / 16)) * (self::intPart((15238 * $j) / 43)) + 29;
        
        $m = self::intPart((24 * $l) / 709);
        $d = $l - self::intPart((709 * $m) / 24);
        $y = 30 * $n + $j - 30;

        return [
            'day' => $d,
            'month' => $m,
            'year' => $y,
            'month_name' => self::getMonthName($m)
        ];
    }
    
    private static function intPart($float) {
        return ($float < -0.0000001) ? ceil($float - 0.0000001) : floor($float + 0.0000001);
    }

    public static function getMonthName($monthIndex) {
        $months = [
            1 => 'Muharram', 2 => 'Safar', 3 => 'Rabiul Awal', 4 => 'Rabiul Akhir',
            5 => 'Jumadil Awal', 6 => 'Jumadil Akhir', 7 => 'Rajab', 8 => 'Syaban',
            9 => 'Ramadhan', 10 => 'Syawal', 11 => 'Dzulqadah', 12 => 'Dzulhijjah'
        ];
        return isset($months[$monthIndex]) ? $months[$monthIndex] : '';
    }

    // Helper to format "YYYY" to "YYYY H" directly
    public static function getYear($date_str = 'now') {
        $date = self::convert($date_str);
        return $date['year'];
    }
}
?>
