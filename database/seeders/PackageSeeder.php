<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Free',
                'description' => 'Build a momentum on social media',
                'price' => 0,
                'monthly_price' => 0.00,
                'icon' => '', // You can add icon path later if needed
                'duration' => 1,
                'date_type' => 'month',
                'trial_days' => 0,
                'is_active' => true,
                'sort_order' => 1,
                'stripe_product_id' => null,
                'stripe_price_id' => null,
            ],
            [
                'name' => 'Professional',
                'description' => 'Scale your social media efforts',
                'price' => 12,
                'monthly_price' => 12.00,
                'icon' => '', // You can add icon path later if needed
                'duration' => 1,
                'date_type' => 'month',
                'trial_days' => 7,
                'is_active' => true,
                'sort_order' => 2,
                'stripe_product_id' => null,
                'stripe_price_id' => null,
            ],
            [
                'name' => 'Business',
                'description' => 'Unleash the power of social media',
                'price' => 21,
                'monthly_price' => 21.00,
                'icon' => '', // You can add icon path later if needed
                'duration' => 1,
                'date_type' => 'month',
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 3,
                'stripe_product_id' => null,
                'stripe_price_id' => null,
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['name' => $package['name']],
                $package
            );
        }
    }
}
