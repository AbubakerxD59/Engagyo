<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'key' => 'social_accounts',
                'name' => 'Social Accounts',
                'type' => 'numeric',
                'default_value' => 1,
                'description' => 'Number of social media accounts that can be connected (Facebook, Pinterest, TikTok, etc.)',
                'is_active' => true,
            ],
            [
                'key' => 'scheduled_posts_per_account',
                'name' => 'Scheduled Posts Per Account',
                'type' => 'numeric',
                'default_value' => 10,
                'description' => 'Number of posts that can be scheduled per account per month',
                'is_active' => true,
            ],
            [
                'key' => 'rss_feed_automation',
                'name' => 'RSS Feed Automation',
                'type' => 'numeric',
                'default_value' => 1,
                'description' => 'Number of RSS feed automations allowed',
                'is_active' => true,
            ],
            [
                'key' => 'video_publishing',
                'name' => 'Video Publishing or Scheduling',
                'type' => 'boolean',
                'default_value' => 0,
                'description' => 'Enable video publishing and scheduling functionality',
                'is_active' => true,
            ],
            [
                'key' => 'api_keys',
                'name' => 'API Keys',
                'type' => 'numeric',
                'default_value' => 1,
                'description' => 'Number of API keys that can be created',
                'is_active' => true,
            ],
            [
                'key' => 'api_access',
                'name' => 'APIs Access',
                'type' => 'boolean',
                'default_value' => 0,
                'description' => 'Enable API access for programmatic posting and management',
                'is_active' => true,
            ],
        ];

        foreach ($features as $featureData) {
            Feature::updateOrCreate(
                ['key' => $featureData['key']],
                $featureData
            );
        }
    }
}

