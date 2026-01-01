<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PinterestTestService;
use Illuminate\Support\Facades\Log;
use App\Services\SocialMediaLogService;

class RunPinterestTests extends Command
{
    protected $signature = 'pinterest:run-tests';

    protected $description = 'Run daily Pinterest publishing tests for all post types';

    protected $logService;

    public function __construct()
    {
        parent::__construct();
        $this->logService = new SocialMediaLogService();
    }

    public function handle(PinterestTestService $testService)
    {
        try {
            $results = $testService->runAllTests();

            if (!$results['success']) {
                $this->logService->log('pinterest', 'runTests', 'Pinterest Tests Failed: ' . ($results['message'] ?? 'Unknown error'), [
                    'results' => $results['results']
                ], 'error');
                return 1;
            }
            return 0;
        } catch (\Exception $e) {
            $this->logService->log('pinterest', 'runTests', 'Pinterest Tests Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ], 'error');
            return 1;
        }
    }
}
