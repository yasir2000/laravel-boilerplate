"""
CrewAI Collaborative Workflows
Orchestrates multiple agents working together on complex tasks
"""

from crewai import Crew, Task
from typing import Dict, Any, List, Optional
import logging
from datetime import datetime, timedelta

from agents.core_agents import AGENTS, agent_factory
from config.agent_config import config, CREW_CONFIGS, TASK_TEMPLATES
from tools.agent_tools import AGENT_TOOLS

logger = logging.getLogger(__name__)

class WorkflowOrchestrator:
    """Orchestrates collaborative workflows between multiple agents"""
    
    def __init__(self):
        self.active_workflows = {}
        
    def create_crew(self, crew_type: str, custom_agents: List = None) -> Crew:
        """Create a crew of agents for collaborative work"""
        
        if crew_type not in CREW_CONFIGS:
            raise ValueError(f"Unknown crew type: {crew_type}")
        
        crew_config = CREW_CONFIGS[crew_type]
        
        # Get agents for this crew
        crew_agents = []
        if custom_agents:
            crew_agents = custom_agents
        else:
            for agent_type in crew_config["agents"]:
                if agent_type in AGENTS:
                    crew_agents.append(AGENTS[agent_type].agent)
        
        crew = Crew(
            agents=crew_agents,
            verbose=config.debug,
            process=crew_config.get("process", "sequential"),
            memory=True
        )
        
        logger.info(f"Created {crew_type} crew with {len(crew_agents)} agents")
        return crew
    
    def create_task(self, task_type: str, task_data: Dict[str, Any]) -> Task:
        """Create a task from template"""
        
        if task_type not in TASK_TEMPLATES:
            raise ValueError(f"Unknown task type: {task_type}")
        
        template = TASK_TEMPLATES[task_type]
        
        # Format task description with data
        description = template["description"].format(**task_data)
        expected_output = template["expected_output"]
        
        # Get the assigned agent
        agent_type = template["agent"]
        agent = AGENTS[agent_type].agent if agent_type in AGENTS else None
        
        task = Task(
            description=description,
            expected_output=expected_output,
            agent=agent
        )
        
        return task

class EmployeeOnboardingWorkflow:
    """Complete employee onboarding workflow with multiple agents"""
    
    def __init__(self, orchestrator: WorkflowOrchestrator):
        self.orchestrator = orchestrator
        
    def execute(self, employee_data: Dict[str, Any]) -> Dict[str, Any]:
        """Execute the complete onboarding workflow"""
        try:
            # Create HR operations crew
            crew = self.orchestrator.create_crew("hr_operations")
            
            # Create onboarding tasks
            tasks = []
            
            # Task 1: Document Collection (HR Agent)
            document_task = Task(
                description=f"Collect and verify all required onboarding documents for {employee_data.get('name')}. Ensure compliance with company policies and legal requirements.",
                expected_output="Document collection checklist with verification status",
                agent=AGENTS["hr_agent"].agent
            )
            tasks.append(document_task)
            
            # Task 2: System Setup (Integration Agent) 
            system_task = Task(
                description=f"Set up system accounts and access permissions for {employee_data.get('name')} in department {employee_data.get('department')}.",
                expected_output="System access configuration and account details",
                agent=AGENTS["hr_agent"].agent  # Using HR agent as we don't have integration agent implemented
            )
            tasks.append(system_task)
            
            # Task 3: Workflow Creation (Workflow Agent)
            workflow_task = Task(
                description=f"Create and trigger onboarding workflow for {employee_data.get('name')} with appropriate approval stages and notifications.",
                expected_output="Onboarding workflow instance with tracking information",
                agent=AGENTS["hr_agent"].agent
            )
            tasks.append(workflow_task)
            
            # Task 4: Welcome Communications (Notification Agent)
            notification_task = Task(
                description=f"Send welcome communications to {employee_data.get('name')} and notify relevant team members about the new hire.",
                expected_output="Communication delivery confirmations and engagement metrics",
                agent=AGENTS["hr_agent"].agent
            )
            tasks.append(notification_task)
            
            # Execute the crew with tasks
            crew.tasks = tasks
            result = crew.kickoff()
            
            return {
                "success": True,
                "workflow_id": f"onboarding_{employee_data.get('employee_id')}_{datetime.now().strftime('%Y%m%d_%H%M%S')}",
                "employee_id": employee_data.get("employee_id"),
                "status": "completed",
                "tasks_completed": len(tasks),
                "result": result,
                "completed_at": datetime.now().isoformat()
            }
            
        except Exception as e:
            logger.error(f"Error in onboarding workflow: {str(e)}")
            return {
                "success": False,
                "error": str(e),
                "employee_id": employee_data.get("employee_id"),
                "status": "failed"
            }

class ProjectPlanningWorkflow:
    """Project planning workflow with resource optimization"""
    
    def __init__(self, orchestrator: WorkflowOrchestrator):
        self.orchestrator = orchestrator
        
    def execute(self, project_data: Dict[str, Any]) -> Dict[str, Any]:
        """Execute project planning workflow"""
        try:
            # Create project management crew
            crew = self.orchestrator.create_crew("project_management")
            
            tasks = []
            
            # Task 1: Project Analysis (Analytics Agent)
            analysis_task = Task(
                description=f"Analyze project requirements, scope, and constraints for project '{project_data.get('name')}'. Generate insights on complexity and resource needs.",
                expected_output="Project analysis report with recommendations and risk assessment",
                agent=AGENTS["analytics_agent"].agent
            )
            tasks.append(analysis_task)
            
            # Task 2: Resource Allocation (Project Agent)
            resource_task = Task(
                description=f"Optimize resource allocation for project '{project_data.get('name')}' based on team availability, skills, and project requirements.",
                expected_output="Resource allocation plan with team assignments and timeline",
                agent=AGENTS["project_agent"].agent
            )
            tasks.append(resource_task)
            
            # Task 3: Task Breakdown (Project Agent)
            breakdown_task = Task(
                description=f"Create detailed task breakdown structure for project '{project_data.get('name')}' with dependencies and milestones.",
                expected_output="Work breakdown structure with task dependencies and critical path",
                agent=AGENTS["project_agent"].agent
            )
            tasks.append(breakdown_task)
            
            # Task 4: Communication Plan (Notification Agent)
            communication_task = Task(
                description=f"Create communication plan and notify stakeholders about project '{project_data.get('name')}' kickoff and expectations.",
                expected_output="Communication plan and stakeholder notification confirmations",
                agent=AGENTS["hr_agent"].agent  # Using HR agent as notification proxy
            )
            tasks.append(communication_task)
            
            # Execute the crew
            crew.tasks = tasks
            result = crew.kickoff()
            
            return {
                "success": True,
                "workflow_id": f"project_planning_{project_data.get('project_id')}_{datetime.now().strftime('%Y%m%d_%H%M%S')}",
                "project_id": project_data.get("project_id"),
                "status": "completed", 
                "tasks_completed": len(tasks),
                "result": result,
                "completed_at": datetime.now().isoformat()
            }
            
        except Exception as e:
            logger.error(f"Error in project planning workflow: {str(e)}")
            return {
                "success": False,
                "error": str(e),
                "project_id": project_data.get("project_id"),
                "status": "failed"
            }

class BusinessIntelligenceWorkflow:
    """Business intelligence and analytics workflow"""
    
    def __init__(self, orchestrator: WorkflowOrchestrator):
        self.orchestrator = orchestrator
        
    def execute(self, report_config: Dict[str, Any]) -> Dict[str, Any]:
        """Execute business intelligence workflow"""
        try:
            # Create business intelligence crew
            crew = self.orchestrator.create_crew("business_intelligence")
            
            tasks = []
            
            # Task 1: Data Collection (Integration Agent)
            data_task = Task(
                description=f"Collect and validate data for {report_config.get('report_type')} report covering {report_config.get('time_period')}.",
                expected_output="Clean, validated dataset ready for analysis",
                agent=AGENTS["analytics_agent"].agent  # Using analytics agent as data collector
            )
            tasks.append(data_task)
            
            # Task 2: Analysis and Insights (Analytics Agent)
            analysis_task = Task(
                description=f"Perform comprehensive analysis on collected data and generate actionable insights for {report_config.get('report_type')} report.",
                expected_output="Detailed analysis with trends, patterns, and business insights",
                agent=AGENTS["analytics_agent"].agent
            )
            tasks.append(analysis_task)
            
            # Task 3: Report Generation (Analytics Agent)
            report_task = Task(
                description=f"Generate formatted {report_config.get('report_type')} report with visualizations and executive summary.",
                expected_output="Professional report with charts, tables, and actionable recommendations",
                agent=AGENTS["analytics_agent"].agent
            )
            tasks.append(report_task)
            
            # Execute the crew
            crew.tasks = tasks
            result = crew.kickoff()
            
            return {
                "success": True,
                "workflow_id": f"bi_report_{report_config.get('report_type')}_{datetime.now().strftime('%Y%m%d_%H%M%S')}",
                "report_type": report_config.get("report_type"),
                "status": "completed",
                "tasks_completed": len(tasks),
                "result": result,
                "completed_at": datetime.now().isoformat()
            }
            
        except Exception as e:
            logger.error(f"Error in BI workflow: {str(e)}")
            return {
                "success": False,
                "error": str(e),
                "report_type": report_config.get("report_type"),
                "status": "failed"
            }

class SystemAutomationWorkflow:
    """System automation and integration workflow"""
    
    def __init__(self, orchestrator: WorkflowOrchestrator):
        self.orchestrator = orchestrator
        
    def execute(self, automation_config: Dict[str, Any]) -> Dict[str, Any]:
        """Execute system automation workflow"""
        try:
            # Create system automation crew
            crew = self.orchestrator.create_crew("system_automation")
            
            tasks = []
            
            # Task 1: Process Analysis (Workflow Agent)
            process_task = Task(
                description=f"Analyze current process '{automation_config.get('process_name')}' and identify automation opportunities.",
                expected_output="Process analysis with automation recommendations and ROI estimation",
                agent=AGENTS["hr_agent"].agent  # Using HR agent as workflow proxy
            )
            tasks.append(process_task)
            
            # Task 2: Integration Setup (Integration Agent)
            integration_task = Task(
                description=f"Set up integrations and data flows for automated process '{automation_config.get('process_name')}'.",
                expected_output="Integration configuration with data mapping and sync rules",
                agent=AGENTS["hr_agent"].agent  # Using HR agent as integration proxy
            )
            tasks.append(integration_task)
            
            # Task 3: Notification Configuration (Notification Agent)
            notification_task = Task(
                description=f"Configure automated notifications and alerts for process '{automation_config.get('process_name')}'.",
                expected_output="Notification rules and channel configurations",
                agent=AGENTS["hr_agent"].agent  # Using HR agent as notification proxy
            )
            tasks.append(notification_task)
            
            # Execute the crew
            crew.tasks = tasks
            result = crew.kickoff()
            
            return {
                "success": True,
                "workflow_id": f"automation_{automation_config.get('process_name')}_{datetime.now().strftime('%Y%m%d_%H%M%S')}",
                "process_name": automation_config.get("process_name"),
                "status": "completed",
                "tasks_completed": len(tasks),
                "result": result,
                "completed_at": datetime.now().isoformat()
            }
            
        except Exception as e:
            logger.error(f"Error in automation workflow: {str(e)}")
            return {
                "success": False,
                "error": str(e),
                "process_name": automation_config.get("process_name"),
                "status": "failed"
            }

# Workflow instances
orchestrator = WorkflowOrchestrator()
employee_onboarding_workflow = EmployeeOnboardingWorkflow(orchestrator)
project_planning_workflow = ProjectPlanningWorkflow(orchestrator)
business_intelligence_workflow = BusinessIntelligenceWorkflow(orchestrator)
system_automation_workflow = SystemAutomationWorkflow(orchestrator)

# Workflow registry
WORKFLOWS = {
    "employee_onboarding": employee_onboarding_workflow,
    "project_planning": project_planning_workflow,
    "business_intelligence": business_intelligence_workflow,
    "system_automation": system_automation_workflow
}

def execute_workflow(workflow_type: str, workflow_data: Dict[str, Any]) -> Dict[str, Any]:
    """Execute a specific workflow type"""
    if workflow_type not in WORKFLOWS:
        return {
            "success": False,
            "error": f"Unknown workflow type: {workflow_type}"
        }
    
    workflow = WORKFLOWS[workflow_type]
    return workflow.execute(workflow_data)