<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FeatureUsageService;
use Illuminate\Support\Facades\Log;

class CheckFeatureLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'features:check-limits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check feature usage limits and send notifications to users approaching or exceeding limits';

    protected $featureUsageService;

    public function __construct(FeatureUsageService $featureUsageService)
    {
        parent::__construct();
        $this->featureUsageService = $featureUsageService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking feature usage limits...');

        $summary = $this->featureUsageService->checkAndNotifyLimits();

        $this->info("Feature limit check completed!");
        $this->info("Users near limit: {$summary['near_limit']}");
        $this->info("Users over limit: {$summary['over_limit']}");
        $this->info("Total notifications sent: {$summary['notifications_sent']}");

        Log::info("Feature limit check completed. Near limit: {$summary['near_limit']}, Over limit: {$summary['over_limit']}, Notifications: {$summary['notifications_sent']}");

        return Command::SUCCESS;
    }
}
