# AI Agents Dashboard - User Guide

## Overview

The AI Agents Dashboard provides a comprehensive interface for managing and monitoring the CrewAI-powered HR automation system. This system includes **12 specialized AI agents** working together to automate various HR workflows with intelligent coordination and real-time monitoring capabilities.

## System Architecture

### Core Agents (6)
1. **HR Agent** (`hr_001`) - Main coordinator for HR-related tasks and employee interactions
2. **Project Manager Agent** (`pm_001`) - Manages workflow orchestration and task coordination
3. **Analytics Agent** (`analytics_001`) - Provides insights, reporting, and data analysis
4. **Workflow Engine Agent** (`workflow_001`) - Handles workflow execution and state management
5. **Integration Agent** (`integration_001`) - Manages system integrations and data flow
6. **Notification Agent** (`notification_001`) - Handles all communication and notifications

### Specialized Agents (6)
1. **IT Support Agent** (`it_001`) - System administration and technical setup
2. **Compliance Agent** (`compliance_001`) - Ensures regulatory compliance and policy adherence
3. **Training Agent** (`training_001`) - Employee development and training coordination
4. **Payroll Agent** (`payroll_001`) - Payroll processing and exception handling
5. **Leave Processing Agent** (`leave_001`) - Leave request management and approval workflows
6. **Coverage Agent** (`coverage_001`) - Staff coverage and scheduling optimization

## Available Workflows

### 1. Employee Onboarding
- **Purpose**: Automate the complete new hire process from offer acceptance to first day
- **Agents Involved**: HR Agent, IT Support Agent, Training Agent, Compliance Agent
- **Duration**: 2-5 business days
- **Features**: 
  - Document collection and verification
  - IT equipment setup and account creation
  - Training program scheduling
  - Compliance background checks
  - Welcome package preparation

### 2. Leave Management
- **Purpose**: Process leave requests with intelligent approval and coverage planning
- **Agents Involved**: Leave Processing Agent, Coverage Agent, HR Agent, Notification Agent
- **Duration**: 1-3 business days
- **Features**: 
  - Automated approval workflows based on company policies
  - Intelligent coverage planning and staff scheduling
  - Calendar integration and conflict resolution
  - Manager and team notifications

### 3. Performance Reviews
- **Purpose**: Orchestrate comprehensive performance evaluation processes
- **Agents Involved**: HR Agent, Analytics Agent, Notification Agent
- **Duration**: 2-4 weeks
- **Features**: 
  - Review cycle scheduling and reminders
  - 360-degree feedback collection
  - Goal setting and progress tracking
  - Performance analytics and insights

### 4. Payroll Exception Handling
- **Purpose**: Identify and resolve payroll discrepancies automatically
- **Agents Involved**: Payroll Agent, HR Agent, Compliance Agent, Analytics Agent
- **Duration**: 1-2 business days
- **Features**: 
  - Exception detection and classification
  - Automated resolution for common issues
  - Escalation workflows for complex cases
  - Audit trail and compliance reporting

### 5. Employee Query Resolution
- **Purpose**: Intelligent handling of employee questions and requests using NLP
- **Agents Involved**: HR Agent, relevant specialist agents based on query type
- **Duration**: 30 minutes - 2 hours
- **Features**: 
  - Natural language processing and intent recognition
  - Knowledge base search and information retrieval
  - Smart routing to appropriate specialist agents
  - Escalation to human HR when needed

### 6. Recruitment Automation
- **Purpose**: Streamline hiring processes from job posting to offer
- **Agents Involved**: HR Agent, Analytics Agent, Compliance Agent
- **Duration**: 2-6 weeks
- **Features**: 
  - Candidate screening and qualification assessment
  - Interview scheduling and coordination
  - Reference checking automation
  - Offer generation and negotiation support

### 7. Compliance Monitoring
- **Purpose**: Continuous compliance assessment and regulatory reporting
- **Agents Involved**: Compliance Agent, Analytics Agent, Notification Agent
- **Duration**: Ongoing/Real-time
- **Features**: 
  - Regulatory requirement tracking
  - Policy compliance assessment
  - Risk identification and mitigation
  - Automated compliance reporting

### 8. Training Coordination
- **Purpose**: Manage employee development programs and skill building
- **Agents Involved**: Training Agent, HR Agent, Analytics Agent
- **Duration**: Ongoing/Program-based
- **Features**: 
  - Training needs assessment
  - Program scheduling and resource allocation
  - Progress tracking and certification management
  - Skills gap analysis and recommendations

## Dashboard Features

### Agent Status Monitoring
- **Real-time Status**: Live monitoring of all 12 agents with health indicators
- **Load Monitoring**: Visual load percentages with color-coded performance metrics
- **Health Indicators**: System-wide health scoring with detailed breakdowns
- **Activity Feed**: Live stream of agent activities and task completions
- **Performance Metrics**: Response times, task completion rates, error tracking

### Workflow Management
- **Active Workflows**: Real-time monitoring of all running workflows with progress indicators
- **Progress Tracking**: Visual timeline with completion percentages and milestone tracking
- **Priority Management**: Dynamic priority adjustment and resource allocation
- **Pause/Resume Controls**: Emergency controls for workflow management
- **Queue Management**: View and manage workflow queues across all agents

### System Health Dashboard
- **Overall Health Score**: Comprehensive system health percentage with trend analysis
- **Performance Metrics**: Response times, memory usage, throughput measurements
- **Error Monitoring**: Real-time error detection with automatic alerting
- **Emergency Controls**: System-wide emergency shutdown and restart capabilities
- **Resource Utilization**: CPU, memory, and network usage monitoring

### Analytics Dashboard
- **Workflow Statistics**: Success rates, completion times, efficiency metrics
- **Agent Performance**: Individual agent KPIs and performance comparisons
- **Trend Analysis**: Historical data visualization and predictive analytics
- **Custom Reports**: Generate detailed reports for management and compliance
- **Business Intelligence**: HR metrics integration and insights generation

## How to Use

### Accessing the Dashboard

1. **Login Required**: Navigate to `/ai-agents` (requires authentication)
2. **Test Access**: Use `/api/test/dashboard` for testing without authentication
3. **API Access**: Direct API access via `/api/ai-agents/*` endpoints

### Starting a New Workflow

1. **Access Dashboard**: Navigate to the AI Agents dashboard from the main menu
2. **Choose Workflow**: Click "Start New Workflow" and select the appropriate type
3. **Configure Parameters**: Fill in required information (employee ID, department, priority)
4. **Set Priority**: Choose from Low, Medium, High, or Urgent priority levels
5. **Add Metadata**: Include any additional context or special requirements
6. **Monitor Progress**: Track workflow execution through the dashboard

### Monitoring Agent Performance

1. **Agent Cards**: View individual agent status, load, and active tasks
2. **Health Indicators**: Monitor system health with color-coded status indicators
3. **Activity Feed**: Track real-time agent activities and task completions
4. **Performance Charts**: Analyze trends and identify performance bottlenecks

### Managing Workflows

1. **Active Workflows Tab**: View all currently running workflows
2. **Workflow Details**: Click on any workflow to see detailed progress information
3. **Pause/Resume**: Use controls to manage workflow execution
4. **Priority Adjustment**: Modify workflow priorities based on business needs
5. **Emergency Actions**: Use emergency controls when immediate intervention is needed

## API Integration

### Authentication
All production API endpoints require Laravel Sanctum authentication:
```bash
Authorization: Bearer {your-api-token}
```

### Key Endpoints
- **Agent Status**: `GET /api/ai-agents/agents/status`
- **System Health**: `GET /api/ai-agents/system/health`
- **Start Workflow**: `POST /api/ai-agents/workflows/start`
- **Active Workflows**: `GET /api/ai-agents/workflows/active`
- **Process Query**: `POST /api/ai-agents/queries/process`

### Test Endpoints (No Auth Required)
- **Test Dashboard**: `GET /api/test/dashboard`
- **Test Agents**: `GET /api/test/agents/status`
- **Test Health**: `GET /api/test/system/health`

## Troubleshooting

### Common Issues
1. **Agents Not Responding**: Check system health and restart if necessary
2. **Workflow Stuck**: Use pause/resume controls or emergency shutdown
3. **Dashboard Not Loading**: Verify ExtJS CDN availability and check browser console
4. **API Errors**: Ensure proper authentication and check network connectivity

### Emergency Procedures
1. **Emergency Shutdown**: Use the emergency shutdown button in the dashboard
2. **System Restart**: Contact system administrator for full system restart
3. **Data Recovery**: Check audit logs and activity feed for recent actions
4. **Escalation**: Contact IT support for critical system issues

## Best Practices

### Workflow Management
- Monitor workflow queues regularly to prevent bottlenecks
- Set appropriate priorities based on business impact
- Use metadata fields to provide context for better agent decision-making
- Review completed workflows for process improvement opportunities

### Performance Optimization
- Schedule resource-intensive workflows during off-peak hours
- Monitor agent load distribution and balance workloads
- Regular system health checks and maintenance
- Keep agents updated with latest configuration and training data

### Security & Compliance
- Regular audit of agent activities and decisions
- Ensure proper access controls and authentication
- Monitor compliance workflows for regulatory adherence
- Maintain audit trails for all automated actions
3. **Configure Parameters**: Fill in required information (employee, priority, etc.)
4. **Initiate**: Click "Start Workflow" to begin the process
5. **Monitor Progress**: Track progress in the Active Workflows panel

### Monitoring Agent Performance

1. **Agent Status Panel**: View real-time status of all agents
2. **Load Indicators**: Monitor current task loads (color-coded)
3. **Health Status**: Check for any agents experiencing issues
4. **Activity Feed**: Review recent agent activities and completions

### Managing Workflows

1. **Active Workflows Grid**: View all currently running workflows
2. **Progress Monitoring**: Check completion percentages and current steps
3. **Priority Adjustment**: Modify workflow priorities as needed
4. **Pause/Resume**: Control workflow execution when necessary
5. **Details View**: Click on any workflow for detailed information

### System Health Monitoring

1. **Health Dashboard**: Monitor overall system health percentage
2. **Performance Metrics**: Review response times and resource usage
3. **Error Alerts**: Respond to any system alerts or warnings
4. **Emergency Controls**: Use emergency shutdown if critical issues arise

## Best Practices

### Workflow Management
- **Prioritize Appropriately**: Use priority levels effectively (High for urgent, Medium for standard, Low for non-critical)
- **Monitor Progress**: Regularly check workflow progress to identify potential delays
- **Resource Planning**: Avoid starting too many high-priority workflows simultaneously
- **Documentation**: Maintain proper documentation for custom workflows

### System Monitoring
- **Regular Health Checks**: Review system health at least twice daily
- **Performance Monitoring**: Track response times and resource usage trends
- **Agent Load Balancing**: Distribute tasks evenly across agents when possible
- **Proactive Maintenance**: Address warnings before they become critical issues

### Troubleshooting
- **Agent Issues**: If an agent shows unhealthy status, check recent activities
- **Workflow Delays**: Review agent loads and system resources for bottlenecks
- **Error Resolution**: Use the activity feed to identify and resolve errors quickly
- **Escalation**: Contact system administrators for persistent issues

## API Integration

The system provides REST API endpoints for integration with other systems:

### Key Endpoints
- `GET /api/ai-agents/agents/status` - Get agent status information
- `GET /api/ai-agents/system/health` - System health check
- `POST /api/ai-agents/workflows/start` - Start new workflow
- `GET /api/ai-agents/workflows/active` - Get active workflows
- `GET /api/ai-agents/activity/feed` - Get activity feed

### Authentication
All API endpoints require authentication using Laravel Sanctum tokens.

## Support and Maintenance

### Regular Maintenance
- **Daily**: System health monitoring, workflow review
- **Weekly**: Performance analysis, agent efficiency review
- **Monthly**: Full system audit, capacity planning

### Support Contacts
- **Technical Issues**: Contact IT Support team
- **Workflow Questions**: Contact HR team
- **System Administration**: Contact DevOps team

## Security Considerations

- **Access Control**: Dashboard access is restricted to authorized personnel
- **Data Privacy**: All employee data is processed according to privacy policies
- **Audit Trails**: Complete audit logs are maintained for all activities
- **Secure Communications**: All agent communications are encrypted

## Future Enhancements

### Planned Features
- **Mobile App**: Mobile interface for monitoring and basic controls
- **Advanced Analytics**: Machine learning insights and predictions
- **Custom Workflows**: Visual workflow builder for custom processes
- **Integration Expansion**: Additional third-party system integrations

### Feedback and Suggestions
Contact the development team with feature requests and improvement suggestions.

---

*Last Updated: October 2024*
*Version: 1.0.0*