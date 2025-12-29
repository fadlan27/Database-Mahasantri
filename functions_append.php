<?php
// c:/Users/1/Desktop/My Aplikasi/Database Mahasantri/functions.php
// Append this to the end or suitable location

function getAcademicHijriRange() {
    $month = date('n');
    $year = date('Y');
    if ($month >= 7) {
        $startYear = $year;
        $endYear = $year + 1;
    } else {
        $startYear = $year - 1;
        $endYear = $year;
    }
    
    // Check included library
    if (!class_exists('HijriDate')) {
        return "1446 - 1447 H"; // Fallback
    }

    // Start of TA: July 15 of Start Year
    $startH = HijriDate::getYear("$startYear-07-15");
    
    // End of TA: June 15 of End Year
    $endH = HijriDate::getYear("$endYear-06-15");
    
    if ($startH == $endH) {
         // Maybe spanning boundaries? Usually TA spans 1 hijri year or 2.
         // If same, just return one. But user likes range "1446 - 1447 H".
         // If strict same, return one. If user wants range implies crossing year.
         return $startH . " H";
    }
    
    // Ensure ascending
    if ($startH > $endH) {
        $temp = $startH; $startH = $endH; $endH = $temp;
    }

    return "$startH - $endH H";
}
