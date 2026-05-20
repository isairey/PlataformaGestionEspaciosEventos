<?php

namespace App\Services;

/**
 * BookingHelper - Helper functions for booking calculations
 * Handles duration parsing, end time calculation, and conflict detection
 */
class BookingHelper
{
    /**
     * Parse duration string to hours
     * Examples: "4 hours" -> 4, "8 hours" -> 8, "1 day" -> 24, "1 month" -> 720
     * 
     * @param string|int $duration Duration string or numeric value
     * @return float Hours as a decimal number
     */
    public static function parseDurationToHours($duration)
    {
        // If already numeric, return as is
        if (is_numeric($duration)) {
            return (float)$duration;
        }

        if (!is_string($duration)) {
            return 4.0; // Default to 4 hours
        }

        $duration = strtolower(trim($duration));

        // Extract the numeric part
        if (!preg_match('/(\d+(?:\.\d+)?)/', $duration, $matches)) {
            return 4.0; // Default to 4 hours
        }

        $number = (float)$matches[1];

        // Determine unit and convert to hours
        if (strpos($duration, 'month') !== false) {
            return $number * 720; // 30 days * 24 hours
        } elseif (strpos($duration, 'week') !== false) {
            return $number * 168; // 7 days * 24 hours
        } elseif (strpos($duration, 'day') !== false) {
            return $number * 24;
        } elseif (strpos($duration, 'hour') !== false) {
            return $number;
        } elseif (strpos($duration, 'minute') !== false) {
            return $number / 60;
        }

        // Default: assume it's hours
        return $number;
    }

    /**
     * Calculate event end time
     * 
     * @param string $startTime Time in HH:MM:SS format (e.g., "14:30:00")
     * @param float $durationHours Total duration in hours (base + additional)
     * @return string End time in HH:MM:SS format
     */
    public static function calculateEventEndTime($startTime, $durationHours)
    {
        try {
            $startDateTime = \DateTime::createFromFormat('H:i:s', $startTime);
            
            if (!$startDateTime) {
                // Try without seconds
                $startDateTime = \DateTime::createFromFormat('H:i', $startTime);
                if (!$startDateTime) {
                    return null;
                }
            }

            // Convert hours to minutes
            $minutes = (int)($durationHours * 60);
            
            // Add the duration
            $startDateTime->modify("+{$minutes} minutes");
            
            return $startDateTime->format('H:i:s');
        } catch (\Exception $e) {
            log_message('error', 'Error calculating event end time: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate total duration hours from base plan duration and additional hours
     * 
     * @param string|int $baseDuration Duration from plan (e.g., "4 hours", "8 hours")
     * @param int $additionalHours Additional hours from extensions (e.g., 2)
     * @return float Total duration in hours
     */
    public static function calculateTotalDurationHours($baseDuration, $additionalHours = 0)
    {
        $baseHours = self::parseDurationToHours($baseDuration);
        return $baseHours + (float)$additionalHours;
    }

    /**
     * Format time for display
     * 
     * @param string $time Time in HH:MM:SS format
     * @return string Formatted time (e.g., "2:30 PM")
     */
    public static function formatTimeForDisplay($time)
    {
        try {
            $timeObj = \DateTime::createFromFormat('H:i:s', $time);
            if (!$timeObj) {
                $timeObj = \DateTime::createFromFormat('H:i', $time);
            }
            
            if (!$timeObj) {
                return $time;
            }
            
            return $timeObj->format('g:i A');
        } catch (\Exception $e) {
            return $time;
        }
    }

    /**
     * Calculate grace period end time (end time + grace hours)
     * 
     * @param string $endTime End time in HH:MM:SS format
     * @param int $graceHours Grace period in hours (default: 2)
     * @return string End time of grace period in HH:MM:SS format
     */
    public static function calculateGracePeriodEndTime($endTime, $graceHours = 2)
    {
        return self::calculateEventEndTime($endTime, $graceHours);
    }

    /**
     * Check if two time ranges conflict (including grace periods)
     * 
     * @param string $startTime1 Start time of first event (HH:MM:SS)
     * @param string $endTime1 End time of first event (HH:MM:SS)
     * @param string $startTime2 Start time of second event (HH:MM:SS)
     * @param string $endTime2 End time of second event (HH:MM:SS)
     * @param int $gracePeriodHours Grace period in hours
     * @return bool True if there is a conflict, false otherwise
     */
    public static function hasTimeConflict($startTime1, $endTime1, $startTime2, $endTime2, $gracePeriodHours = 2)
    {
        try {
            $start1 = \DateTime::createFromFormat('H:i:s', $startTime1);
            $end1 = \DateTime::createFromFormat('H:i:s', $endTime1);
            $start2 = \DateTime::createFromFormat('H:i:s', $startTime2);
            $end2 = \DateTime::createFromFormat('H:i:s', $endTime2);

            // Handle midnight crossing by assuming if end < start, end is next day
            if ($end1 < $start1) {
                $end1->modify('+1 day');
            }
            if ($end2 < $start2) {
                $end2->modify('+1 day');
            }

            // Add grace period to end times
            $end1->modify("+{$gracePeriodHours} hours");
            $end2->modify("+{$gracePeriodHours} hours");

            // Check for overlap
            return !($end1 <= $start2 || $end2 <= $start1);
        } catch (\Exception $e) {
            log_message('error', 'Error checking time conflict: ' . $e->getMessage());
            return false;
        }
    }
}
