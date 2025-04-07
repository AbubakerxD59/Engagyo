<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            ['name' => 'Airport VIP Meet & Greet (Protocol)'],
            ['name' => 'Accomodation'],
            ['name' => 'Transport'],
            ['name' => 'Tourism'],
            ['name' => 'Shopping Trips'],
            ['name' => 'Medical Emergency 24/7'],
            ['name' => 'Crisis Help 24/7'],
            ['name' => 'Security Protection'],
            ['name' => "Children's Activity"],
            ['name' => "Personal Requests"],
            ['name' => "Tour Guides"],
            ['name' => "Legal Services"],
            ['name' => "Property Buy & Sell"],
            ['name' => "Property Maintenance"],
            ['name' => "Property Security"],
            ['name' => "Agricultural Land Services"],
            ['name' => "Property Disputes"],
            ['name' => "Household Staff"],
            ['name' => "Wedding Planning"],
            ['name' => "Personal Assistant 24/7"],
            ['name' => "Full VIP Concierge Services"],
        ];
        foreach ($features as $feature) {
            Feature::updateOrCreate(
                ['name' => $feature['name']],
                [
                    'name' => $feature['name']
                ]
            );
        }
    }
}
