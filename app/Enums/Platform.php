<?php

namespace App\Enums;

enum Platform: string
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
     * Get the display name for the platform.
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
     * Get the icon class for the platform (Font Awesome).
     */
    public function icon(): string
    {
        return match ($this) {
            self::FACEBOOK => 'fab fa-facebook',
            self::PINTEREST => 'fab fa-pinterest',
            self::INSTAGRAM => 'fab fa-instagram',
            self::TWITTER => 'fab fa-x-twitter',
            self::TIKTOK => 'fab fa-tiktok',
            self::LINKEDIN => 'fab fa-linkedin',
            self::YOUTUBE => 'fab fa-youtube',
            self::SNAPCHAT => 'fab fa-snapchat',
            self::THREADS => 'fab fa-threads',
            self::WHATSAPP => 'fab fa-whatsapp',
            self::TELEGRAM => 'fab fa-telegram',
            self::REDDIT => 'fab fa-reddit',
            self::TUMBLR => 'fab fa-tumblr',
        };
    }

    /**
     * Get the brand color for the platform.
     */
    public function color(): string
    {
        return match ($this) {
            self::FACEBOOK => '#1877F2',
            self::PINTEREST => '#E60023',
            self::INSTAGRAM => '#E4405F',
            self::TWITTER => '#000000',
            self::TIKTOK => '#000000',
            self::LINKEDIN => '#0A66C2',
            self::YOUTUBE => '#FF0000',
            self::SNAPCHAT => '#FFFC00',
            self::THREADS => '#000000',
            self::WHATSAPP => '#25D366',
            self::TELEGRAM => '#26A5E4',
            self::REDDIT => '#FF4500',
            self::TUMBLR => '#36465D',
        };
    }

    /**
     * Check if the platform is currently supported for posting.
     */
    public function isSupported(): bool
    {
        return match ($this) {
            self::FACEBOOK, self::PINTEREST => true,
            default => false,
        };
    }

    /**
     * Get all supported platforms for posting.
     */
    public static function supported(): array
    {
        return array_filter(
            self::cases(),
            fn(Platform $platform) => $platform->isSupported()
        );
    }

    /**
     * Get all platform values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get platforms as key-value pairs for dropdowns.
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn(Platform $p) => $p->label(), self::cases())
        );
    }

    /**
     * Get only supported platforms as key-value pairs.
     */
    public static function supportedOptions(): array
    {
        $supported = self::supported();
        return array_combine(
            array_map(fn(Platform $p) => $p->value, $supported),
            array_map(fn(Platform $p) => $p->label(), $supported)
        );
    }
}

