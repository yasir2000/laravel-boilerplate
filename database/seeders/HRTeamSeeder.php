<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class HRTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample teams
        $teams = [
            [
                'name' => 'Development Team Alpha',
                'description' => 'Frontend and backend development for the main product',
                'department' => 'Engineering',
                'team_type' => 'project',
                'status' => 'active',
                'icon' => '🚀',
                'performance_score' => 92,
                'goals' => [
                    'Complete product MVP by Q2',
                    'Implement CI/CD pipeline',
                    'Achieve 95% code coverage'
                ]
            ],
            [
                'name' => 'Marketing Innovators',
                'description' => 'Digital marketing campaigns and brand strategy',
                'department' => 'Marketing',
                'team_type' => 'permanent',
                'status' => 'active',
                'icon' => '🎯',
                'performance_score' => 87,
                'goals' => [
                    'Increase brand awareness by 40%',
                    'Launch new product campaign',
                    'Improve social media engagement'
                ]
            ],
            [
                'name' => 'Sales Champions',
                'description' => 'Enterprise sales and customer relationship management',
                'department' => 'Sales',
                'team_type' => 'permanent',
                'status' => 'active',
                'icon' => '💎',
                'performance_score' => 95,
                'goals' => [
                    'Achieve 120% of quarterly target',
                    'Onboard 25 new enterprise clients',
                    'Improve customer retention rate'
                ]
            ],
            [
                'name' => 'Design Studio',
                'description' => 'Product design and user experience optimization',
                'department' => 'Design',
                'team_type' => 'cross-functional',
                'status' => 'active',
                'icon' => '🌟',
                'performance_score' => 89,
                'goals' => [
                    'Redesign mobile app interface',
                    'Conduct user research studies',
                    'Create design system v2.0'
                ]
            ],
            [
                'name' => 'Operations Excellence',
                'description' => 'Process optimization and operational efficiency',
                'department' => 'Operations',
                'team_type' => 'task-force',
                'status' => 'on-hold',
                'icon' => '⚡',
                'performance_score' => 75,
                'goals' => [
                    'Streamline onboarding process',
                    'Implement automation tools',
                    'Reduce operational costs by 15%'
                ]
            ],
            [
                'name' => 'Data Analytics Squad',
                'description' => 'Business intelligence and data-driven insights',
                'department' => 'Engineering',
                'team_type' => 'project',
                'status' => 'completed',
                'icon' => '📊',
                'performance_score' => 96,
                'goals' => [
                    'Build data warehouse',
                    'Create executive dashboards',
                    'Implement predictive analytics'
                ]
            ],
            [
                'name' => 'Customer Success Heroes',
                'description' => 'Customer support and success management',
                'department' => 'Customer Success',
                'team_type' => 'permanent',
                'status' => 'active',
                'icon' => '🛡️',
                'performance_score' => 91,
                'goals' => [
                    'Achieve 98% customer satisfaction',
                    'Reduce response time to 2 hours',
                    'Launch customer education program'
                ]
            ],
            [
                'name' => 'Innovation Lab',
                'description' => 'Research and development of new technologies',
                'department' => 'R&D',
                'team_type' => 'working-group',
                'status' => 'forming',
                'icon' => '🔬',
                'performance_score' => 0,
                'goals' => [
                    'Explore AI/ML applications',
                    'Prototype new product features',
                    'Research emerging technologies'
                ]
            ]
        ];

        foreach ($teams as $teamData) {
            $team = Team::create($teamData);

            // Assign random team lead (for demo purposes)
            $randomLead = User::inRandomOrder()->first();
            if ($randomLead) {
                $team->update(['team_lead_id' => $randomLead->id]);
                
                // Add the lead as a team member with 'lead' role
                $team->addMember($randomLead, 'lead');
            }

            // Add random team members (2-5 members per team)
            $memberCount = rand(2, 5);
            $randomMembers = User::where('id', '!=', $randomLead?->id ?? 0)
                                ->inRandomOrder()
                                ->take($memberCount)
                                ->get();

            foreach ($randomMembers as $member) {
                $roles = ['senior-member', 'member', 'junior-member', 'specialist'];
                $team->addMember($member, $roles[array_rand($roles)]);
            }
        }

        $this->command->info('HR Teams seeded successfully!');
    }
}