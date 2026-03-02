<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class TimezoneService
{
    /**
     * Default timezone when user has none selected.
     */
    public const DEFAULT_TIMEZONE = 'America/New_York';

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
        return Carbon::parse($datetime, $timezone)->utc()->format('Y-m-d H:i');
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
