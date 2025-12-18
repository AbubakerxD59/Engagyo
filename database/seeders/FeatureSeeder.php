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
                'key' => Feature::$features_list[0],
                'name' => 'Social Accounts',
                'type' => 'numeric',
                'default_value' => 1,
                'description' => 'Number of social media accounts that can be connected (Facebook, Pinterest, TikTok, etc.) per account',
                'is_active' => true,
            ],
            [
                'key' => Feature::$features_list[1],
                'name' => 'Scheduled Posts Per Account',
                'type' => 'numeric',
                'default_value' => 10,
                'description' => 'Number of posts that can be scheduled per account per month',
                'is_active' => true,
            ],
            [
                'key' => Feature::$features_list[2],
                'name' => 'RSS Feed Automation',
                'type' => 'numeric',
                'default_value' => 1,
                'description' => 'Number of RSS feed automations allowed',
                'is_active' => true,
            ],
            [
                'key' => Feature::$features_list[3],
                'name' => 'Video Publishing or Scheduling',
                'type' => 'boolean',
                'default_value' => 0,
                'description' => 'Enable video publishing and scheduling functionality',
                'is_active' => true,
            ],
            [
                'key' => Feature::$features_list[4],
                'name' => 'API Keys',
                'type' => 'numeric',
                'default_value' => 1,
                'description' => 'Number of API keys that can be created',
                'is_active' => true,
            ],
            [
                'key' => Feature::$features_list[5],
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
