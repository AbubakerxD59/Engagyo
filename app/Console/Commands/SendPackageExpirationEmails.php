<?php

namespace App\Console\Commands;

use App\Services\PackageExpirationEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPackageExpirationEmails extends Command
{
    protected $signature = 'packages:expiration-emails';

    protected $description = 'Send package expiration warning (3 days before) and expired notification emails';

    public function handle(PackageExpirationEmailService $service): int
    {
        if (! config('mail_branding.package_expiration_emails_enabled', true)) {
            $this->warn('Package expiration emails are disabled (MAIL_PACKAGE_EXPIRATION_EMAILS_ENABLED=false).');

            return Command::SUCCESS;
        }

        $this->info('Processing package expiration emails...');

        $warningSent = $service->processUpcomingExpirations();
        $expiredSent = $service->processExpiredPackages();

        $this->info("Expiring soon emails queued: {$warningSent}");
        $this->info("Expired emails queued: {$expiredSent}");

        Log::info('Package expiration emails processed', [
            'warning_sent' => $warningSent,
            'expired_sent' => $expiredSent,
        ]);

        return Command::SUCCESS;
    }
}
