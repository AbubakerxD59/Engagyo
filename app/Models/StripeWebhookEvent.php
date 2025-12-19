<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_event_id',
        'type',
        'status',
        'payload',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Check if event has been processed
     */
    public static function isProcessed(string $eventId): bool
    {
        return self::where('stripe_event_id', $eventId)
            ->where('status', 'processed')
            ->exists();
    }

    /**
     * Mark event as processed
     */
    public static function markAsProcessed(string $eventId, string $type, array $payload = []): void
    {
        self::updateOrCreate(
            ['stripe_event_id' => $eventId],
            [
                'type' => $type,
                'status' => 'processed',
                'payload' => $payload,
            ]
        );
    }

    /**
     * Mark event as failed
     */
    public static function markAsFailed(string $eventId, string $type, string $errorMessage, array $payload = []): void
    {
        self::updateOrCreate(
            ['stripe_event_id' => $eventId],
            [
                'type' => $type,
                'status' => 'failed',
                'payload' => $payload,
                'error_message' => $errorMessage,
            ]
        );
    }
}

