<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;
use App\Models\Menu;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'id' => '1',
                'parent_id' => 'Accounts',
                'key' => Feature::$features_list[0],
                'name' => 'No. of Social Accounts',
                'type' => 'numeric',
                'default_value' => 1,
                'description' => 'Number of social media accounts that can be connected (Facebook, Pinterest, TikTok, etc.) per account',
                'is_active' => true,
            ],
            [
                'id' => '2',
                'parent_id' => 'Schedule',
                'key' => Feature::$features_list[1],
                'name' => 'Total Scheduled Posts',
                'type' => 'numeric',
                'default_value' => 10,
                'description' => 'Number of posts that can be scheduled per month',
                'is_active' => true,
            ],
            [
                'id' => '3',
                'parent_id' => 'Automation',
                'key' => Feature::$features_list[2],
                'name' => 'RSS Feed Automation',
                'type' => 'numeric',
                'default_value' => 1,
                'description' => 'Number of RSS Feed automations allowed',
                'is_active' => true,
            ],
            [
                'id' => '4',
                'parent_id' => 'Api Posts',
                'key' => Feature::$features_list[5],
                'name' => 'API Access',
                'type' => 'boolean',
                'default_value' => 0,
                'description' => 'Enable API access for programmatic posting and management',
                'is_active' => true,
            ],
            [
                'id' => '5',
                'parent_id' => 'URL Tracking',
                'key' => Feature::$features_list[6],
                'name' => 'URL Tracking',
                'type' => 'numeric',
                'default_value' => 1,
                'description' => 'Number of URL tracking domains allowed',
                'is_active' => true,
            ],
        ];

        foreach ($features as $feature) {
            $menu = Menu::where('name', $feature['parent_id'])->first();
            $feature['parent_id'] = $menu ? $menu->id : null;
            $checkFeature = Feature::where('key', $feature['key'])->first();
            if (!$checkFeature) {
                Feature::insert($feature);
            }
        }
    }
}
