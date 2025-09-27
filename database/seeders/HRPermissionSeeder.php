<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HRPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create HR-specific permissions
        $hrPermissions = [
            // Department Management
            'hr:departments:view',
            'hr:departments:create',
            'hr:departments:update',
            'hr:departments:delete',
            'hr:departments:manage',
            
            // Position Management
            'hr:positions:view',
            'hr:positions:create',
            'hr:positions:update',
            'hr:positions:delete',
            'hr:positions:manage',
            
            // Employee Management
            'hr:employees:view',
            'hr:employees:create',
            'hr:employees:update',
            'hr:employees:delete',
            'hr:employees:view-sensitive', // salary, personal info
            'hr:employees:manage-own',
            'hr:employees:manage-team',
            'hr:employees:manage-all',
            
            // Attendance Management
            'hr:attendance:view',
            'hr:attendance:create',
            'hr:attendance:update',
            'hr:attendance:delete',
            'hr:attendance:view-own',
            'hr:attendance:view-team',
            'hr:attendance:view-all',
            'hr:attendance:approve',
            
            // Leave Management
            'hr:leave:view',
            'hr:leave:create',
            'hr:leave:update',
            'hr:leave:delete',
            'hr:leave:view-own',
            'hr:leave:view-team',
            'hr:leave:view-all',
            'hr:leave:approve',
            'hr:leave:reject',
            
            // Performance Management
            'hr:evaluations:view',
            'hr:evaluations:create',
            'hr:evaluations:update',
            'hr:evaluations:delete',
            'hr:evaluations:view-own',
            'hr:evaluations:view-team',
            'hr:evaluations:view-all',
            'hr:evaluations:conduct',
            
            // Reports and Analytics
            'hr:reports:view',
            'hr:reports:export',
            'hr:reports:advanced',
            'hr:dashboard:view',
            'hr:dashboard:advanced',
            
            // File Management
            'hr:files:view',
            'hr:files:upload',
            'hr:files:download',
            'hr:files:delete',
            'hr:files:view-sensitive',
            
            // System Administration
            'hr:system:configure',
            'hr:system:audit',
            'hr:system:backup',
        ];

        // Create permissions
        foreach ($hrPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create HR-specific roles
        
        // HR Administrator - Full system access
        $hrAdmin = Role::firstOrCreate(['name' => 'hr-admin']);
        $hrAdmin->givePermissionTo($hrPermissions);
        
        // HR Manager - Manage all HR operations except system config
        $hrManager = Role::firstOrCreate(['name' => 'hr-manager']);
        $hrManagerPermissions = [
            'hr:departments:view', 'hr:departments:create', 'hr:departments:update', 'hr:departments:manage',
            'hr:positions:view', 'hr:positions:create', 'hr:positions:update', 'hr:positions:manage',
            'hr:employees:view', 'hr:employees:create', 'hr:employees:update', 'hr:employees:view-sensitive',
            'hr:employees:manage-all',
            'hr:attendance:view', 'hr:attendance:create', 'hr:attendance:update', 'hr:attendance:view-all', 'hr:attendance:approve',
            'hr:leave:view', 'hr:leave:create', 'hr:leave:update', 'hr:leave:view-all', 'hr:leave:approve', 'hr:leave:reject',
            'hr:evaluations:view', 'hr:evaluations:create', 'hr:evaluations:update', 'hr:evaluations:view-all', 'hr:evaluations:conduct',
            'hr:reports:view', 'hr:reports:export', 'hr:reports:advanced',
            'hr:dashboard:view', 'hr:dashboard:advanced',
            'hr:files:view', 'hr:files:upload', 'hr:files:download', 'hr:files:view-sensitive',
        ];
        $hrManager->givePermissionTo($hrManagerPermissions);
        
        // Department Manager - Manage their department and team
        $deptManager = Role::firstOrCreate(['name' => 'department-manager']);
        $deptManagerPermissions = [
            'hr:departments:view',
            'hr:positions:view',
            'hr:employees:view', 'hr:employees:manage-team',
            'hr:attendance:view', 'hr:attendance:view-team', 'hr:attendance:approve',
            'hr:leave:view', 'hr:leave:view-team', 'hr:leave:approve', 'hr:leave:reject',
            'hr:evaluations:view', 'hr:evaluations:create', 'hr:evaluations:update', 'hr:evaluations:view-team', 'hr:evaluations:conduct',
            'hr:reports:view',
            'hr:dashboard:view',
            'hr:files:view', 'hr:files:upload', 'hr:files:download',
        ];
        $deptManager->givePermissionTo($deptManagerPermissions);
        
        // HR Specialist - Regular HR operations
        $hrSpecialist = Role::firstOrCreate(['name' => 'hr-specialist']);
        $hrSpecialistPermissions = [
            'hr:departments:view',
            'hr:positions:view', 'hr:positions:create', 'hr:positions:update',
            'hr:employees:view', 'hr:employees:create', 'hr:employees:update',
            'hr:attendance:view', 'hr:attendance:view-all',
            'hr:leave:view', 'hr:leave:create', 'hr:leave:update', 'hr:leave:view-all',
            'hr:evaluations:view', 'hr:evaluations:view-all',
            'hr:reports:view',
            'hr:dashboard:view',
            'hr:files:view', 'hr:files:upload', 'hr:files:download',
        ];
        $hrSpecialist->givePermissionTo($hrSpecialistPermissions);
        
        // Team Lead - Manage direct reports
        $teamLead = Role::firstOrCreate(['name' => 'team-lead']);
        $teamLeadPermissions = [
            'hr:employees:view', 'hr:employees:manage-team',
            'hr:attendance:view', 'hr:attendance:view-team',
            'hr:leave:view', 'hr:leave:view-team', 'hr:leave:approve',
            'hr:evaluations:view', 'hr:evaluations:conduct', 'hr:evaluations:view-team',
            'hr:dashboard:view',
            'hr:files:view',
        ];
        $teamLead->givePermissionTo($teamLeadPermissions);
        
        // Regular Employee - Self-service
        $hrEmployee = Role::firstOrCreate(['name' => 'hr-employee']);
        $hrEmployeePermissions = [
            'hr:employees:manage-own',
            'hr:attendance:view-own', 'hr:attendance:create',
            'hr:leave:view-own', 'hr:leave:create', 'hr:leave:update',
            'hr:evaluations:view-own',
            'hr:dashboard:view',
            'hr:files:view', 'hr:files:upload',
        ];
        $hrEmployee->givePermissionTo($hrEmployeePermissions);

        $this->command->info('HR permissions and roles created successfully!');
    }
}