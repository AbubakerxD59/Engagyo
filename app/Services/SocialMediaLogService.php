<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SocialMediaLogService
{
    /**
     * Supported social media platforms
     */
    const PLATFORMS = [
        'facebook' => 'Facebook',
        'pinterest' => 'Pinterest',
        'tiktok' => 'TikTok',
        'instagram' => 'Instagram',
        'twitter' => 'Twitter',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
    ];

    /**
     * Log actions/events for a social media platform
     *
     * @param string $platform Platform name (facebook, pinterest, tiktok, etc.)
     * @param string $action Action/event type (post, draft, publish, error, etc.)
     * @param string $message Log message
     * @param array $context Additional context data
     * @param string $level Log level (info, warning, error, debug)
     * @return void
     */
    public function log($platform, $action, $message, $context = [], $level = 'info')
    {
        try {
            // Normalize platform name
            $platform = strtolower($platform);
            
            // Validate platform
            if (!isset(self::PLATFORMS[$platform])) {
                Log::warning("Invalid platform for logging: {$platform}");
                return;
            }

            // Get log channel for the platform
            $channel = $this->getLogChannel($platform);
            
            // Prepare log data
            $logData = [
                'platform' => self::PLATFORMS[$platform],
                'action' => $action,
                'message' => $message,
                'timestamp' => Carbon::now()->toIso8601String(),
                'context' => $context,
            ];

            // Log based on level
            switch (strtolower($level)) {
                case 'error':
                    Log::channel($channel)->error($message, $logData);
                    break;
                case 'warning':
                    Log::channel($channel)->warning($message, $logData);
                    break;
                case 'debug':
                    Log::channel($channel)->debug($message, $logData);
                    break;
                default:
                    Log::channel($channel)->info($message, $logData);
                    break;
            }
        } catch (Exception $e) {
            // Fallback to default log if platform-specific logging fails
            Log::error("Failed to log to platform channel: " . $e->getMessage(), [
                'platform' => $platform ?? 'unknown',
                'action' => $action ?? 'unknown',
                'original_message' => $message ?? 'unknown'
            ]);
        }
    }

    /**
     * Log post creation/publishing
     *
     * @param string $platform
     * @param string $postType (photo, video, link, content_only)
     * @param int $postId
     * @param array $data Post data
     * @param string $status (success, failed, pending)
     * @return void
     */
    public function logPost($platform, $postType, $postId, $data = [], $status = 'success')
    {
        $message = "Post {$status}: {$postType} post (ID: {$postId})";
        $context = [
            'post_id' => $postId,
            'post_type' => $postType,
            'status' => $status,
            'data' => $this->sanitizeData($data),
        ];

        $level = $status === 'failed' ? 'error' : 'info';
        $this->log($platform, 'post', $message, $context, $level);
    }

    /**
     * Log draft upload
     *
     * @param string $platform
     * @param string $postType
     * @param int $postId
     * @param array $data
     * @param string $status
     * @return void
     */
    public function logDraft($platform, $postType, $postId, $data = [], $status = 'success')
    {
        $message = "Draft {$status}: {$postType} draft uploaded (ID: {$postId})";
        $context = [
            'post_id' => $postId,
            'post_type' => $postType,
            'status' => $status,
            'data' => $this->sanitizeData($data),
        ];

        $level = $status === 'failed' ? 'error' : 'info';
        $this->log($platform, 'draft', $message, $context, $level);
    }

    /**
     * Log API errors
     *
     * @param string $platform
     * @param string $endpoint
     * @param string $errorMessage
     * @param array $context
     * @return void
     */
    public function logApiError($platform, $endpoint, $errorMessage, $context = [])
    {
        $message = "API Error: {$endpoint} - {$errorMessage}";
        $context = array_merge($context, [
            'endpoint' => $endpoint,
            'error' => $errorMessage,
        ]);

        $this->log($platform, 'api_error', $message, $context, 'error');
    }

    /**
     * Log token refresh
     *
     * @param string $platform
     * @param int $accountId
     * @param string $status
     * @param string $message
     * @return void
     */
    public function logTokenRefresh($platform, $accountId, $status, $message = '')
    {
        $logMessage = "Token refresh {$status} for account ID: {$accountId}";
        if ($message) {
            $logMessage .= " - {$message}";
        }

        $context = [
            'account_id' => $accountId,
            'status' => $status,
        ];

        $level = $status === 'failed' ? 'error' : 'info';
        $this->log($platform, 'token_refresh', $logMessage, $context, $level);
    }

    /**
     * Log account connection
     *
     * @param string $platform
     * @param int $accountId
     * @param string $username
     * @param string $status
     * @return void
     */
    public function logAccountConnection($platform, $accountId, $username, $status = 'connected')
    {
        $message = "Account {$status}: {$username} (ID: {$accountId})";
        $context = [
            'account_id' => $accountId,
            'username' => $username,
            'status' => $status,
        ];

        $level = $status === 'disconnected' || $status === 'failed' ? 'warning' : 'info';
        $this->log($platform, 'account_connection', $message, $context, $level);
    }

    /**
     * Log post deletion
     *
     * @param string $platform
     * @param int $postId
     * @param string $status
     * @return void
     */
    public function logPostDeletion($platform, $postId, $status = 'success')
    {
        $message = "Post deletion {$status}: Post ID {$postId}";
        $context = [
            'post_id' => $postId,
            'status' => $status,
        ];

        $level = $status === 'failed' ? 'error' : 'info';
        $this->log($platform, 'post_deletion', $message, $context, $level);
    }

    /**
     * Log scheduled post
     *
     * @param string $platform
     * @param int $postId
     * @param string $scheduledDate
     * @param array $data
     * @return void
     */
    public function logScheduledPost($platform, $postId, $scheduledDate, $data = [])
    {
        $message = "Post scheduled: Post ID {$postId} for {$scheduledDate}";
        $context = [
            'post_id' => $postId,
            'scheduled_date' => $scheduledDate,
            'data' => $this->sanitizeData($data),
        ];

        $this->log($platform, 'schedule', $message, $context, 'info');
    }

    /**
     * Log queued post
     *
     * @param string $platform
     * @param int $postId
     * @param array $data
     * @return void
     */
    public function logQueuedPost($platform, $postId, $data = [])
    {
        $message = "Post queued: Post ID {$postId}";
        $context = [
            'post_id' => $postId,
            'data' => $this->sanitizeData($data),
        ];

        $this->log($platform, 'queue', $message, $context, 'info');
    }

    /**
     * Get log channel name for platform
     *
     * @param string $platform
     * @return string
     */
    private function getLogChannel($platform)
    {
        return "social_{$platform}";
    }

    /**
     * Sanitize sensitive data from log context
     *
     * @param array $data
     * @return array
     */
    private function sanitizeData($data)
    {
        $sensitiveKeys = ['access_token', 'refresh_token', 'password', 'secret', 'api_key', 'token'];
        
        $sanitized = $data;
        
        foreach ($sensitiveKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '***REDACTED***';
            }
        }

        // Recursively sanitize nested arrays
        foreach ($sanitized as $k => $v) {
            if (is_array($v)) {
                $sanitized[$k] = $this->sanitizeData($v);
            }
        }

        return $sanitized;
    }

    /**
     * Get log file path for a platform and date
     *
     * @param string $platform
     * @param string|null $date Date in Y-m-d format (default: today)
     * @return string
     */
    public function getLogFilePath($platform, $date = null)
    {
        $date = $date ?: Carbon::now()->format('Y-m-d');
        $platform = strtolower($platform);
        $logDir = storage_path('logs/social-media');
        
        // Create directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        return "{$logDir}/{$platform}-{$date}.log";
    }

    /**
     * Read log entries for a platform and date
     *
     * @param string $platform
     * @param string|null $date
     * @return array
     */
    public function readLogs($platform, $date = null)
    {
        $filePath = $this->getLogFilePath($platform, $date);
        
        if (!file_exists($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];

        foreach ($lines as $line) {
            $logs[] = $line;
        }

        return $logs;
    }
}

