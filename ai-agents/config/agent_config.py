"""
CrewAI Collaborative Agent System Configuration
"""

import os
from typing import Dict, Any, List
from pydantic import BaseSettings, Field
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

class AgentConfig(BaseSettings):
    """Main configuration class for the agent system"""
    
    # AI Model Configuration
    openai_api_key: str = Field(default="", env="OPENAI_API_KEY")
    anthropic_api_key: str = Field(default="", env="ANTHROPIC_API_KEY")
    google_api_key: str = Field(default="", env="GOOGLE_API_KEY")
    
    default_llm_model: str = Field(default="gpt-4-turbo-preview", env="DEFAULT_LLM_MODEL")
    default_embedding_model: str = Field(default="text-embedding-3-small", env="DEFAULT_EMBEDDING_MODEL")
    temperature: float = Field(default=0.1, env="TEMPERATURE")
    max_tokens: int = Field(default=4000, env="MAX_TOKENS")
    
    # Database Configuration
    db_host: str = Field(default="localhost", env="DB_HOST")
    db_port: int = Field(default=5432, env="DB_PORT")
    db_name: str = Field(default="laravel_hr_boilerplate", env="DB_DATABASE")
    db_user: str = Field(default="postgres", env="DB_USERNAME")
    db_password: str = Field(default="", env="DB_PASSWORD")
    
    # Redis Configuration
    redis_host: str = Field(default="localhost", env="REDIS_HOST")
    redis_port: int = Field(default=6379, env="REDIS_PORT")
    redis_password: str = Field(default="", env="REDIS_PASSWORD")
    redis_db: int = Field(default=1, env="REDIS_DB")
    
    # Laravel Integration
    laravel_api_url: str = Field(default="http://localhost:8000", env="LARAVEL_API_URL")
    laravel_api_token: str = Field(default="", env="LARAVEL_API_TOKEN")
    laravel_app_key: str = Field(default="", env="LARAVEL_APP_KEY")
    
    # Agent System Settings
    agent_memory_backend: str = Field(default="redis", env="AGENT_MEMORY_BACKEND")
    agent_log_level: str = Field(default="INFO", env="AGENT_LOG_LEVEL")
    agent_max_iterations: int = Field(default=10, env="AGENT_MAX_ITERATIONS")
    agent_execution_timeout: int = Field(default=300, env="AGENT_EXECUTION_TIMEOUT")
    
    # External Services
    twilio_account_sid: str = Field(default="", env="TWILIO_ACCOUNT_SID")
    twilio_auth_token: str = Field(default="", env="TWILIO_AUTH_TOKEN")
    twilio_phone_number: str = Field(default="", env="TWILIO_PHONE_NUMBER")
    
    smtp_host: str = Field(default="smtp.gmail.com", env="SMTP_HOST")
    smtp_port: int = Field(default=587, env="SMTP_PORT")
    smtp_username: str = Field(default="", env="SMTP_USERNAME")
    smtp_password: str = Field(default="", env="SMTP_PASSWORD")
    
    # Development Settings
    debug: bool = Field(default=True, env="DEBUG")
    testing: bool = Field(default=False, env="TESTING")
    environment: str = Field(default="development", env="ENVIRONMENT")
    
    @property
    def database_url(self) -> str:
        """Generate database URL from components"""
        return f"postgresql://{self.db_user}:{self.db_password}@{self.db_host}:{self.db_port}/{self.db_name}"
    
    @property
    def redis_url(self) -> str:
        """Generate Redis URL from components"""
        if self.redis_password:
            return f"redis://:{self.redis_password}@{self.redis_host}:{self.redis_port}/{self.redis_db}"
        return f"redis://{self.redis_host}:{self.redis_port}/{self.redis_db}"
    
    class Config:
        env_file = ".env"
        case_sensitive = False

# Agent Role Definitions
AGENT_ROLES = {
    "hr_agent": {
        "role": "HR Management Specialist",
        "goal": "Automate and optimize human resources processes, employee management, and compliance",
        "backstory": """You are an experienced HR professional with deep knowledge of employment 
        law, benefits administration, and employee lifecycle management. You excel at automating 
        repetitive HR tasks while maintaining the human touch in employee interactions.""",
        "capabilities": [
            "employee_onboarding",
            "leave_management", 
            "performance_evaluation",
            "compliance_monitoring",
            "document_verification",
            "attendance_tracking"
        ],
        "tools": ["database_query", "email_sender", "document_processor", "calendar_manager"]
    },
    
    "project_agent": {
        "role": "Project Management Coordinator",
        "goal": "Optimize project planning, resource allocation, and team collaboration",
        "backstory": """You are a seasoned project manager with expertise in agile methodologies, 
        resource optimization, and team dynamics. You have a talent for identifying bottlenecks 
        and finding creative solutions to keep projects on track.""",
        "capabilities": [
            "project_planning",
            "task_assignment",
            "resource_allocation",
            "deadline_monitoring",
            "team_coordination",
            "progress_tracking"
        ],
        "tools": ["project_analyzer", "task_scheduler", "resource_optimizer", "notification_sender"]
    },
    
    "analytics_agent": {
        "role": "Business Intelligence Analyst",
        "goal": "Generate insights, reports, and predictive analytics from business data",
        "backstory": """You are a data scientist with strong business acumen and visualization 
        skills. You can transform raw data into actionable insights and create compelling 
        reports that drive business decisions.""",
        "capabilities": [
            "data_analysis",
            "report_generation",
            "predictive_modeling",
            "trend_identification",
            "dashboard_creation",
            "kpi_monitoring"
        ],
        "tools": ["data_analyzer", "report_generator", "visualization_creator", "ml_predictor"]
    },
    
    "workflow_agent": {
        "role": "Process Automation Specialist", 
        "goal": "Design, implement, and optimize automated business workflows",
        "backstory": """You are a business process expert with deep knowledge of workflow 
        automation and integration patterns. You excel at identifying inefficiencies and 
        creating streamlined automated processes.""",
        "capabilities": [
            "workflow_design",
            "process_optimization",
            "approval_automation",
            "exception_handling",
            "integration_management",
            "performance_monitoring"
        ],
        "tools": ["workflow_engine", "process_optimizer", "approval_router", "integration_manager"]
    },
    
    "integration_agent": {
        "role": "System Integration Specialist",
        "goal": "Manage external integrations, API connections, and data synchronization",
        "backstory": """You are a systems integration expert with experience in API design, 
        data mapping, and enterprise integration patterns. You ensure seamless data flow 
        between systems while maintaining data integrity.""",
        "capabilities": [
            "api_management",
            "data_synchronization",
            "system_monitoring",
            "error_handling",
            "integration_testing",
            "data_validation"
        ],
        "tools": ["api_client", "data_mapper", "sync_manager", "health_monitor"]
    },
    
    "notification_agent": {
        "role": "Communication Coordinator",
        "goal": "Manage multi-channel notifications and user communications",
        "backstory": """You are a communications specialist with expertise in multi-channel 
        messaging, user engagement, and communication optimization. You ensure the right 
        message reaches the right person at the right time through the best channel.""",
        "capabilities": [
            "message_delivery",
            "channel_optimization",
            "user_preferences",
            "communication_scheduling",
            "delivery_tracking",
            "engagement_analysis"
        ],
        "tools": ["email_client", "sms_sender", "push_notifier", "communication_tracker"]
    }
}

# Task Templates
TASK_TEMPLATES = {
    "employee_onboarding": {
        "description": "Complete the employee onboarding process for {employee_name}",
        "expected_output": "Onboarding checklist completion status and next steps",
        "agent": "hr_agent"
    },
    
    "project_planning": {
        "description": "Create project plan for {project_name} with timeline and resource allocation",
        "expected_output": "Detailed project plan with milestones and resource assignments",
        "agent": "project_agent"
    },
    
    "analytics_report": {
        "description": "Generate {report_type} analytics report for {time_period}",
        "expected_output": "Comprehensive report with insights and recommendations",
        "agent": "analytics_agent"
    },
    
    "workflow_automation": {
        "description": "Automate {process_name} workflow with approval stages",
        "expected_output": "Implemented workflow with automation rules and configurations",
        "agent": "workflow_agent"
    },
    
    "system_integration": {
        "description": "Integrate {system_name} with existing platform",
        "expected_output": "Integration setup with data mapping and synchronization rules",
        "agent": "integration_agent"
    },
    
    "notification_campaign": {
        "description": "Send {notification_type} notifications to {target_audience}",
        "expected_output": "Notification delivery report with engagement metrics",
        "agent": "notification_agent"
    }
}

# Crew Configurations
CREW_CONFIGS = {
    "hr_operations": {
        "name": "HR Operations Crew",
        "description": "Handles all HR-related operations and processes",
        "agents": ["hr_agent", "workflow_agent", "notification_agent"],
        "process": "sequential"
    },
    
    "project_management": {
        "name": "Project Management Crew",
        "description": "Manages project planning, execution, and monitoring",
        "agents": ["project_agent", "analytics_agent", "notification_agent"],
        "process": "hierarchical"
    },
    
    "business_intelligence": {
        "name": "Business Intelligence Crew", 
        "description": "Provides analytics, reporting, and business insights",
        "agents": ["analytics_agent", "integration_agent"],
        "process": "sequential"
    },
    
    "system_automation": {
        "name": "System Automation Crew",
        "description": "Automates system processes and integrations",
        "agents": ["workflow_agent", "integration_agent", "notification_agent"],
        "process": "hierarchical"
    }
}

# Load configuration instance
config = AgentConfig()