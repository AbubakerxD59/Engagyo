<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FacebookTestService;
use Illuminate\Support\Facades\Log;

class RunFacebookTests extends Command
{
    protected $signature = 'facebook:run-tests';

    protected $description = 'Run daily Facebook publishing tests for all post types';

    public function handle(FacebookTestService $testService)
    {
        $this->info('Starting Facebook publishing tests...');

        try {
            $results = $testService->runAllTests();

            if (!$results['success']) {
                $this->error('Test execution failed: ' . ($results['message'] ?? 'Unknown error'));
                Log::error('Facebook Tests Failed: ' . ($results['message'] ?? 'Unknown error'));
                return 1;
            }

            $passed = 0;
            $failed = 0;

            foreach ($results['results'] as $type => $result) {
                if ($result['success']) {
                    $this->info("✓ {$type} test: PASSED");
                    $passed++;
                } else {
                    $this->error("✗ {$type} test: FAILED - " . ($result['message'] ?? 'Unknown error'));
                    $failed++;
                }
            }

            $this->info("\nTest Summary: {$passed} passed, {$failed} failed");

            Log::info('Facebook Tests Completed', [
                'passed' => $passed,
                'failed' => $failed,
                'results' => $results['results']
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('Exception during test execution: ' . $e->getMessage());
            Log::error('Facebook Tests Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}

