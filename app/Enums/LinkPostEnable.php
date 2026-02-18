<?php

namespace App\Enums;

enum LinkPostEnable: string
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
     * Check if link posting API is enabled for this platform.
     * Only Facebook and Pinterest support link posts.
     *
     * @return bool
     */
    public function isLinkPostEnabled(): bool
    {
        return match ($this) {
            self::FACEBOOK, self::PINTEREST => true,
            default => false,
        };
    }

    /**
     * Check if link posting is enabled for a platform by string value.
     *
     * @param string $platform
     * @return bool
     */
    public static function isLinkPostEnabledFor(string $platform): bool
    {
        try {
            $enum = self::from(strtolower($platform));
            return $enum->isLinkPostEnabled();
        } catch (\ValueError $e) {
            return false;
        }
    }

    /**
     * Get all platforms with link posting enabled.
     *
     * @return array<LinkPostEnable>
     */
    public static function getLinkPostEnabledPlatforms(): array
    {
        return array_filter(
            self::cases(),
            fn(self $platform) => $platform->isLinkPostEnabled()
        );
    }

    /**
     * Get all platforms with link posting enabled as string values.
     *
     * @return array<string>
     */
    public static function getLinkPostEnabledPlatformValues(): array
    {
        return array_map(
            fn(self $platform) => $platform->value,
            self::getLinkPostEnabledPlatforms()
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
}
