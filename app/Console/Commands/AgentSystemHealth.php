<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIAgentService;
use Illuminate\Support\Facades\Log;

class AgentSystemHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:health {--detailed : Show detailed health information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health status of the AI agent system';

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
        $this->info('Checking AI Agent System Health...');
        $this->newLine();

        // Perform health check
        $healthStatus = $this->agentService->healthCheck();

        if (isset($healthStatus['status'])) {
            $status = $healthStatus['status'];
            $statusColor = $status === 'healthy' ? 'green' : ($status === 'degraded' ? 'yellow' : 'red');
            
            $this->line("Status: <fg={$statusColor}>{$status}</>");
            
            if (isset($healthStatus['timestamp'])) {
                $this->line("Last Check: {$healthStatus['timestamp']}");
            }
        } else {
            $this->error('Failed to get health status from AI agent system');
            if (isset($healthStatus['error'])) {
                $this->error("Error: {$healthStatus['error']}");
            }
            return Command::FAILURE;
        }

        // Show available agents
        $this->newLine();
        $this->info('Available Agents:');
        
        $agentsStatus = $this->agentService->getAgentsStatus();
        if (isset($agentsStatus['agents'])) {
            foreach ($agentsStatus['agents'] as $agentType => $agentInfo) {
                $available = $agentInfo['available'] ? '✓' : '✗';
                $color = $agentInfo['available'] ? 'green' : 'red';
                $this->line("  <fg={$color}>{$available}</> {$agentType}: {$agentInfo['role']}");
            }
        }

        // Show detailed information if requested
        if ($this->option('detailed')) {
            $this->showDetailedHealth($healthStatus);
        }

        $this->newLine();
        
        if ($healthStatus['status'] === 'healthy') {
            $this->info('✓ AI Agent System is healthy and ready');
            return Command::SUCCESS;
        } else {
            $this->warn('⚠ AI Agent System has issues that may affect performance');
            return Command::FAILURE;
        }
    }

    private function showDetailedHealth($healthStatus)
    {
        $this->newLine();
        $this->info('Detailed Health Information:');
        
        // Show components status if available
        if (isset($healthStatus['components'])) {
            $this->line('Components:');
            foreach ($healthStatus['components'] as $component => $status) {
                $color = $status === 'healthy' ? 'green' : 'red';
                $icon = $status === 'healthy' ? '✓' : '✗';
                $this->line("  <fg={$color}>{$icon}</> {$component}: {$status}");
            }
        }

        // Show system resources if available
        if (isset($healthStatus['system_resources'])) {
            $this->newLine();
            $this->line('System Resources:');
            foreach ($healthStatus['system_resources'] as $resource => $value) {
                $this->line("  {$resource}: {$value}%");
            }
        }

        // Show issues if any
        if (isset($healthStatus['issues']) && !empty($healthStatus['issues'])) {
            $this->newLine();
            $this->error('Issues Detected:');
            foreach ($healthStatus['issues'] as $issue) {
                $this->line("  • {$issue}");
            }
        }
    }
}