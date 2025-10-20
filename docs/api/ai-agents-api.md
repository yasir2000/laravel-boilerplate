# AI Agents API Documentation

## Overview

The AI Agents API provides comprehensive endpoints for managing and monitoring the CrewAI-powered HR automation system. The API is built on Laravel 10 with Laravel Sanctum authentication and follows RESTful principles.

## Base URL

```
Production: https://your-domain.com/api
Development: http://localhost:8000/api
```

## Authentication

### Production Endpoints
All production AI Agents endpoints require authentication using Laravel Sanctum tokens:

```bash
Authorization: Bearer {your-api-token}
```

### Test Endpoints
Test endpoints are available without authentication for development and testing purposes:

```bash
/api/test/*
```

## Core Endpoints

### Agent Management

#### Get Agents Status
```http
GET /api/ai-agents/agents/status
```

**Description**: Retrieve the current status of all 12 AI agents including load, active tasks, and health indicators.

**Response**:
```json
{
  "success": true,
  "core_agents": [
    {
      "id": "hr_001",
      "name": "HR Agent",
      "type": "hr_agent",
      "status": "active",
      "active_tasks": 3,
      "load_percentage": 45,
      "iconCls": "fa-users",
      "last_activity": "2025-10-20T11:00:34.990634Z"
    }
  ],
  "specialized_agents": [
    {
      "id": "it_001",
      "name": "IT Support Agent",
      "type": "it_support_agent",
      "status": "active",
      "queue_size": 4,
      "completed_today": 12,
      "iconCls": "fa-laptop",
      "specialization": "System Administration"
    }
  ],
  "last_updated": "2025-10-20T11:00:34Z"
}
```

### System Health

#### Get System Health
```http
GET /api/ai-agents/system/health
```

**Description**: Get comprehensive system health metrics and status information.

**Response**:
```json
{
  "success": true,
  "health_data": {
    "overall_health": "excellent",
    "health_percentage": 95,
    "last_check": "2025-10-20T11:00:34Z",
    "components": {
      "agents": {
        "status": "healthy",
        "active_count": 12,
        "total_count": 12
      },
      "workflows": {
        "status": "healthy",
        "active_count": 5,
        "error_count": 0
      },
      "system": {
        "status": "healthy",
        "cpu_usage": 45,
        "memory_usage": 67,
        "disk_usage": 23
      }
    }
  }
}
```

#### Run Health Check
```http
POST /api/ai-agents/system/health-check
```

**Description**: Perform a comprehensive system health check on all agents and components.

**Response**:
```json
{
  "success": true,
  "message": "Health check completed successfully",
  "results": {
    "overall_score": 95,
    "checked_at": "2025-10-20T11:00:34Z",
    "issues_found": 0,
    "recommendations": []
  }
}
```

#### Emergency Shutdown
```http
POST /api/ai-agents/system/emergency-shutdown
```

**Description**: Perform emergency shutdown of all AI agents and workflows.

**Request Body**:
```json
{
  "reason": "System maintenance",
  "immediate": true
}
```

**Response**:
```json
{
  "success": true,
  "message": "Emergency shutdown initiated",
  "shutdown_id": "emergency_2025102011003",
  "affected_workflows": 5,
  "estimated_downtime": "15 minutes"
}
```

### Workflow Management

#### Get Active Workflows
```http
GET /api/ai-agents/workflows/active
```

**Description**: Retrieve all currently active workflows with their status and progress.

**Response**:
```json
{
  "success": true,
  "workflows": [
    {
      "id": "wf_001",
      "type": "employee_onboarding",
      "status": "in_progress",
      "priority": "high",
      "progress_percentage": 75,
      "assigned_agents": ["hr_001", "it_001", "training_001"],
      "created_at": "2025-10-20T09:00:00Z",
      "estimated_completion": "2025-10-22T17:00:00Z",
      "metadata": {
        "employee_id": 123,
        "department": "Engineering"
      }
    }
  ],
  "total_count": 5,
  "last_updated": "2025-10-20T11:00:34Z"
}
```

#### Start Workflow
```http
POST /api/ai-agents/workflows/start
```

**Description**: Initiate a new workflow with specified parameters.

**Request Body**:
```json
{
  "workflow_type": "employee_onboarding",
  "employee_id": 123,
  "department": "Engineering",
  "priority": "high",
  "metadata": {
    "start_date": "2025-10-21",
    "manager_id": 456,
    "equipment_needed": ["laptop", "access_card"]
  }
}
```

**Response**:
```json
{
  "success": true,
  "workflow_id": "wf_002",
  "message": "Workflow started successfully",
  "status": "initiated",
  "assigned_agents": ["hr_001", "it_001"],
  "estimated_completion": "2025-10-23T17:00:00Z"
}
```

#### Pause Workflow
```http
POST /api/ai-agents/workflows/{workflowId}/pause
```

**Description**: Pause an active workflow execution.

**Request Body**:
```json
{
  "reason": "Waiting for additional information",
  "pause_duration": "24h"
}
```

**Response**:
```json
{
  "success": true,
  "workflow_id": "wf_001",
  "status": "paused",
  "message": "Workflow paused successfully",
  "resume_at": "2025-10-21T11:00:34Z"
}
```

#### Get Workflow Details
```http
GET /api/ai-agents/workflows/{workflowId}/details
```

**Description**: Get detailed information about a specific workflow including steps and progress.

**Response**:
```json
{
  "success": true,
  "workflow": {
    "id": "wf_001",
    "type": "employee_onboarding",
    "status": "in_progress",
    "progress_percentage": 75,
    "steps": [
      {
        "step_id": "step_001",
        "name": "Document Collection",
        "status": "completed",
        "agent": "hr_001",
        "completed_at": "2025-10-20T10:00:00Z"
      },
      {
        "step_id": "step_002",
        "name": "IT Setup",
        "status": "in_progress",
        "agent": "it_001",
        "started_at": "2025-10-20T10:30:00Z"
      }
    ],
    "metadata": {
      "employee_id": 123,
      "department": "Engineering"
    }
  }
}
```

### Activity Monitoring

#### Get Activity Feed
```http
GET /api/ai-agents/activity/feed
```

**Description**: Retrieve real-time activity feed from all agents.

**Query Parameters**:
- `limit` (optional): Number of activities to return (default: 50)
- `agent_id` (optional): Filter by specific agent
- `since` (optional): ISO timestamp to get activities since

**Response**:
```json
{
  "success": true,
  "activities": [
    {
      "id": "activity_001",
      "agent_id": "hr_001",
      "agent_name": "HR Agent",
      "action": "task_completed",
      "description": "Completed document verification for employee onboarding",
      "workflow_id": "wf_001",
      "timestamp": "2025-10-20T11:00:34Z",
      "metadata": {
        "task_duration": "15 minutes",
        "success": true
      }
    }
  ],
  "total_count": 150,
  "has_more": true
}
```

### Employee Query Processing

#### Process Employee Query
```http
POST /api/ai-agents/queries/process
```

**Description**: Process natural language queries from employees using AI agents.

**Request Body**:
```json
{
  "query": "How do I request time off for next week?",
  "employee_id": 123,
  "priority": "medium",
  "context": {
    "department": "Engineering",
    "manager_id": 456
  }
}
```

**Response**:
```json
{
  "success": true,
  "query_id": "query_001",
  "status": "processed",
  "response": {
    "answer": "To request time off for next week, please follow these steps: 1. Log into the HR portal, 2. Navigate to 'Leave Requests', 3. Select your leave type and dates, 4. Submit for manager approval.",
    "confidence": 0.95,
    "sources": ["hr_policies", "leave_management_guide"],
    "follow_up_actions": [
      {
        "action": "create_leave_request",
        "url": "/hr/leave-requests/create"
      }
    ]
  },
  "processing_time": "2.3 seconds",
  "assigned_agent": "hr_001"
}
```

## Test Endpoints (No Authentication Required)

### Test Agent Status
```http
GET /api/test/agents/status
```

Returns mock agent status data for testing purposes.

### Test System Health
```http
GET /api/test/system/health
```

Returns mock system health data for testing purposes.

### Test Active Workflows
```http
GET /api/test/workflows/active
```

Returns mock active workflows data for testing purposes.

### Test Dashboard
```http
GET /api/test/dashboard
```

Returns the AI Agents dashboard view for testing purposes.

## Error Handling

### Standard Error Response
```json
{
  "success": false,
  "error": {
    "code": "AGENT_UNAVAILABLE",
    "message": "The requested agent is currently unavailable",
    "details": "HR Agent (hr_001) is undergoing maintenance"
  },
  "timestamp": "2025-10-20T11:00:34Z"
}
```

### Common Error Codes
- `AGENT_UNAVAILABLE`: Agent is offline or under maintenance
- `WORKFLOW_NOT_FOUND`: Specified workflow ID does not exist
- `INVALID_WORKFLOW_TYPE`: Unsupported workflow type requested
- `INSUFFICIENT_PERMISSIONS`: User lacks required permissions
- `SYSTEM_OVERLOADED`: System is at capacity, try again later
- `VALIDATION_ERROR`: Request data validation failed

## Rate Limiting

### Production Limits
- **Authenticated requests**: 1000 requests per hour per user
- **System health checks**: 60 requests per hour per user
- **Workflow operations**: 100 requests per hour per user

### Test Endpoints
- **Test endpoints**: 300 requests per hour per IP

## Webhooks

### Workflow Events
Register webhooks to receive real-time notifications about workflow events:

```json
{
  "event": "workflow.completed",
  "workflow_id": "wf_001",
  "workflow_type": "employee_onboarding",
  "timestamp": "2025-10-20T11:00:34Z",
  "data": {
    "employee_id": 123,
    "completion_status": "success",
    "duration": "2.5 hours"
  }
}
```

### Agent Events
Receive notifications about agent status changes:

```json
{
  "event": "agent.status_changed",
  "agent_id": "hr_001",
  "previous_status": "active",
  "new_status": "maintenance",
  "timestamp": "2025-10-20T11:00:34Z",
  "reason": "Scheduled maintenance update"
}
```

## SDK Examples

### JavaScript/Node.js
```javascript
const axios = require('axios');

class AIAgentsAPI {
  constructor(baseURL, token) {
    this.client = axios.create({
      baseURL,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
  }

  async getAgentsStatus() {
    const response = await this.client.get('/ai-agents/agents/status');
    return response.data;
  }

  async startWorkflow(workflowData) {
    const response = await this.client.post('/ai-agents/workflows/start', workflowData);
    return response.data;
  }

  async processQuery(query, employeeId) {
    const response = await this.client.post('/ai-agents/queries/process', {
      query,
      employee_id: employeeId,
      priority: 'medium'
    });
    return response.data;
  }
}

// Usage
const api = new AIAgentsAPI('http://localhost:8000/api', 'your-token');
const status = await api.getAgentsStatus();
console.log('Agents Status:', status);
```

### PHP/Laravel
```php
use Illuminate\Support\Facades\Http;

class AIAgentsService
{
    private $baseUrl;
    private $token;

    public function __construct($baseUrl, $token)
    {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
    }

    public function getAgentsStatus()
    {
        $response = Http::withToken($this->token)
            ->get($this->baseUrl . '/ai-agents/agents/status');
        
        return $response->json();
    }

    public function startWorkflow($workflowData)
    {
        $response = Http::withToken($this->token)
            ->post($this->baseUrl . '/ai-agents/workflows/start', $workflowData);
        
        return $response->json();
    }
}

// Usage
$api = new AIAgentsService('http://localhost:8000/api', 'your-token');
$status = $api->getAgentsStatus();
```

### Python
```python
import requests

class AIAgentsAPI:
    def __init__(self, base_url, token):
        self.base_url = base_url
        self.headers = {
            'Authorization': f'Bearer {token}',
            'Content-Type': 'application/json'
        }

    def get_agents_status(self):
        response = requests.get(
            f'{self.base_url}/ai-agents/agents/status',
            headers=self.headers
        )
        return response.json()

    def start_workflow(self, workflow_data):
        response = requests.post(
            f'{self.base_url}/ai-agents/workflows/start',
            json=workflow_data,
            headers=self.headers
        )
        return response.json()

# Usage
api = AIAgentsAPI('http://localhost:8000/api', 'your-token')
status = api.get_agents_status()
print('Agents Status:', status)
```

## Testing

### Unit Tests
```bash
# Run AI Agents API tests
php artisan test --testsuite=Feature --filter=AIAgents

# Run specific test
php artisan test tests/Feature/AIAgentsControllerTest.php
```

### Integration Tests
```bash
# Test full workflow execution
php artisan ai-agents:test-workflow employee_onboarding

# Test system health
php artisan ai-agents:health-check
```

### Load Testing
```bash
# Apache Bench example
ab -n 1000 -c 10 -H "Authorization: Bearer your-token" \
   http://localhost:8000/api/ai-agents/agents/status
```

## Deployment Considerations

### Production Setup
1. Configure proper rate limiting
2. Set up monitoring and alerting
3. Implement proper logging and audit trails
4. Configure webhook endpoints for real-time updates
5. Set up SSL certificates for secure communication

### Environment Variables
```bash
AI_AGENTS_ENABLED=true
AI_AGENTS_BASE_URL=http://localhost:8001
AI_AGENTS_TIMEOUT=30
AI_AGENTS_API_TOKEN=your-secure-token
AI_AGENTS_LOG_LEVEL=info
AI_AGENTS_DEBUG=false
```

### Security Best Practices
1. Use strong API tokens with proper expiration
2. Implement IP whitelisting for production endpoints
3. Enable request logging and monitoring
4. Regular security audits and penetration testing
5. Proper input validation and sanitization