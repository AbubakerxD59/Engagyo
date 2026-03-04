<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class TimezoneService
{
    /**
     * Default timezone when user has none selected.
     */
    public const DEFAULT_TIMEZONE = "UTC";

    /**
     * Convert user's local datetime to UTC for storage.
     * Use when storing publish_date - cron jobs compare with UTC.
     *
     * @param string $datetime Datetime string in user's timezone (e.g. "2025-03-15 14:30")
     * @param User|null $user User model (for timezone). Uses DEFAULT_TIMEZONE if null/no timezone.
     * @return string Datetime in UTC format "Y-m-d H:i"
     */
    public static function toUtc(string $datetime, ?User $user = null): string
    {
        $timezone = self::getUserTimezone($user);
        return Carbon::createFromFormat('Y-m-d H:i', $datetime, $timezone)->utc()->format('Y-m-d H:i');
    }

    /**
     * Convert UTC datetime from storage to user's local timezone for display.
     *
     * @param string $utcDatetime Datetime stored in UTC (e.g. "2025-03-15 19:30")
     * @param User|null $user User model (for timezone). Uses DEFAULT_TIMEZONE if null/no timezone.
     * @return string Datetime in user's local format "Y-m-d H:i"
     */
    public static function toUserLocal(string $utcDatetime, ?User $user = null): string
    {
        $timezone = self::getUserTimezone($user);
        return Carbon::parse($utcDatetime, 'UTC')->setTimezone($timezone)->format('Y-m-d H:i');
    }

    /**
     * Parse UTC datetime to Carbon in user's timezone (for formatting/display).
     *
     * @param string $utcDatetime Datetime stored in UTC
     * @param User|null $user User model (for timezone)
     * @return Carbon
     */
    public static function parseUtcToUserCarbon(string $utcDatetime, ?User $user = null): Carbon
    {
        $timezone = self::getUserTimezone($user);
        return Carbon::parse($utcDatetime, 'UTC')->setTimezone($timezone);
    }

    /**
     * Get publish_date as Carbon in user's timezone for display.
     * Stored publish_date is in default timezone. If user's timezone equals default, return as-is; otherwise convert via UTC to user's timezone.
     *
     * @param string $publishDate Stored publish_date (in default timezone)
     * @param User|null $user User model (for timezone)
     * @return Carbon Carbon instance in user's timezone for formatting
     */
    public static function publishDateForDisplay(string $publishDate, ?User $user = null): Carbon
    {
        $userTz = self::getUserTimezone($user);
        $default_timezone = date_default_timezone_get();
        $parsed = Carbon::parse($publishDate, $default_timezone);
        if ($userTz === $default_timezone) {
            return $parsed;
        }
        return $parsed->utc()->setTimezone($userTz);
    }

    /**
     * Get the user's timezone name.
     *
     * @param User|null $user
     * @return string
     */
    public static function getUserTimezone(?User $user = null): string
    {
        if ($user && $user->relationLoaded('timezone') && $user->timezone) {
            return $user->timezone->name;
        }
        if ($user) {
            $user->loadMissing('timezone');
            if ($user->timezone) {
                return $user->timezone->name;
            }
        }
        return self::DEFAULT_TIMEZONE;
    }
}
