"""
HR Workflow Integration System
Master orchestrator for all HR workflows and agent coordination
"""

from datetime import datetime
from typing import Dict, Any, List, Optional
import logging
import asyncio

# Import all workflow systems
from workflows.enhanced_use_cases import enhanced_workflow_orchestrator
from workflows.leave_management import leave_management_system
from workflows.performance_review import performance_review_system
from workflows.payroll_exceptions import payroll_exception_handler
from workflows.employee_queries import employee_query_system
from workflows.recruitment_automation import recruitment_system
from workflows.compliance_monitoring import compliance_system

# Import agents
from agents.core_agents import AGENTS
from agents.specialized_agents import SPECIALIZED_AGENTS
from tools.agent_tools import AGENT_TOOLS

logger = logging.getLogger(__name__)

class HRWorkflowIntegrationSystem:
    """Master orchestrator for all HR workflows and agent coordination"""
    
    def __init__(self):
        self.active_workflows = {}
        self.workflow_systems = {
            "onboarding": enhanced_workflow_orchestrator,
            "leave_management": leave_management_system,
            "performance_review": performance_review_system,
            "payroll_exceptions": payroll_exception_handler,
            "employee_queries": employee_query_system,
            "recruitment": recruitment_system,
            "compliance": compliance_system
        }
        
    async def initiate_workflow(self, workflow_type: str, workflow_data: Dict[str, Any]) -> Dict[str, Any]:
        """Initiate any HR workflow through unified interface"""
        workflow_id = f"{workflow_type}_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        
        try:
            logger.info(f"Initiating {workflow_type} workflow: {workflow_id}")
            
            # Route to appropriate workflow system
            if workflow_type == "employee_onboarding":
                result = await enhanced_workflow_orchestrator.start_employee_onboarding(workflow_data)
            elif workflow_type == "leave_request":
                result = await leave_management_system.process_leave_request(workflow_data)
            elif workflow_type == "performance_review":
                result = await performance_review_system.initiate_performance_review(workflow_data)
            elif workflow_type == "payroll_exceptions":
                result = await payroll_exception_handler.process_payroll_exceptions(workflow_data)
            elif workflow_type == "employee_query":
                result = await employee_query_system.process_employee_query(workflow_data)
            elif workflow_type == "recruitment":
                result = await recruitment_system.initiate_recruitment_process(workflow_data)
            elif workflow_type == "compliance_monitoring":
                result = await compliance_system.initiate_compliance_monitoring(workflow_data)
            else:
                return {
                    "success": False,
                    "error": f"Unknown workflow type: {workflow_type}"
                }
            
            # Track active workflow
            if result.get("success"):
                self.active_workflows[workflow_id] = {
                    "type": workflow_type,
                    "status": "active",
                    "started_at": datetime.now().isoformat(),
                    "result": result
                }
            
            return {
                "success": result.get("success", False),
                "workflow_id": workflow_id,
                "workflow_type": workflow_type,
                "result": result
            }
            
        except Exception as e:
            logger.error(f"Error initiating {workflow_type} workflow: {str(e)}")
            return {
                "success": False,
                "workflow_id": workflow_id,
                "error": str(e)
            }
    
    async def get_workflow_status(self, workflow_id: str) -> Dict[str, Any]:
        """Get status of any active workflow"""
        try:
            if workflow_id in self.active_workflows:
                return {
                    "success": True,
                    "workflow_found": True,
                    "workflow_data": self.active_workflows[workflow_id]
                }
            
            # Check persistent memory
            stored_workflow = AGENT_TOOLS["memory_store"]._run("get", workflow_id)
            if stored_workflow:
                return {
                    "success": True,
                    "workflow_found": True,
                    "workflow_data": stored_workflow
                }
            
            return {
                "success": True,
                "workflow_found": False,
                "message": "Workflow not found"
            }
            
        except Exception as e:
            logger.error(f"Error getting workflow status: {str(e)}")
            return {
                "success": False,
                "error": str(e)
            }
    
    async def get_all_active_workflows(self) -> Dict[str, Any]:
        """Get status of all active workflows"""
        try:
            all_workflows = {}
            
            # Get from active workflows
            for workflow_id, workflow_data in self.active_workflows.items():
                all_workflows[workflow_id] = workflow_data
            
            # Get from memory store (simplified - would need proper key scanning in real implementation)
            # This would require implementing a way to scan memory keys by pattern
            
            workflow_summary = {
                "total_active": len(all_workflows),
                "by_type": {},
                "by_status": {}
            }
            
            for workflow_id, workflow_data in all_workflows.items():
                workflow_type = workflow_data.get("type", "unknown")
                workflow_status = workflow_data.get("status", "unknown")
                
                workflow_summary["by_type"][workflow_type] = workflow_summary["by_type"].get(workflow_type, 0) + 1
                workflow_summary["by_status"][workflow_status] = workflow_summary["by_status"].get(workflow_status, 0) + 1
            
            return {
                "success": True,
                "workflows": all_workflows,
                "summary": workflow_summary
            }
            
        except Exception as e:
            logger.error(f"Error getting all workflows: {str(e)}")
            return {
                "success": False,
                "error": str(e)
            }
    
    async def get_system_health(self) -> Dict[str, Any]:
        """Get overall system health and status"""
        try:
            # Check all agents
            agent_status = {}
            for agent_name, agent in {**AGENTS, **SPECIALIZED_AGENTS}.items():
                try:
                    # Simple health check - would be more comprehensive in real implementation
                    agent_status[agent_name] = {
                        "status": "healthy",
                        "last_checked": datetime.now().isoformat()
                    }
                except Exception as e:
                    agent_status[agent_name] = {
                        "status": "error",
                        "error": str(e),
                        "last_checked": datetime.now().isoformat()
                    }
            
            # Check workflow systems
            workflow_system_status = {}
            for system_name, system in self.workflow_systems.items():
                try:
                    workflow_system_status[system_name] = {
                        "status": "healthy",
                        "last_checked": datetime.now().isoformat()
                    }
                except Exception as e:
                    workflow_system_status[system_name] = {
                        "status": "error",
                        "error": str(e),
                        "last_checked": datetime.now().isoformat()
                    }
            
            # Overall health
            total_components = len(agent_status) + len(workflow_system_status)
            healthy_components = sum(1 for status in {**agent_status, **workflow_system_status}.values() 
                                   if status.get("status") == "healthy")
            
            health_percentage = (healthy_components / total_components) * 100 if total_components > 0 else 0
            
            overall_health = "healthy" if health_percentage >= 95 else "degraded" if health_percentage >= 80 else "critical"
            
            return {
                "success": True,
                "overall_health": overall_health,
                "health_percentage": health_percentage,
                "agent_status": agent_status,
                "workflow_system_status": workflow_system_status,
                "active_workflows": len(self.active_workflows),
                "last_health_check": datetime.now().isoformat()
            }
            
        except Exception as e:
            logger.error(f"Error getting system health: {str(e)}")
            return {
                "success": False,
                "error": str(e)
            }
    
    async def emergency_shutdown(self, reason: str = "Manual shutdown") -> Dict[str, Any]:
        """Emergency shutdown of all workflows and agents"""
        try:
            logger.warning(f"Emergency shutdown initiated: {reason}")
            
            shutdown_results = {
                "workflows_stopped": 0,
                "agents_stopped": 0,
                "errors": []
            }
            
            # Stop all active workflows
            for workflow_id, workflow_data in self.active_workflows.items():
                try:
                    workflow_data["status"] = "emergency_stopped"
                    workflow_data["stopped_at"] = datetime.now().isoformat()
                    workflow_data["stop_reason"] = reason
                    shutdown_results["workflows_stopped"] += 1
                except Exception as e:
                    shutdown_results["errors"].append(f"Error stopping workflow {workflow_id}: {str(e)}")
            
            # Notify all agents
            for agent_name in {**AGENTS, **SPECIALIZED_AGENTS}.keys():
                try:
                    # In a real implementation, you would properly shut down agents
                    shutdown_results["agents_stopped"] += 1
                except Exception as e:
                    shutdown_results["errors"].append(f"Error stopping agent {agent_name}: {str(e)}")
            
            return {
                "success": True,
                "shutdown_completed": True,
                "reason": reason,
                "shutdown_results": shutdown_results,
                "shutdown_time": datetime.now().isoformat()
            }
            
        except Exception as e:
            logger.error(f"Error during emergency shutdown: {str(e)}")
            return {
                "success": False,
                "error": str(e)
            }

# System Integration Instance
hr_integration_system = HRWorkflowIntegrationSystem()

# Convenience functions for easy access
async def start_workflow(workflow_type: str, data: Dict[str, Any]) -> Dict[str, Any]:
    """Convenience function to start any workflow"""
    return await hr_integration_system.initiate_workflow(workflow_type, data)

async def get_workflow_status(workflow_id: str) -> Dict[str, Any]:
    """Convenience function to get workflow status"""
    return await hr_integration_system.get_workflow_status(workflow_id)

async def get_system_health() -> Dict[str, Any]:
    """Convenience function to get system health"""
    return await hr_integration_system.get_system_health()

# Export for external use
__all__ = [
    "hr_integration_system", 
    "HRWorkflowIntegrationSystem",
    "start_workflow",
    "get_workflow_status", 
    "get_system_health"
]