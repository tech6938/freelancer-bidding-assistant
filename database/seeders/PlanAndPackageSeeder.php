<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;
use App\Models\BidPackage;

class PlanAndPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pre-seed Subscription Plans
        $plans = [
            [
                'name'             => 'Starter Plan',
                'slug'             => 'starter-plan',
                'price'            => 19.00,
                'duration_days'    => 30,
                'included_ai_bids' => 100,
                'badge'            => null,
                'description'      => 'Essential auto-bidding engine access for solo freelancers starting out.',
                'features'         => [
                    'has_autobid'          => true,
                    'free_ai_bids'         => 100,
                    'place_bids_limit'     => 100,
                    'skills_limit'         => 10,
                    'has_bid_price_matrix' => false,
                    'countries_limit'      => 5,
                    'languages_limit'      => 2,
                    'currencies_limit'     => 3,
                ],
                'is_active'        => true,
            ],
            [
                'name'             => 'Professional Plan',
                'slug'             => 'professional-plan',
                'price'            => 49.00,
                'duration_days'    => 30,
                'included_ai_bids' => 350,
                'badge'            => 'Most Popular',
                'description'      => 'Full-power automation with high bid limits and dynamic price matrix.',
                'features'         => [
                    'has_autobid'          => true,
                    'free_ai_bids'         => 350,
                    'place_bids_limit'     => 500,
                    'skills_limit'         => 30,
                    'has_bid_price_matrix' => true,
                    'countries_limit'      => 20,
                    'languages_limit'      => 10,
                    'currencies_limit'     => 15,
                ],
                'is_active'        => true,
            ],
            [
                'name'             => 'Agency Elite',
                'slug'             => 'agency-elite',
                'price'            => 129.00,
                'duration_days'    => 90,
                'included_ai_bids' => 1000,
                'badge'            => 'Best Value (90 Days)',
                'description'      => 'Quarterly access with huge bid capacity and priority multi-skill coverage.',
                'features'         => [
                    'has_autobid'          => true,
                    'free_ai_bids'         => 1000,
                    'place_bids_limit'     => 2000,
                    'skills_limit'         => 100,
                    'has_bid_price_matrix' => true,
                    'countries_limit'      => 50,
                    'languages_limit'      => 30,
                    'currencies_limit'     => 50,
                ],
                'is_active'        => true,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        // 2. Pre-seed AI Bid Packages (Strictly Bid Count / Quantities)
        $packages = [
            [
                'name'         => 'Starter 50 Bids Pack',
                'bid_quantity' => 50,
                'price'        => 9.00,
                'badge'        => null,
                'description'  => 'Instant +50 bids add-on for active subscribers.',
                'is_active'    => true,
            ],
            [
                'name'         => 'Booster 150 Bids Pack',
                'bid_quantity' => 150,
                'price'        => 24.00,
                'badge'        => 'Popular',
                'description'  => 'Instant +150 bids add-on with 10% bonus value.',
                'is_active'    => true,
            ],
            [
                'name'         => 'Scale 500 Bids Pack',
                'bid_quantity' => 500,
                'price'        => 69.00,
                'badge'        => 'Best Value',
                'description'  => 'Massive +500 bids top-up at our lowest per-bid rate.',
                'is_active'    => true,
            ],
        ];

        foreach ($packages as $pkgData) {
            BidPackage::updateOrCreate(
                ['name' => $pkgData['name']],
                $pkgData
            );
        }
    }
}
