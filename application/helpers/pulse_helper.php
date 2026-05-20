<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// ----------------------------------------------------------
// Format milliseconds into human readable time
// Examples:
//   85ms     → "85ms"
//   1674ms   → "1.6s"
//   65400ms  → "1m 5s"
// ----------------------------------------------------------
if ( ! function_exists('format_ms')) {
    function format_ms($ms)
    {
        $ms = (float) $ms;

        // Less than 1 second — show ms
        if ($ms < 1000) {
            return round($ms, 1) . 'ms';
        }

        // Less than 60 seconds — show seconds
        if ($ms < 60000) {
            return round($ms / 1000, 1) . 's';
        }

        // 60 seconds or more — show minutes and seconds
        $seconds = floor($ms / 1000);
        $minutes = floor($seconds / 60);
        $remaining_seconds = $seconds % 60;

        return $minutes . 'm ' . $remaining_seconds . 's';
    }
}

// ----------------------------------------------------------
// Get color class based on milliseconds
// ----------------------------------------------------------
if ( ! function_exists('ms_color')) {
    function ms_color($ms)
    {
        $ms = (float) $ms;

        if ($ms > 1000) return 'red';
        if ($ms > 200)  return 'yellow';
        return 'green';
    }
}