<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * DateHelper class that extends Carbon functionality for various date comparisons and conversions.
 */
class DateHelper
{
    /**
     * Returns present Date.
     *
     * @param string|null $date
     * @return Carbon
     */
    public static function now()
    {
        return Carbon::now();
    }
    /**
     * Check if the given date is today.
     *
     * @param string|null $date
     * @return bool
     */
    public static function isToday($date = null)
    {
        return Carbon::parse($date)->isToday();
    }

    /**
     * Check if the given date is yesterday.
     *
     * @param string|null $date
     * @return bool
     */
    public static function isYesterday($date = null)
    {
        return Carbon::parse($date)->isYesterday();
    }

    /**
     * Check if the given date is tomorrow.
     *
     * @param string|null $date
     * @return bool
     */
    public static function isTomorrow($date = null)
    {
        return Carbon::parse($date)->isTomorrow();
    }

    /**
     * Check if the given date falls on a weekend.
     *
     * @param string|null $date
     * @return bool
     */
    public static function isWeekend($date = null)
    {
        return Carbon::parse($date)->isWeekend();
    }

    /**
     * Check if the given date falls on a weekday.
     *
     * @param string|null $date
     * @return bool
     */
    public static function isWeekday($date = null)
    {
        return Carbon::parse($date)->isWeekday();
    }

    /**
     * Check if the given date is in the future.
     *
     * @param string|null $date
     * @return bool
     */
    public static function isFuture($date = null)
    {
        return Carbon::parse($date)->isFuture();
    }

    /**
     * Check if the given date is in the past.
     *
     * @param string|null $date
     * @return bool
     */
    public static function isPast($date = null)
    {
        return Carbon::parse($date)->isPast();
    }

    /**
     * Check if the year of the given date is a leap year.
     *
     * @param string|null $date
     * @return bool
     */
    public static function isLeapYear($date = null)
    {
        return Carbon::parse($date)->isLeapYear();
    }

    /**
     * Check if the given date is the same day as another date.
     *
     * @param string $date
     * @param string $otherDate
     * @return bool
     */
    public static function isSameDay($date, $otherDate)
    {
        return Carbon::parse($date)->isSameDay(Carbon::parse($otherDate));
    }

    /**
     * Check if the given date is in the same month as another date.
     *
     * @param string $date
     * @param string $otherDate
     * @return bool
     */
    public static function isSameMonth($date, $otherDate)
    {
        return Carbon::parse($date)->isSameMonth(Carbon::parse($otherDate));
    }

    /**
     * Check if the given date is in the same year as another date.
     *
     * @param string $date
     * @param string $otherDate
     * @return bool
     */
    public static function isSameYear($date, $otherDate)
    {
        return Carbon::parse($date)->isSameYear(Carbon::parse($otherDate));
    }

    /**
     * Add a number of minutes to the given date.
     *
     * @param int $minutes
     * @param string|null $date
     * @return Carbon
     */
    public static function addMinutes($minutes = 1, $date = null)
    {
        return Carbon::parse($date)->addMinutes($minutes);
    }

    /**
     * Add a number of days to the given date.
     *
     * @param int $days
     * @param string|null $date
     * @return Carbon
     */
    public static function addDays($days = 1, $date = null)
    {
        return Carbon::parse($date)->addDays($days);
    }

    /**
     * Add a number of weeks to the given date.
     *
     * @param int $weeks
     * @param string|null $date
     * @return Carbon
     */
    public static function addWeeks($weeks, $date = null)
    {
        return Carbon::parse($date)->addWeeks($weeks);
    }

    /**
     * Add a number of months to the given date.
     *
     * @param int $months
     * @param string|null $date
     * @return Carbon
     */
    public static function addMonths($months, $date = null)
    {
        return Carbon::parse($date)->addMonths($months);
    }

    /**
     * Add a number of years to the given date.
     *
     * @param int $years
     * @param string|null $date
     * @return Carbon
     */
    public static function addYears($years, $date = null)
    {
        return Carbon::parse($date)->addYears($years);
    }

    /**
     * Subtract a number of days from the given date.
     *
     * @param int $days
     * @param string|null $date
     * @return Carbon
     */
    public static function subDays($days, $date = null)
    {
        return Carbon::parse($date)->subDays($days);
    }

    /**
     * Subtract a number of weeks from the given date.
     *
     * @param int $weeks
     * @param string|null $date
     * @return Carbon
     */
    public static function subWeeks($weeks, $date = null)
    {
        return Carbon::parse($date)->subWeeks($weeks);
    }

    /**
     * Subtract a number of months from the given date.
     *
     * @param int $months
     * @param string|null $date
     * @return Carbon
     */
    public static function subMonths($months, $date = null)
    {
        return Carbon::parse($date)->subMonths($months);
    }

    /**
     * Subtract a number of years from the given date.
     *
     * @param int $years
     * @param string|null $date
     * @return Carbon
     */
    public static function subYears($years, $date = null)
    {
        return Carbon::parse($date)->subYears($years);
    }

    /**
     * Get the start of the given day.
     *
     * @param string|null $date
     * @return Carbon
     */
    public static function startOfDay($date = null)
    {
        return Carbon::parse($date)->startOfDay();
    }

    /**
     * Get the end of the given day.
     *
     * @param string|null $date
     * @return Carbon
     */
    public static function endOfDay($date = null)
    {
        return Carbon::parse($date)->endOfDay();
    }

    /**
     * Get the start of the given week.
     *
     * @param string|null $date
     * @return Carbon
     */
    public static function startOfWeek($date = null)
    {
        return Carbon::parse($date)->startOfWeek();
    }

    /**
     * Get the end of the given week.
     *
     * @param string|null $date
     * @return Carbon
     */
    public static function endOfWeek($date = null)
    {
        return Carbon::parse($date)->endOfWeek();
    }

    /**
     * Get the start of the given month.
     *
     * @param string|null $date
     * @return Carbon
     */
    public static function startOfMonth($date = null)
    {
        return Carbon::parse($date)->startOfMonth();
    }

    /**
     * Get the end of the given month.
     *
     * @param string|null $date
     * @return Carbon
     */
    public static function endOfMonth($date = null)
    {
        return Carbon::parse($date)->endOfMonth();
    }

    /**
     * Get the start of the given year.
     *
     * @param string|null $date
     * @return Carbon
     */
    public static function startOfYear($date = null)
    {
        return Carbon::parse($date)->startOfYear();
    }

    /**
     * Get the end of the given year.
     *
     * @param string|null $date
     * @return Carbon
     */
    public static function endOfYear($date = null)
    {
        return Carbon::parse($date)->endOfYear();
    }

    /**
     * Get the difference in minutes between the given date and another date.
     *
     * @param string $date
     * @param string $otherDate
     * @return int
     */
    public static function diffInMinutes($date, $otherDate)
    {
        return Carbon::parse($date)->diffInMinutes(Carbon::parse($otherDate));
    }

    /**
     * Get the difference in days between the given date and another date.
     *
     * @param string $date
     * @param string $otherDate
     * @return int
     */
    public static function diffInDays($date, $otherDate)
    {
        return Carbon::parse($date)->diffInDays(Carbon::parse($otherDate));
    }

    /**
     * Get the difference in months between the given date and another date.
     *
     * @param string $date
     * @param string $otherDate
     * @return int
     */
    public static function diffInMonths($date, $otherDate)
    {
        return Carbon::parse($date)->diffInMonths(Carbon::parse($otherDate));
    }

    /**
     * Get the difference in years between the given date and another date.
     *
     * @param string $date
     * @param string $otherDate
     * @return int
     */
    public static function diffInYears($date, $otherDate)
    {
        return Carbon::parse($date)->diffInYears(Carbon::parse($otherDate));
    }

    /**
     * Get the formatted date.
     *
     * @param string|null $date
     * @param string $format
     * @return string
     */
    public static function format($date = null, $format = "Y-m-d H:i:s")
    {
        return Carbon::parse($date)->format($format);
    }

    /**
     * Parse a date string into a Carbon instance.
     *
     * @param string $date
     * @return Carbon
     */
    public static function parse($date)
    {
        return Carbon::parse($date);
    }
}
