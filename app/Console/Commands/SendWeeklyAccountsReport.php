<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WeeklyAccountsReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class SendWeeklyAccountsReport extends Command
{
    protected $signature = 'reports:weekly-accounts {--user= : Send to a single user ID (for testing)}';

    protected $description = 'Queue weekly connected-accounts report emails for all active panel users';

    public function handle(WeeklyAccountsReportService $reportService): int
    {
        if (! config('mail_branding.weekly_accounts_report_enabled', true)) {
            $this->warn('Weekly accounts report is disabled (MAIL_WEEKLY_ACCOUNTS_REPORT_ENABLED=false).');

            return Command::SUCCESS;
        }

        $userId = $this->option('user');
        if ($userId !== null && $userId !== '') {
            $user = User::query()->find($userId);
            if (! $user) {
                $this->error("User #{$userId} not found.");

                return Command::FAILURE;
            }

            $reportService->queueForUser($user);
            $this->info("Queued weekly report for user #{$user->id} ({$user->email}).");

            return Command::SUCCESS;
        }

        $roleId = Role::query()->where('name', 'User')->value('id');
        if (! $roleId) {
            $this->error('User role not found.');

            return Command::FAILURE;
        }

        $queued = 0;
        $skipped = 0;

        User::query()
            ->where('status', 1)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('email_verified_at')
            ->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId))
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($reportService, &$queued, &$skipped) {
                foreach ($users as $user) {
                    if (empty($user->email)) {
                        $skipped++;

                        continue;
                    }

                    try {
                        $reportService->queueForUser($user);
                        $queued++;
                    } catch (\Throwable $e) {
                        $skipped++;
                        Log::warning('Failed to queue weekly accounts report', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Weekly accounts report: {$queued} email(s) queued, {$skipped} skipped.");
        Log::info('Weekly accounts report dispatch completed', [
            'queued' => $queued,
            'skipped' => $skipped,
        ]);

        return Command::SUCCESS;
    }
}
