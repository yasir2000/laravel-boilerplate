<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        // Create Super Admin
        $superAdmin = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@laravel-boilerplate.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
            'company_id' => $companies->first()->id,
        ]);
        $superAdmin->assignRole('super-admin');

        // Create Admin for each company
        foreach ($companies as $company) {
            $admin = User::create([
                'first_name' => 'Admin',
                'last_name' => $company->name,
                'email' => "admin@{$company->email}",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'company_id' => $company->id,
            ]);
            $admin->assignRole('admin');

            // Create Manager
            $manager = User::create([
                'first_name' => 'Manager',
                'last_name' => $company->name,
                'email' => "manager@{$company->email}",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'company_id' => $company->id,
            ]);
            $manager->assignRole('manager');

            // Create Employees
            for ($i = 1; $i <= 3; $i++) {
                $employee = User::create([
                    'first_name' => "Employee {$i}",
                    'last_name' => $company->name,
                    'email' => "employee{$i}@{$company->email}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'company_id' => $company->id,
                ]);
                $employee->assignRole('employee');
            }

            // Create Client
            $client = User::create([
                'first_name' => 'Client',
                'last_name' => $company->name,
                'email' => "client@{$company->email}",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'company_id' => $company->id,
            ]);
            $client->assignRole('client');
        }
    }
}
