<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user for the HR system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating admin user...');

        // Create company first
        $company = Company::firstOrCreate(
            ['email' => 'contact@hr-system.com'],
            [
                'id' => Str::uuid(),
                'name' => 'HR System Company',
                'email' => 'contact@hr-system.com',
                'website' => 'https://hr-system.com',
                'is_active' => true
            ]
        );

        // Create admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@hr-system.com'],
            [
                'id' => Str::uuid(),
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@hr-system.com',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'is_active' => true,
                'email_verified_at' => now()
            ]
        );

        $this->info('✅ Admin user created successfully!');
        $this->line('📧 Email: admin@hr-system.com');
        $this->line('🔑 Password: password');
        $this->line('🏢 Company: ' . $company->name);
        
        return 0;
    }
}
