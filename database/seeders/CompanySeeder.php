<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            'name' => 'Tech Solutions Inc.',
            'email' => 'contact@techsolutions.com',
            'phone' => '+1-555-0123',
            'address' => '123 Tech Street',
            'city' => 'San Francisco',
            'state' => 'California',
            'country' => 'United States',
            'postal_code' => '94105',
            'website' => 'https://techsolutions.com',
            'is_active' => true,
            'subscription_plan' => 'enterprise',
            'subscription_expires_at' => now()->addYear(),
        ]);

        Company::create([
            'name' => 'Creative Agency Ltd.',
            'email' => 'hello@creativeagency.com',
            'phone' => '+1-555-0456',
            'address' => '456 Creative Avenue',
            'city' => 'New York',
            'state' => 'New York',
            'country' => 'United States',
            'postal_code' => '10001',
            'website' => 'https://creativeagency.com',
            'is_active' => true,
            'subscription_plan' => 'premium',
            'subscription_expires_at' => now()->addMonths(6),
        ]);

        Company::create([
            'name' => 'Startup Hub',
            'email' => 'info@startuphub.com',
            'phone' => '+1-555-0789',
            'address' => '789 Innovation Boulevard',
            'city' => 'Austin',
            'state' => 'Texas',
            'country' => 'United States',
            'postal_code' => '73301',
            'website' => 'https://startuphub.com',
            'is_active' => true,
            'subscription_plan' => 'basic',
            'subscription_expires_at' => now()->addMonths(3),
        ]);
    }
}
