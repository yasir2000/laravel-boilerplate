<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIAgentService;
use App\Models\Employee;

class TriggerEmployeeOnboarding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:onboard-employee {employee_id : The ID of the employee to onboard}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger AI-powered employee onboarding workflow';

    protected $agentService;

    public function __construct(AIAgentService $agentService)
    {
        parent::__construct();
        $this->agentService = $agentService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employeeId = $this->argument('employee_id');
        
        $this->info("Triggering AI-powered onboarding for employee ID: {$employeeId}");
        $this->newLine();

        // Get employee data
        $employee = Employee::find($employeeId);
        
        if (!$employee) {
            $this->error("Employee with ID {$employeeId} not found.");
            return Command::FAILURE;
        }

        $this->line("Employee: {$employee->name}");
        $this->line("Email: {$employee->email}");
        $this->line("Department: " . ($employee->department->name ?? 'N/A'));
        $this->line("Position: " . ($employee->position ?? 'N/A'));
        $this->newLine();

        // Prepare employee data for onboarding
        $employeeData = [
            'employee_id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'department_id' => $employee->department_id,
            'manager_id' => $employee->manager_id,
            'position' => $employee->position,
            'hire_date' => $employee->hire_date?->toDateString(),
            'salary' => $employee->salary
        ];

        // Trigger onboarding workflow
        $this->info('Initiating onboarding workflow...');
        
        $result = $this->agentService->processEmployeeOnboarding($employeeData);

        if ($result['success']) {
            $this->info('✓ Onboarding workflow initiated successfully');
            
            if (isset($result['workflow_id'])) {
                $this->line("Workflow ID: {$result['workflow_id']}");
            }
            
            if (isset($result['next_steps'])) {
                $this->newLine();
                $this->info('Next Steps:');
                foreach ($result['next_steps'] as $step) {
                    $this->line("  • {$step}");
                }
            }
            
            $this->newLine();
            $this->info('The HR agent will now handle the onboarding process automatically.');
            $this->info('You can monitor progress through the workflow system.');
            
            return Command::SUCCESS;
        } else {
            $this->error('✗ Failed to initiate onboarding workflow');
            if (isset($result['error'])) {
                $this->error("Error: {$result['error']}");
            }
            return Command::FAILURE;
        }
    }
}