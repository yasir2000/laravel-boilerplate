<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HR\Department;
use App\Models\HR\Position;
use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class HRSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create departments
        $departments = [
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Manages employee relations, recruitment, and HR policies',
                'location' => 'Building A - Floor 1',
                'budget' => 500000,
                'max_employees' => 10,
                'is_active' => true
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Manages technology infrastructure and software development',
                'location' => 'Building B - Floor 3',
                'budget' => 1000000,
                'max_employees' => 25,
                'is_active' => true
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'description' => 'Manages financial operations and accounting',
                'location' => 'Building A - Floor 2',
                'budget' => 750000,
                'max_employees' => 15,
                'is_active' => true
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Manages marketing campaigns and brand promotion',
                'location' => 'Building C - Floor 1',
                'budget' => 600000,
                'max_employees' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Sales',
                'code' => 'SAL',
                'description' => 'Manages sales operations and customer relationships',
                'location' => 'Building C - Floor 2',
                'budget' => 800000,
                'max_employees' => 20,
                'is_active' => true
            ]
        ];

        foreach ($departments as $deptData) {
            Department::create($deptData);
        }

        // Create positions
        $positions = [
            // HR Department
            ['title' => 'HR Manager', 'code' => 'HR-MGR', 'department_id' => 1, 'level' => 'manager', 'min_salary' => 60000, 'max_salary' => 80000],
            ['title' => 'HR Specialist', 'code' => 'HR-SPC', 'department_id' => 1, 'level' => 'senior', 'min_salary' => 45000, 'max_salary' => 55000],
            ['title' => 'Recruiter', 'code' => 'HR-REC', 'department_id' => 1, 'level' => 'mid', 'min_salary' => 40000, 'max_salary' => 50000],
            
            // IT Department
            ['title' => 'IT Director', 'code' => 'IT-DIR', 'department_id' => 2, 'level' => 'director', 'min_salary' => 90000, 'max_salary' => 120000],
            ['title' => 'Senior Software Developer', 'code' => 'IT-SSD', 'department_id' => 2, 'level' => 'senior', 'min_salary' => 70000, 'max_salary' => 90000],
            ['title' => 'Software Developer', 'code' => 'IT-SD', 'department_id' => 2, 'level' => 'mid', 'min_salary' => 50000, 'max_salary' => 70000],
            ['title' => 'Junior Developer', 'code' => 'IT-JD', 'department_id' => 2, 'level' => 'junior', 'min_salary' => 35000, 'max_salary' => 50000],
            ['title' => 'System Administrator', 'code' => 'IT-SA', 'department_id' => 2, 'level' => 'senior', 'min_salary' => 55000, 'max_salary' => 75000],
            
            // Finance Department
            ['title' => 'Finance Manager', 'code' => 'FIN-MGR', 'department_id' => 3, 'level' => 'manager', 'min_salary' => 65000, 'max_salary' => 85000],
            ['title' => 'Senior Accountant', 'code' => 'FIN-SA', 'department_id' => 3, 'level' => 'senior', 'min_salary' => 50000, 'max_salary' => 65000],
            ['title' => 'Accountant', 'code' => 'FIN-ACC', 'department_id' => 3, 'level' => 'mid', 'min_salary' => 40000, 'max_salary' => 55000],
            
            // Marketing Department
            ['title' => 'Marketing Manager', 'code' => 'MKT-MGR', 'department_id' => 4, 'level' => 'manager', 'min_salary' => 55000, 'max_salary' => 75000],
            ['title' => 'Marketing Specialist', 'code' => 'MKT-SPC', 'department_id' => 4, 'level' => 'mid', 'min_salary' => 45000, 'max_salary' => 60000],
            ['title' => 'Content Creator', 'code' => 'MKT-CC', 'department_id' => 4, 'level' => 'junior', 'min_salary' => 35000, 'max_salary' => 45000],
            
            // Sales Department
            ['title' => 'Sales Manager', 'code' => 'SAL-MGR', 'department_id' => 5, 'level' => 'manager', 'min_salary' => 60000, 'max_salary' => 80000],
            ['title' => 'Senior Sales Representative', 'code' => 'SAL-SSR', 'department_id' => 5, 'level' => 'senior', 'min_salary' => 45000, 'max_salary' => 65000],
            ['title' => 'Sales Representative', 'code' => 'SAL-SR', 'department_id' => 5, 'level' => 'mid', 'min_salary' => 35000, 'max_salary' => 50000],
        ];

        foreach ($positions as $posData) {
            Position::create($posData);
        }

        // Create sample employees
        $employees = [
            [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@company.com',
                'department_id' => 1,
                'position_id' => 1, // HR Manager
                'hire_date' => '2020-01-15',
                'salary' => 70000,
                'employment_type' => 'full_time',
                'employment_status' => 'active'
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah.johnson@company.com',
                'department_id' => 2,
                'position_id' => 4, // IT Director
                'hire_date' => '2019-03-20',
                'salary' => 105000,
                'employment_type' => 'full_time',
                'employment_status' => 'active'
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'email' => 'michael.brown@company.com',
                'department_id' => 2,
                'position_id' => 5, // Senior Software Developer
                'hire_date' => '2021-05-10',
                'salary' => 80000,
                'employment_type' => 'full_time',
                'employment_status' => 'active'
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Davis',
                'email' => 'emily.davis@company.com',
                'department_id' => 3,
                'position_id' => 9, // Finance Manager
                'hire_date' => '2020-08-01',
                'salary' => 75000,
                'employment_type' => 'full_time',
                'employment_status' => 'active'
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Wilson',
                'email' => 'david.wilson@company.com',
                'department_id' => 4,
                'position_id' => 12, // Marketing Manager
                'hire_date' => '2021-02-15',
                'salary' => 65000,
                'employment_type' => 'full_time',
                'employment_status' => 'active'
            ],
            [
                'first_name' => 'Lisa',
                'last_name' => 'Anderson',
                'email' => 'lisa.anderson@company.com',
                'department_id' => 5,
                'position_id' => 15, // Sales Manager
                'hire_date' => '2020-11-30',
                'salary' => 70000,
                'employment_type' => 'full_time',
                'employment_status' => 'active'
            ]
        ];

        foreach ($employees as $empData) {
            // Create user account first
            $user = User::create([
                'name' => $empData['first_name'] . ' ' . $empData['last_name'],
                'email' => $empData['email'],
                'password' => Hash::make('password123'),
                'email_verified_at' => now()
            ]);

            // Create employee record
            $employeeData = $empData;
            unset($employeeData['email']);
            $employeeData['user_id'] = $user->id;
            $employeeData['employee_id'] = 'EMP' . now()->year . str_pad(Employee::count() + 1, 4, '0', STR_PAD_LEFT);
            $employeeData['salary_type'] = 'yearly';
            $employeeData['work_hours_per_week'] = 40;
            $employeeData['vacation_days_per_year'] = 21;
            $employeeData['sick_days_per_year'] = 10;

            Employee::create($employeeData);
        }

        echo "HR seed data created successfully!\n";
        echo "Created " . Department::count() . " departments\n";
        echo "Created " . Position::count() . " positions\n";
        echo "Created " . Employee::count() . " employees\n";
    }
}