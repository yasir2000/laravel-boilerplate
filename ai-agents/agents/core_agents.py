"""
CrewAI Collaborative Agents Implementation
Core agent definitions for the Laravel HR system
"""

from crewai import Agent
from langchain_openai import ChatOpenAI
from typing import List, Dict, Any
import logging

from config.agent_config import config, AGENT_ROLES
from tools.agent_tools import AGENT_TOOLS

# Configure logging
logging.basicConfig(level=getattr(logging, config.agent_log_level))
logger = logging.getLogger(__name__)

class BaseAgentFactory:
    """Base factory class for creating agents"""
    
    def __init__(self, llm_model: str = None):
        self.llm = ChatOpenAI(
            model=llm_model or config.default_llm_model,
            temperature=config.temperature,
            max_tokens=config.max_tokens,
            api_key=config.openai_api_key
        )
    
    def create_agent(self, agent_type: str, tools: List = None) -> Agent:
        """Create an agent with specified type and tools"""
        
        if agent_type not in AGENT_ROLES:
            raise ValueError(f"Unknown agent type: {agent_type}")
        
        role_config = AGENT_ROLES[agent_type]
        
        # Get tools for this agent
        agent_tools = []
        if tools:
            agent_tools = tools
        else:
            tool_names = role_config.get("tools", [])
            agent_tools = [AGENT_TOOLS[tool_name] for tool_name in tool_names if tool_name in AGENT_TOOLS]
        
        agent = Agent(
            role=role_config["role"],
            goal=role_config["goal"],
            backstory=role_config["backstory"],
            tools=agent_tools,
            llm=self.llm,
            verbose=config.debug,
            memory=True,
            max_iter=config.agent_max_iterations,
            allow_delegation=True
        )
        
        logger.info(f"Created {agent_type} agent with {len(agent_tools)} tools")
        return agent

class HRManagementAgent:
    """HR Management Specialist Agent"""
    
    def __init__(self, agent_factory: BaseAgentFactory):
        self.factory = agent_factory
        self.agent = self._create_agent()
    
    def _create_agent(self) -> Agent:
        """Create HR management agent with specialized tools"""
        tools = [
            AGENT_TOOLS["database_query"],
            AGENT_TOOLS["laravel_api"],
            AGENT_TOOLS["email_sender"],
            AGENT_TOOLS["workflow_engine"],
            AGENT_TOOLS["memory_store"]
        ]
        
        return self.factory.create_agent("hr_agent", tools)
    
    def process_employee_onboarding(self, employee_data: Dict[str, Any]) -> Dict[str, Any]:
        """Process new employee onboarding workflow"""
        try:
            # Store employee data in memory
            memory_key = f"onboarding_{employee_data.get('employee_id')}"
            memory_result = AGENT_TOOLS["memory_store"]._run(
                "set", memory_key, employee_data, ttl=86400  # 24 hours
            )
            
            # Create onboarding workflow
            workflow_data = {
                "name": f"Employee Onboarding - {employee_data.get('name')}",
                "model_type": "Employee", 
                "model_id": employee_data.get("employee_id"),
                "steps": [
                    {"name": "Document Collection", "assignee_type": "hr", "order": 1},
                    {"name": "System Account Setup", "assignee_type": "it", "order": 2},
                    {"name": "Orientation Scheduling", "assignee_type": "hr", "order": 3},
                    {"name": "Equipment Assignment", "assignee_type": "facilities", "order": 4}
                ]
            }
            
            workflow_result = AGENT_TOOLS["workflow_engine"]._run("create", workflow_data)
            
            # Send welcome email
            if employee_data.get("email"):
                email_result = AGENT_TOOLS["email_sender"]._run(
                    employee_data["email"],
                    "Welcome to the Team!",
                    f"Dear {employee_data.get('name')}, welcome to our organization. Your onboarding process has begun."
                )
            
            return {
                "success": True,
                "employee_id": employee_data.get("employee_id"),
                "workflow_id": workflow_result.get("data", {}).get("id"),
                "next_steps": ["Document collection", "System setup", "Orientation"]
            }
            
        except Exception as e:
            logger.error(f"Error in employee onboarding: {str(e)}")
            return {"success": False, "error": str(e)}
    
    def process_leave_request(self, leave_data: Dict[str, Any]) -> Dict[str, Any]:
        """Process employee leave request with automated approval workflow"""
        try:
            # Get employee information
            employee_query = "SELECT * FROM employees WHERE id = :employee_id"
            employee_result = AGENT_TOOLS["database_query"]._run(
                employee_query, {"employee_id": leave_data.get("employee_id")}
            )
            
            if not employee_result["success"] or not employee_result["data"]:
                return {"success": False, "error": "Employee not found"}
            
            employee = employee_result["data"][0]
            
            # Create leave approval workflow
            workflow_data = {
                "name": f"Leave Request - {employee['name']}",
                "model_type": "LeaveRequest",
                "model_id": leave_data.get("leave_request_id"),
                "steps": [
                    {"name": "Manager Approval", "assignee_id": employee.get("manager_id"), "order": 1},
                    {"name": "HR Review", "assignee_type": "hr", "order": 2}
                ]
            }
            
            workflow_result = AGENT_TOOLS["workflow_engine"]._run("create", workflow_data)
            
            # Notify manager
            if employee.get("manager_email"):
                AGENT_TOOLS["email_sender"]._run(
                    employee["manager_email"],
                    "Leave Request Approval Required",
                    f"A leave request from {employee['name']} requires your approval."
                )
            
            return {
                "success": True,
                "leave_request_id": leave_data.get("leave_request_id"),
                "workflow_id": workflow_result.get("data", {}).get("id"),
                "status": "pending_manager_approval"
            }
            
        except Exception as e:
            logger.error(f"Error processing leave request: {str(e)}")
            return {"success": False, "error": str(e)}

class ProjectManagementAgent:
    """Project Management Coordinator Agent"""
    
    def __init__(self, agent_factory: BaseAgentFactory):
        self.factory = agent_factory
        self.agent = self._create_agent()
    
    def _create_agent(self) -> Agent:
        """Create project management agent with specialized tools"""
        tools = [
            AGENT_TOOLS["database_query"],
            AGENT_TOOLS["laravel_api"],
            AGENT_TOOLS["data_analyzer"],
            AGENT_TOOLS["email_sender"],
            AGENT_TOOLS["memory_store"]
        ]
        
        return self.factory.create_agent("project_agent", tools)
    
    def optimize_resource_allocation(self, project_id: int) -> Dict[str, Any]:
        """Optimize resource allocation for a project"""
        try:
            # Get project data
            project_query = """
                SELECT p.*, t.name as task_name, t.status, t.assigned_to, t.estimated_hours
                FROM projects p
                LEFT JOIN tasks t ON p.id = t.project_id
                WHERE p.id = :project_id
            """
            
            project_result = AGENT_TOOLS["database_query"]._run(
                project_query, {"project_id": project_id}
            )
            
            if not project_result["success"]:
                return project_result
            
            # Analyze resource utilization
            analysis_result = AGENT_TOOLS["data_analyzer"]._run(
                project_query.replace(":project_id", str(project_id)), "summary"
            )
            
            # Get team member availability
            team_query = """
                SELECT u.id, u.name, u.email,
                       COUNT(t.id) as active_tasks,
                       SUM(t.estimated_hours) as total_workload
                FROM users u
                LEFT JOIN tasks t ON u.id = t.assigned_to AND t.status IN ('in_progress', 'pending')
                WHERE u.active = true
                GROUP BY u.id, u.name, u.email
                ORDER BY total_workload ASC
            """
            
            team_result = AGENT_TOOLS["database_query"]._run(team_query)
            
            if team_result["success"] and team_result["data"]:
                # Find team members with lowest workload for new assignments  
                available_members = [
                    member for member in team_result["data"] 
                    if (member["total_workload"] or 0) < 40  # Less than 40 hours
                ]
                
                recommendations = {
                    "project_id": project_id,
                    "analysis": analysis_result.get("analysis", {}),
                    "available_members": available_members[:3],  # Top 3 available
                    "recommendations": [
                        "Consider redistributing tasks to team members with lower workload",
                        "Schedule regular check-ins to monitor progress",
                        "Identify potential bottlenecks in task dependencies"
                    ]
                }
            else:
                recommendations = {
                    "project_id": project_id,
                    "error": "Unable to analyze team availability"
                }
            
            return {"success": True, "recommendations": recommendations}
            
        except Exception as e:
            logger.error(f"Error optimizing resource allocation: {str(e)}")
            return {"success": False, "error": str(e)}

class AnalyticsAgent:
    """Business Intelligence Analyst Agent"""
    
    def __init__(self, agent_factory: BaseAgentFactory):
        self.factory = agent_factory
        self.agent = self._create_agent()
    
    def _create_agent(self) -> Agent:
        """Create analytics agent with specialized tools"""
        tools = [
            AGENT_TOOLS["database_query"],
            AGENT_TOOLS["data_analyzer"], 
            AGENT_TOOLS["report_generator"],
            AGENT_TOOLS["memory_store"]
        ]
        
        return self.factory.create_agent("analytics_agent", tools)
    
    def generate_employee_analytics(self, time_period: str = "last_30_days") -> Dict[str, Any]:
        """Generate comprehensive employee analytics report"""
        try:
            # Define time period filter
            time_filters = {
                "last_7_days": "created_at >= NOW() - INTERVAL '7 days'",
                "last_30_days": "created_at >= NOW() - INTERVAL '30 days'",
                "last_90_days": "created_at >= NOW() - INTERVAL '90 days'",
                "year_to_date": "EXTRACT(YEAR FROM created_at) = EXTRACT(YEAR FROM NOW())"
            }
            
            time_filter = time_filters.get(time_period, time_filters["last_30_days"])
            
            # Employee statistics query
            employee_stats_query = f"""
                SELECT 
                    COUNT(*) as total_employees,
                    COUNT(CASE WHEN active = true THEN 1 END) as active_employees,
                    COUNT(CASE WHEN {time_filter} THEN 1 END) as new_employees,
                    ROUND(AVG(CASE WHEN salary > 0 THEN salary END), 2) as avg_salary
                FROM employees
            """
            
            stats_result = AGENT_TOOLS["database_query"]._run(employee_stats_query)
            
            # Department distribution
            dept_query = f"""
                SELECT d.name as department, COUNT(e.id) as employee_count
                FROM departments d
                LEFT JOIN employees e ON d.id = e.department_id
                GROUP BY d.id, d.name
                ORDER BY employee_count DESC
            """
            
            dept_result = AGENT_TOOLS["database_query"]._run(dept_query)
            
            # Generate report
            report_data = {
                "time_period": time_period,
                "employee_statistics": stats_result.get("data", [{}])[0] if stats_result["success"] else {},
                "department_distribution": dept_result.get("data", []) if dept_result["success"] else [],
                "insights": [
                    "Monitor new hire retention rates",
                    "Analyze department growth patterns", 
                    "Track salary competitiveness"
                ]
            }
            
            report_result = AGENT_TOOLS["report_generator"]._run(
                "employee_summary", employee_stats_query, "json"
            )
            
            return {
                "success": True,
                "analytics": report_data,
                "detailed_report": report_result.get("report", {}) if report_result["success"] else {}
            }
            
        except Exception as e:
            logger.error(f"Error generating employee analytics: {str(e)}")
            return {"success": False, "error": str(e)}

# Agent Factory Instance
agent_factory = BaseAgentFactory()

# Agent Instances
hr_agent = HRManagementAgent(agent_factory)
project_agent = ProjectManagementAgent(agent_factory)
analytics_agent = AnalyticsAgent(agent_factory)

# Agent Registry
AGENTS = {
    "hr_agent": hr_agent,
    "project_agent": project_agent, 
    "analytics_agent": analytics_agent
}

def get_agent(agent_type: str):
    """Get agent instance by type"""
    return AGENTS.get(agent_type)