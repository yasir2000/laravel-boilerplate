<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();

        foreach ($projects as $project) {
            $employees = $project->company->users()->role('employee')->get();
            $creator = $project->owner;

            if ($employees->isEmpty()) {
                continue;
            }

            // Create tasks for each project
            $tasks = [
                [
                    'title' => 'Project Planning and Analysis',
                    'description' => 'Analyze requirements and create project plan',
                    'status' => 'completed',
                    'priority' => 'high',
                    'due_date' => now()->subDays(20),
                    'completed_at' => now()->subDays(18),
                    'estimated_hours' => 16.0,
                    'actual_hours' => 18.0,
                ],
                [
                    'title' => 'UI/UX Design',
                    'description' => 'Create mockups and design prototypes',
                    'status' => 'in_progress',
                    'priority' => 'high',
                    'due_date' => now()->addDays(5),
                    'estimated_hours' => 24.0,
                    'actual_hours' => 12.0,
                ],
                [
                    'title' => 'Backend Development',
                    'description' => 'Develop backend API and database structure',
                    'status' => 'todo',
                    'priority' => 'medium',
                    'due_date' => now()->addDays(15),
                    'estimated_hours' => 40.0,
                ],
                [
                    'title' => 'Frontend Implementation',
                    'description' => 'Implement frontend based on designs',
                    'status' => 'todo',
                    'priority' => 'medium',
                    'due_date' => now()->addDays(25),
                    'estimated_hours' => 32.0,
                ],
                [
                    'title' => 'Testing and QA',
                    'description' => 'Perform comprehensive testing and quality assurance',
                    'status' => 'todo',
                    'priority' => 'high',
                    'due_date' => now()->addDays(35),
                    'estimated_hours' => 20.0,
                ],
                [
                    'title' => 'Documentation',
                    'description' => 'Create technical and user documentation',
                    'status' => 'todo',
                    'priority' => 'low',
                    'due_date' => now()->addDays(40),
                    'estimated_hours' => 8.0,
                ],
            ];

            foreach ($tasks as $index => $taskData) {
                $assignedUser = $employees->random();
                
                Task::create(array_merge($taskData, [
                    'project_id' => $project->id,
                    'assigned_to' => $assignedUser->id,
                    'created_by' => $creator->id,
                ]));
            }

            // Create some overdue tasks for demonstration
            if ($project->status === 'in_progress') {
                Task::create([
                    'title' => 'Urgent Bug Fix',
                    'description' => 'Fix critical bug in production system',
                    'status' => 'todo',
                    'priority' => 'urgent',
                    'due_date' => now()->subDays(2),
                    'estimated_hours' => 4.0,
                    'project_id' => $project->id,
                    'assigned_to' => $employees->random()->id,
                    'created_by' => $creator->id,
                ]);

                Task::create([
                    'title' => 'Performance Optimization',
                    'description' => 'Optimize database queries and improve performance',
                    'status' => 'review',
                    'priority' => 'medium',
                    'due_date' => now()->subDays(1),
                    'estimated_hours' => 6.0,
                    'actual_hours' => 7.0,
                    'project_id' => $project->id,
                    'assigned_to' => $employees->random()->id,
                    'created_by' => $creator->id,
                ]);
            }
        }
    }
}
