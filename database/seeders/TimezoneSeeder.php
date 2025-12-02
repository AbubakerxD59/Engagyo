<?php

namespace Database\Seeders;

use App\Models\Timezone;
use Illuminate\Database\Seeder;

class TimezoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timezones = [
            // UTC
            ['name' => 'UTC', 'offset' => 'UTC+00:00', 'abbr' => 'UTC'],
            
            // Americas
            ['name' => 'America/New_York', 'offset' => 'UTC-05:00', 'abbr' => 'EST'],
            ['name' => 'America/Chicago', 'offset' => 'UTC-06:00', 'abbr' => 'CST'],
            ['name' => 'America/Denver', 'offset' => 'UTC-07:00', 'abbr' => 'MST'],
            ['name' => 'America/Los_Angeles', 'offset' => 'UTC-08:00', 'abbr' => 'PST'],
            ['name' => 'America/Anchorage', 'offset' => 'UTC-09:00', 'abbr' => 'AKST'],
            ['name' => 'America/Phoenix', 'offset' => 'UTC-07:00', 'abbr' => 'MST'],
            ['name' => 'America/Toronto', 'offset' => 'UTC-05:00', 'abbr' => 'EST'],
            ['name' => 'America/Vancouver', 'offset' => 'UTC-08:00', 'abbr' => 'PST'],
            ['name' => 'America/Mexico_City', 'offset' => 'UTC-06:00', 'abbr' => 'CST'],
            ['name' => 'America/Bogota', 'offset' => 'UTC-05:00', 'abbr' => 'COT'],
            ['name' => 'America/Lima', 'offset' => 'UTC-05:00', 'abbr' => 'PET'],
            ['name' => 'America/Santiago', 'offset' => 'UTC-04:00', 'abbr' => 'CLT'],
            ['name' => 'America/Buenos_Aires', 'offset' => 'UTC-03:00', 'abbr' => 'ART'],
            ['name' => 'America/Sao_Paulo', 'offset' => 'UTC-03:00', 'abbr' => 'BRT'],
            ['name' => 'America/Caracas', 'offset' => 'UTC-04:00', 'abbr' => 'VET'],
            
            // Europe
            ['name' => 'Europe/London', 'offset' => 'UTC+00:00', 'abbr' => 'GMT'],
            ['name' => 'Europe/Paris', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Berlin', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Madrid', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Rome', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Amsterdam', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Brussels', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Vienna', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Stockholm', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Oslo', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Copenhagen', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Helsinki', 'offset' => 'UTC+02:00', 'abbr' => 'EET'],
            ['name' => 'Europe/Athens', 'offset' => 'UTC+02:00', 'abbr' => 'EET'],
            ['name' => 'Europe/Bucharest', 'offset' => 'UTC+02:00', 'abbr' => 'EET'],
            ['name' => 'Europe/Moscow', 'offset' => 'UTC+03:00', 'abbr' => 'MSK'],
            ['name' => 'Europe/Istanbul', 'offset' => 'UTC+03:00', 'abbr' => 'TRT'],
            ['name' => 'Europe/Warsaw', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Prague', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Zurich', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Europe/Dublin', 'offset' => 'UTC+00:00', 'abbr' => 'GMT'],
            ['name' => 'Europe/Lisbon', 'offset' => 'UTC+00:00', 'abbr' => 'WET'],
            
            // Asia
            ['name' => 'Asia/Dubai', 'offset' => 'UTC+04:00', 'abbr' => 'GST'],
            ['name' => 'Asia/Karachi', 'offset' => 'UTC+05:00', 'abbr' => 'PKT'],
            ['name' => 'Asia/Kolkata', 'offset' => 'UTC+05:30', 'abbr' => 'IST'],
            ['name' => 'Asia/Dhaka', 'offset' => 'UTC+06:00', 'abbr' => 'BST'],
            ['name' => 'Asia/Bangkok', 'offset' => 'UTC+07:00', 'abbr' => 'ICT'],
            ['name' => 'Asia/Jakarta', 'offset' => 'UTC+07:00', 'abbr' => 'WIB'],
            ['name' => 'Asia/Singapore', 'offset' => 'UTC+08:00', 'abbr' => 'SGT'],
            ['name' => 'Asia/Hong_Kong', 'offset' => 'UTC+08:00', 'abbr' => 'HKT'],
            ['name' => 'Asia/Shanghai', 'offset' => 'UTC+08:00', 'abbr' => 'CST'],
            ['name' => 'Asia/Taipei', 'offset' => 'UTC+08:00', 'abbr' => 'CST'],
            ['name' => 'Asia/Seoul', 'offset' => 'UTC+09:00', 'abbr' => 'KST'],
            ['name' => 'Asia/Tokyo', 'offset' => 'UTC+09:00', 'abbr' => 'JST'],
            ['name' => 'Asia/Manila', 'offset' => 'UTC+08:00', 'abbr' => 'PHT'],
            ['name' => 'Asia/Kuala_Lumpur', 'offset' => 'UTC+08:00', 'abbr' => 'MYT'],
            ['name' => 'Asia/Riyadh', 'offset' => 'UTC+03:00', 'abbr' => 'AST'],
            ['name' => 'Asia/Tehran', 'offset' => 'UTC+03:30', 'abbr' => 'IRST'],
            ['name' => 'Asia/Jerusalem', 'offset' => 'UTC+02:00', 'abbr' => 'IST'],
            ['name' => 'Asia/Almaty', 'offset' => 'UTC+06:00', 'abbr' => 'ALMT'],
            ['name' => 'Asia/Tashkent', 'offset' => 'UTC+05:00', 'abbr' => 'UZT'],
            ['name' => 'Asia/Colombo', 'offset' => 'UTC+05:30', 'abbr' => 'IST'],
            ['name' => 'Asia/Yangon', 'offset' => 'UTC+06:30', 'abbr' => 'MMT'],
            ['name' => 'Asia/Ho_Chi_Minh', 'offset' => 'UTC+07:00', 'abbr' => 'ICT'],
            
            // Pacific
            ['name' => 'Pacific/Honolulu', 'offset' => 'UTC-10:00', 'abbr' => 'HST'],
            ['name' => 'Pacific/Auckland', 'offset' => 'UTC+12:00', 'abbr' => 'NZST'],
            ['name' => 'Pacific/Fiji', 'offset' => 'UTC+12:00', 'abbr' => 'FJT'],
            ['name' => 'Pacific/Guam', 'offset' => 'UTC+10:00', 'abbr' => 'ChST'],
            ['name' => 'Pacific/Samoa', 'offset' => 'UTC-11:00', 'abbr' => 'SST'],
            
            // Australia
            ['name' => 'Australia/Sydney', 'offset' => 'UTC+10:00', 'abbr' => 'AEST'],
            ['name' => 'Australia/Melbourne', 'offset' => 'UTC+10:00', 'abbr' => 'AEST'],
            ['name' => 'Australia/Brisbane', 'offset' => 'UTC+10:00', 'abbr' => 'AEST'],
            ['name' => 'Australia/Perth', 'offset' => 'UTC+08:00', 'abbr' => 'AWST'],
            ['name' => 'Australia/Adelaide', 'offset' => 'UTC+09:30', 'abbr' => 'ACST'],
            ['name' => 'Australia/Darwin', 'offset' => 'UTC+09:30', 'abbr' => 'ACST'],
            ['name' => 'Australia/Hobart', 'offset' => 'UTC+10:00', 'abbr' => 'AEST'],
            
            // Africa
            ['name' => 'Africa/Cairo', 'offset' => 'UTC+02:00', 'abbr' => 'EET'],
            ['name' => 'Africa/Lagos', 'offset' => 'UTC+01:00', 'abbr' => 'WAT'],
            ['name' => 'Africa/Johannesburg', 'offset' => 'UTC+02:00', 'abbr' => 'SAST'],
            ['name' => 'Africa/Nairobi', 'offset' => 'UTC+03:00', 'abbr' => 'EAT'],
            ['name' => 'Africa/Casablanca', 'offset' => 'UTC+01:00', 'abbr' => 'WEST'],
            ['name' => 'Africa/Accra', 'offset' => 'UTC+00:00', 'abbr' => 'GMT'],
            ['name' => 'Africa/Algiers', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            ['name' => 'Africa/Tunis', 'offset' => 'UTC+01:00', 'abbr' => 'CET'],
            
            // Atlantic
            ['name' => 'Atlantic/Reykjavik', 'offset' => 'UTC+00:00', 'abbr' => 'GMT'],
            ['name' => 'Atlantic/Azores', 'offset' => 'UTC-01:00', 'abbr' => 'AZOT'],
            ['name' => 'Atlantic/Cape_Verde', 'offset' => 'UTC-01:00', 'abbr' => 'CVT'],
            
            // Indian Ocean
            ['name' => 'Indian/Maldives', 'offset' => 'UTC+05:00', 'abbr' => 'MVT'],
            ['name' => 'Indian/Mauritius', 'offset' => 'UTC+04:00', 'abbr' => 'MUT'],
        ];

        foreach ($timezones as $timezone) {
            Timezone::updateOrCreate(
                ['name' => $timezone['name']],
                $timezone
            );
        }
    }
}

