<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws \JsonException
     */
    public function run(): void
    {
        Plan::factory()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Perfect for getting started',
            'price' => 0,
            'pdf_limit' => 10,
            'features' => json_encode([
                '10 PDFs per month',
                'Standard summaries',
                'Email support',
                'Basic export options',
            ], JSON_THROW_ON_ERROR),
            'is_active' => true,
        ]);

        Plan::factory()->create([
            'name' => 'Standard',
            'slug' => 'standard',
            'description' => 'Best for regular use',
            'price' => 9.99,
            'pdf_limit' => 50,
            'features' => json_encode([
                '50 PDFs per month',
                'All summaries types',
                'Priority support',
                'Many export options',
                'Advanced analytics',
            ], JSON_THROW_ON_ERROR),
            'is_active' => true,
        ]);

        Plan::factory()->create([
            'name' => 'Premium',
            'slug' => 'premium',
            'description' => 'Powerful for business',
            'price' => 29.99,
            'pdf_limit' => 500,
            'features' => json_encode([
                '500 PDFs per month',
                'All summaries types',
                'API access support',
                'Many export options',
                'Custom integration',
            ], JSON_THROW_ON_ERROR),
            'is_active' => true,
        ]);
    }
}
