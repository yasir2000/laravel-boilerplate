<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $managers = $company->users()->role('manager')->get();
            $manager = $managers->first();

            if (!$manager) {
                continue;
            }

            // Create projects for each company
            Project::create([
                'name' => 'Website Redesign',
                'description' => 'Complete redesign of the company website with modern UI/UX',
                'status' => 'in_progress',
                'priority' => 'high',
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(60),
                'budget' => 50000.00,
                'company_id' => $company->id,
                'owner_id' => $manager->id,
                'client_name' => 'John Doe',
                'client_email' => 'john.doe@client.com',
                'progress_percentage' => 35,
            ]);

            Project::create([
                'name' => 'Mobile App Development',
                'description' => 'Development of a mobile application for iOS and Android platforms',
                'status' => 'planning',
                'priority' => 'medium',
                'start_date' => now()->addDays(15),
                'end_date' => now()->addDays(120),
                'budget' => 75000.00,
                'company_id' => $company->id,
                'owner_id' => $manager->id,
                'client_name' => 'Jane Smith',
                'client_email' => 'jane.smith@client.com',
                'progress_percentage' => 5,
            ]);

            Project::create([
                'name' => 'Database Migration',
                'description' => 'Migration of legacy database to PostgreSQL with data optimization',
                'status' => 'completed',
                'priority' => 'critical',
                'start_date' => now()->subDays(90),
                'end_date' => now()->subDays(10),
                'budget' => 25000.00,
                'company_id' => $company->id,
                'owner_id' => $manager->id,
                'client_name' => 'Tech Corp',
                'client_email' => 'admin@techcorp.com',
                'progress_percentage' => 100,
            ]);

            Project::create([
                'name' => 'API Integration',
                'description' => 'Integration with third-party APIs for enhanced functionality',
                'status' => 'on_hold',
                'priority' => 'low',
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(45),
                'budget' => 15000.00,
                'company_id' => $company->id,
                'owner_id' => $manager->id,
                'client_name' => 'API Solutions',
                'client_email' => 'contact@apisolutions.com',
                'progress_percentage' => 20,
            ]);
        }
    }
}
