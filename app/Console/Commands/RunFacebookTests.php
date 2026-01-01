<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\FacebookTestService;
use App\Services\SocialMediaLogService;

class RunFacebookTests extends Command
{
    protected $signature = 'facebook:run-tests';

    protected $description = 'Run daily Facebook publishing tests for all post types';

    protected $logService;

    public function __construct()
    {
        parent::__construct();
        $this->logService = new SocialMediaLogService();
    }

    public function handle(FacebookTestService $testService)
    {
        try {
            $results = $testService->runAllTests();
            if (!$results['success']) {
                $this->logService->log('facebook', 'runTests', 'Facebook Tests Failed: ' . ($results['message'] ?? 'Unknown error'), [
                    'results' => $results['results']
                ], 'error');
                return 1;
            }
            return 0;
        } catch (\Exception $e) {
            $this->logService->log('facebook', 'runTests', 'Facebook Tests Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            return 1;
        }
    }
}
