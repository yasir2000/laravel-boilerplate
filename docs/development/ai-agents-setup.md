# AI Agents System - Installation & Setup Guide

## Overview

This guide covers the installation, configuration, and setup of the AI Agents system within the Laravel HR Boilerplate. The AI Agents system uses CrewAI framework to provide intelligent automation for HR workflows.

## Prerequisites

### System Requirements
- **PHP**: 8.1 or higher
- **Laravel**: 10.x
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Cache**: Redis 6.0+
- **Node.js**: 16+ (for frontend dashboard)
- **Memory**: Minimum 2GB RAM (4GB+ recommended)
- **Storage**: 1GB+ free space

### Optional Requirements
- **Python**: 3.8+ (for CrewAI integration)
- **Docker**: For containerized deployment
- **ExtJS**: CDN or local installation for dashboard

## Installation Steps

### 1. Clone and Setup Base Laravel Application

```bash
# Clone the repository
git clone https://github.com/yasir2000/laravel-boilerplate.git
cd laravel-boilerplate

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 2. Database Configuration

Configure your database connection in `.env`:

```env
# MySQL Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_boilerplate
DB_USERNAME=your_username
DB_PASSWORD=your_password

# AI Agents Database (SQLite for efficiency)
AI_AGENTS_DB_PATH=database/ai_agents.sqlite
```

### 3. AI Agents Configuration

Add AI Agents configuration to your `.env` file:

```env
# AI Agents System Configuration
AI_AGENTS_ENABLED=true
AI_AGENTS_BASE_URL=http://localhost:8001
AI_AGENTS_TIMEOUT=30
AI_AGENTS_API_TOKEN=your-secure-api-token
AI_AGENTS_LOG_LEVEL=info
AI_AGENTS_DEBUG=false
AI_AGENTS_MOCK=false
AI_AGENTS_TEST_MODE=false

# CrewAI Configuration (if using external CrewAI service)
CREWAI_SERVICE_URL=http://localhost:8002
CREWAI_API_KEY=your-crewai-api-key

# Dashboard Configuration
AI_DASHBOARD_EXTJS_CDN=https://cdn.sencha.com/ext/gpl/7.0.0
AI_DASHBOARD_THEME=triton
```

### 4. Run Database Migrations

```bash
# Run main application migrations
php artisan migrate

# Create AI Agents SQLite database (if using SQLite)
touch database/ai_agents.sqlite

# Seed initial data
php artisan db:seed
```

### 5. Configure Cache and Queue

```bash
# Configure Redis for caching and queues
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Start queue workers
php artisan queue:work --daemon
```

### 6. Install Frontend Dependencies

```bash
# Install Node.js dependencies
npm install

# Build frontend assets
npm run build

# For development
npm run dev
```

## Configuration Details

### AI Agents Configuration File

The main configuration is in `config/ai_agents.php`:

```php
<?php

return [
    // Enable/disable AI agent features
    'enabled' => env('AI_AGENTS_ENABLED', true),

    // Base URL for the AI agent service
    'base_url' => env('AI_AGENTS_BASE_URL', 'http://localhost:8001'),

    // Request timeout in seconds
    'timeout' => env('AI_AGENTS_TIMEOUT', 30),

    // API authentication token
    'api_token' => env('AI_AGENTS_API_TOKEN', ''),

    // Core Agents Configuration
    'core_agents' => [
        'hr_agent' => [
            'name' => 'HR Agent',
            'role' => 'Human Resources Coordinator',
            'goal' => 'Manage employee relations and HR processes',
            'backstory' => 'Experienced HR professional with expertise in employee management',
            'tools' => ['employee_database', 'policy_manager', 'notification_system'],
            'max_execution_time' => 300,
            'max_iter' => 5
        ],
        'project_manager_agent' => [
            'name' => 'Project Manager Agent',
            'role' => 'Project Coordination Specialist',
            'goal' => 'Orchestrate workflows and coordinate tasks between agents',
            'backstory' => 'Skilled project manager with experience in process automation',
            'tools' => ['workflow_engine', 'task_coordinator', 'progress_tracker'],
            'max_execution_time' => 200,
            'max_iter' => 3
        ],
        // ... additional agents
    ],

    // Specialized Agents Configuration
    'specialized_agents' => [
        'it_support_agent' => [
            'name' => 'IT Support Agent',
            'role' => 'System Administration Specialist',
            'specialization' => 'System Administration',
            'tools' => ['system_monitor', 'account_manager', 'equipment_tracker'],
            'queue_size' => 10,
            'processing_timeout' => 180
        ],
        // ... additional specialized agents
    ],

    // Workflow Types Configuration
    'workflows' => [
        'employee_onboarding' => [
            'name' => 'Employee Onboarding',
            'description' => 'Complete new hire onboarding process',
            'agents' => ['hr_agent', 'it_support_agent', 'training_agent', 'compliance_agent'],
            'estimated_duration' => '2-5 business days',
            'steps' => [
                'document_collection',
                'background_verification',
                'it_setup',
                'training_schedule',
                'compliance_check',
                'welcome_package'
            ]
        ],
        // ... additional workflows
    ],

    // Dashboard Configuration
    'dashboard' => [
        'refresh_interval' => 30, // seconds
        'max_activity_items' => 100,
        'enable_real_time' => true,
        'theme' => env('AI_DASHBOARD_THEME', 'triton')
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => true,
        'log_level' => env('AI_AGENTS_LOG_LEVEL', 'info'),
        'log_channel' => 'ai_agents',
        'retain_days' => 30
    ],

    // Testing Configuration
    'testing' => [
        'mock_responses' => env('AI_AGENTS_MOCK', false),
        'debug_mode' => env('AI_AGENTS_DEBUG', false),
        'test_mode' => env('AI_AGENTS_TEST_MODE', false)
    ]
];
```

### Database Configuration

Create AI Agents database tables (optional, for persistent storage):

```bash
# Create migration for AI agents data
php artisan make:migration create_ai_agents_tables

# Example migration content
Schema::create('ai_agent_workflows', function (Blueprint $table) {
    $table->id();
    $table->string('workflow_id')->unique();
    $table->string('workflow_type');
    $table->string('status');
    $table->integer('priority');
    $table->json('metadata');
    $table->json('assigned_agents');
    $table->integer('progress_percentage')->default(0);
    $table->timestamp('estimated_completion')->nullable();
    $table->timestamps();
});

Schema::create('ai_agent_activities', function (Blueprint $table) {
    $table->id();
    $table->string('activity_id')->unique();
    $table->string('agent_id');
    $table->string('agent_name');
    $table->string('action');
    $table->text('description');
    $table->string('workflow_id')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

## Service Setup

### 1. AI Agent Service

Create the main service class:

```php
// app/Services/AIAgentService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIAgentService
{
    private $baseUrl;
    private $timeout;
    private $apiToken;

    public function __construct()
    {
        $this->baseUrl = config('ai_agents.base_url');
        $this->timeout = config('ai_agents.timeout', 30);
        $this->apiToken = config('ai_agents.api_token');
    }

    /**
     * Check if AI agent system is healthy
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiToken)
                ->get($this->baseUrl . '/health');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('AI Agent health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get status of all available agents
     */
    public function getAgentsStatus(): array
    {
        // Implementation details...
    }

    /**
     * Start a new workflow
     */
    public function startWorkflow(string $type, array $params): array
    {
        // Implementation details...
    }
}
```

### 2. Controller Setup

Create the API controller:

```php
// app/Http/Controllers/API/AIAgentsController.php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AIAgentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIAgentsController extends Controller
{
    private AIAgentService $agentService;

    public function __construct(AIAgentService $agentService)
    {
        $this->agentService = $agentService;
    }

    public function getAgentsStatus(): JsonResponse
    {
        try {
            $status = $this->agentService->getAgentsStatus();
            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve agents status'
            ], 500);
        }
    }

    // Additional controller methods...
}
```

### 3. Routes Configuration

Add routes in `routes/api.php`:

```php
// AI Agents API Routes
Route::middleware(['auth:sanctum'])->prefix('ai-agents')->group(function () {
    Route::get('/agents/status', [AIAgentsController::class, 'getAgentsStatus']);
    Route::get('/system/health', [AIAgentsController::class, 'getSystemHealth']);
    Route::post('/workflows/start', [AIAgentsController::class, 'startWorkflow']);
    Route::get('/workflows/active', [AIAgentsController::class, 'getActiveWorkflows']);
    Route::post('/queries/process', [AIAgentsController::class, 'processEmployeeQuery']);
});

// Test routes (no authentication required)
Route::prefix('test')->group(function () {
    Route::get('/agents/status', [AIAgentsController::class, 'getTestAgentsStatus']);
    Route::get('/dashboard', function() {
        return view('ai-agents.dashboard');
    });
});
```

## Dashboard Setup

### 1. Blade Template

Create the dashboard template at `resources/views/ai-agents/dashboard.blade.php`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }} - AI Agents Dashboard</title>
    
    <!-- ExtJS CDN -->
    <link rel="stylesheet" type="text/css" href="{{ config('ai_agents.dashboard.extjs_cdn') }}/classic/theme-triton/resources/theme-triton-all.css">
    <script type="text/javascript" src="{{ config('ai_agents.dashboard.extjs_cdn') }}/ext-all.js"></script>
    <script type="text/javascript" src="{{ config('ai_agents.dashboard.extjs_cdn') }}/classic/theme-triton/theme-triton.js"></script>
    
    <!-- Dashboard CSS -->
    <style>
        /* Dashboard styles */
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .loading-container { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background-color: rgba(255, 255, 255, 0.9);
            display: flex; justify-content: center; align-items: center; z-index: 10000;
        }
    </style>
</head>
<body>
    <!-- Loading overlay -->
    <div id="loading-overlay" class="loading-container">
        <div class="loading-spinner"></div>
    </div>

    <!-- ExtJS viewport container -->
    <div id="ai-agents-viewport"></div>

    <script>
        // Dashboard initialization code
        Ext.onReady(function() {
            // Create viewport and initialize dashboard
        });
    </script>
</body>
</html>
```

### 2. JavaScript Components

Create ExtJS components for the dashboard:

```javascript
// public/js/agents/AgentsDashboard.js
Ext.define('AI.view.AgentsDashboard', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.agentsdashboard',

    title: 'AI Agents Dashboard',
    layout: 'border',

    initComponent: function() {
        // Dashboard component initialization
        this.callParent();
    }
});
```

## Console Commands

Create Artisan commands for system management:

```php
// app/Console/Commands/AgentSystemHealth.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIAgentService;

class AgentSystemHealth extends Command
{
    protected $signature = 'ai-agents:health-check';
    protected $description = 'Check the health status of the AI agent system';

    public function handle(AIAgentService $agentService)
    {
        $this->info('Checking AI Agent System Health...');
        
        if ($agentService->isHealthy()) {
            $this->info('✓ AI Agent System is healthy and ready');
        } else {
            $this->error('✗ AI Agent System has issues');
        }
    }
}
```

Register commands in `app/Console/Kernel.php`:

```php
protected $commands = [
    \App\Console\Commands\AgentSystemHealth::class,
    \App\Console\Commands\TriggerEmployeeOnboarding::class,
];
```

## Testing Setup

### 1. Feature Tests

Create tests for AI Agents functionality:

```php
// tests/Feature/AIAgentsTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AIAgentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_agents_status_endpoint()
    {
        $response = $this->get('/api/test/agents/status');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'core_agents',
                     'specialized_agents'
                 ]);
    }

    public function test_dashboard_loads()
    {
        $response = $this->get('/api/test/dashboard');
        $response->assertStatus(200)
                 ->assertSee('AI Agents Dashboard');
    }
}
```

### 2. Unit Tests

```php
// tests/Unit/AIAgentServiceTest.php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AIAgentService;

class AIAgentServiceTest extends TestCase
{
    public function test_health_check_returns_boolean()
    {
        $service = new AIAgentService();
        $result = $service->isHealthy();
        $this->assertIsBool($result);
    }
}
```

## Deployment

### 1. Production Environment

Set production environment variables:

```env
AI_AGENTS_ENABLED=true
AI_AGENTS_BASE_URL=https://your-agents-service.com
AI_AGENTS_API_TOKEN=your-production-token
AI_AGENTS_LOG_LEVEL=warning
AI_AGENTS_DEBUG=false
AI_AGENTS_MOCK=false
```

### 2. Docker Deployment

Create Docker configuration:

```dockerfile
# Dockerfile for AI Agents
FROM php:8.2-fpm

# Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]
```

### 3. Docker Compose

```yaml
# docker-compose.yml
version: '3.8'
services:
  app:
    build: .
    container_name: laravel-ai-agents
    volumes:
      - .:/var/www
    environment:
      - AI_AGENTS_ENABLED=true
      - AI_AGENTS_BASE_URL=http://ai-agents-service:8001

  ai-agents-service:
    image: your-ai-agents-image
    container_name: ai-agents-service
    ports:
      - "8001:8000"
    environment:
      - CREWAI_CONFIG_PATH=/app/config
```

## Monitoring and Maintenance

### 1. Health Monitoring

Set up automated health checks:

```bash
# Add to crontab
*/5 * * * * cd /path/to/laravel && php artisan ai-agents:health-check >> /var/log/ai-agents-health.log 2>&1
```

### 2. Log Rotation

Configure log rotation for AI Agents logs:

```bash
# /etc/logrotate.d/ai-agents
/var/log/ai-agents*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    sharedscripts
}
```

### 3. Performance Monitoring

Monitor key metrics:
- Agent response times
- Workflow completion rates
- System resource usage
- Error rates and patterns

## Troubleshooting

### Common Issues

1. **Agents not responding**
   - Check AI_AGENTS_BASE_URL is accessible
   - Verify API token is correct
   - Check network connectivity

2. **Dashboard not loading**
   - Verify ExtJS CDN is accessible
   - Check browser console for JavaScript errors
   - Ensure proper file permissions

3. **Workflows failing**
   - Check agent system health
   - Verify database connectivity
   - Review application logs

### Debug Mode

Enable debug mode for troubleshooting:

```env
AI_AGENTS_DEBUG=true
AI_AGENTS_LOG_LEVEL=debug
```

### Log Analysis

Check logs for issues:

```bash
# View AI Agents logs
tail -f storage/logs/ai-agents.log

# View Laravel logs
tail -f storage/logs/laravel.log
```

## Security Considerations

### 1. API Security
- Use strong API tokens
- Implement rate limiting
- Enable HTTPS in production
- Regular security audits

### 2. Data Protection
- Encrypt sensitive agent data
- Implement proper access controls
- Regular security updates
- Audit trail maintenance

### 3. Network Security
- Firewall configuration
- VPN for agent communication
- Secure API endpoints
- Regular penetration testing

## Support and Documentation

### Getting Help
- Check the documentation: `/docs/ai-agents-user-guide.md`
- Review API documentation: `/docs/api/ai-agents-api.md`
- Contact support: AI-Agents-Support@yourcompany.com

### Contributing
- Follow coding standards
- Write comprehensive tests
- Update documentation
- Submit pull requests

This completes the installation and setup guide for the AI Agents system. Follow these steps carefully to ensure a successful deployment.