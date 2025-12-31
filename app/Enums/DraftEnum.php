<?php

namespace App\Enums;

enum DraftEnum: string
{
    case FACEBOOK = 'facebook';
    case PINTEREST = 'pinterest';
    case INSTAGRAM = 'instagram';
    case TWITTER = 'twitter';
    case TIKTOK = 'tiktok';
    case LINKEDIN = 'linkedin';
    case YOUTUBE = 'youtube';
    case SNAPCHAT = 'snapchat';
    case THREADS = 'threads';
    case WHATSAPP = 'whatsapp';
    case TELEGRAM = 'telegram';
    case REDDIT = 'reddit';
    case TUMBLR = 'tumblr';

    /**
     * Check if draft functionality is active for this platform.
     *
     * @return bool
     */
    public function isDraftActive(): bool
    {
        return match ($this) {
            self::TIKTOK => true,
            default => false,
        };
    }

    /**
     * Get draft status for a platform by string value.
     *
     * @param string $platform
     * @return bool
     */
    public static function isDraftActiveFor(string $platform): bool
    {
        try {
            $enum = self::from(strtolower($platform));
            return $enum->isDraftActive();
        } catch (\ValueError $e) {
            return false;
        }
    }

    /**
     * Get all platforms with draft active.
     *
     * @return array
     */
    public static function getDraftActivePlatforms(): array
    {
        return array_filter(
            self::cases(),
            fn(DraftEnum $platform) => $platform->isDraftActive()
        );
    }

    /**
     * Get all platforms with draft active as string values.
     *
     * @return array
     */
    public static function getDraftActivePlatformValues(): array
    {
        return array_map(
            fn(DraftEnum $platform) => $platform->value,
            self::getDraftActivePlatforms()
        );
    }

    /**
     * Get the display name for the platform.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::FACEBOOK => 'Facebook',
            self::PINTEREST => 'Pinterest',
            self::INSTAGRAM => 'Instagram',
            self::TWITTER => 'Twitter/X',
            self::TIKTOK => 'TikTok',
            self::LINKEDIN => 'LinkedIn',
            self::YOUTUBE => 'YouTube',
            self::SNAPCHAT => 'Snapchat',
            self::THREADS => 'Threads',
            self::WHATSAPP => 'WhatsApp',
            self::TELEGRAM => 'Telegram',
            self::REDDIT => 'Reddit',
            self::TUMBLR => 'Tumblr',
        };
    }

    /**
     * Get all platform values as an array.
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get platforms as key-value pairs for dropdowns.
     *
     * @return array
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn(DraftEnum $p) => $p->label(), self::cases())
        );
    }

    /**
     * Get only platforms with draft active as key-value pairs.
     *
     * @return array
     */
    public static function draftActiveOptions(): array
    {
        $active = self::getDraftActivePlatforms();
        return array_combine(
            array_map(fn(DraftEnum $p) => $p->value, $active),
            array_map(fn(DraftEnum $p) => $p->label(), $active)
        );
    }
}

