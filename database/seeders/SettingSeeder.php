<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'company_name', 'value' => env('APP_NAME', 'PakConnections')],
            ['key' => 'company_email', 'value' => ''],
            ['key' => 'company_phone_number', 'value' => '123456789'],
            ['key' => 'company_logo', 'value' => 'assets/img/site_logo.jpg'],
            ['key' => 'item_per_page', 'value' => '25'],
            ['key' => 'date_format', 'value' => 'jS M, Y'],
            ['key' => 'time_format', 'value' => 'h:m a'],
            ['key' => 'date_time_format', 'value' => 'jS M, Y h:m a'],
        ];
        try {
            if (count($settings) > 0) {
                foreach ($settings as $setting) {
                    $key = isset($setting['key']) ? $setting['key'] : '';
                    $getSetting = DB::table('settings')->where('key', $key)->first();
                    if (empty($getSetting)) {
                        DB::table('settings')->insert($setting);
                    }
                }
            }
        } catch (\Exception $exception) {
            $exception->getMessage();
        }
    }
}
