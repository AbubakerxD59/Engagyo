<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TikTokTestService;
use App\Services\SocialMediaLogService;

class RunTikTokTests extends Command
{
    protected $signature = 'tiktok:run-tests';

    protected $description = 'Run daily TikTok publishing tests for all post types';

    protected $logService;

    public function __construct()
    {
        parent::__construct();
        $this->logService = new SocialMediaLogService();
    }

    public function handle(TikTokTestService $testService)
    {
        try {
            $results = $testService->runAllTests();

            if (!$results['success']) {
                $this->logService->log('tiktok', 'runTests', 'TikTok Tests Failed: ' . ($results['message'] ?? 'Unknown error'), [
                    'results' => $results['results']
                ], 'error');
                return 1;
            }

            return 0;
        } catch (\Exception $e) {
            $this->logService->log('tiktok', 'runTests', 'TikTok Tests Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            return 1;
        }
    }
}
