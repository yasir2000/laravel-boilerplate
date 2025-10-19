"""
FastAPI Server for CrewAI Agent System
Provides REST API endpoints for Laravel to communicate with AI agents
"""

from fastapi import FastAPI, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Dict, Any, Optional, List
import logging
import uvicorn
from datetime import datetime
import asyncio

from agents.core_agents import AGENTS, get_agent
from config.agent_config import config
from tools.agent_tools import AGENT_TOOLS

# Configure logging
logging.basicConfig(level=getattr(logging, config.agent_log_level))
logger = logging.getLogger(__name__)

# FastAPI app
app = FastAPI(
    title="CrewAI Agent System API",
    description="REST API for Laravel HR system AI agents",
    version="1.0.0"
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8000", config.laravel_api_url],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Pydantic models for API requests
class AgentTaskRequest(BaseModel):
    agent_type: str
    task: str
    data: Dict[str, Any] = {}
    priority: str = "normal"
    
class EmployeeOnboardingRequest(BaseModel):
    employee_id: int
    name: str
    email: str
    department_id: int = None
    manager_id: int = None
    position: str = None

class LeaveRequestData(BaseModel):
    leave_request_id: int
    employee_id: int
    start_date: str
    end_date: str
    leave_type: str
    reason: str = None

class ProjectOptimizationRequest(BaseModel):
    project_id: int
    optimization_type: str = "resource_allocation"

class AnalyticsRequest(BaseModel):
    report_type: str
    time_period: str = "last_30_days"
    filters: Dict[str, Any] = {}

# Health check endpoint
@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "timestamp": datetime.now().isoformat(),
        "agents_available": list(AGENTS.keys()),
        "environment": config.environment
    }

# Agent status endpoint
@app.get("/agents/status")
async def agents_status():
    """Get status of all agents"""
    status = {}
    for agent_type, agent_instance in AGENTS.items():
        status[agent_type] = {
            "available": True,
            "role": agent_instance.agent.role,
            "goal": agent_instance.agent.goal
        }
    
    return {"agents": status}

# Generic agent task endpoint
@app.post("/agents/execute-task")
async def execute_agent_task(
    request: AgentTaskRequest,
    background_tasks: BackgroundTasks
):
    """Execute a task using specified agent"""
    try:
        agent = get_agent(request.agent_type)
        if not agent:
            raise HTTPException(status_code=404, detail=f"Agent {request.agent_type} not found")
        
        # Store task in memory for tracking
        task_id = f"task_{datetime.now().strftime('%Y%m%d_%H%M%S')}_{request.agent_type}"
        task_data = {
            "task_id": task_id,
            "agent_type": request.agent_type,
            "task": request.task,
            "data": request.data,
            "status": "initiated",
            "created_at": datetime.now().isoformat()
        }
        
        AGENT_TOOLS["memory_store"]._run("set", task_id, task_data, ttl=3600)
        
        # Execute task in background
        background_tasks.add_task(execute_task_background, task_id, agent, request.task, request.data)
        
        return {
            "success": True,
            "task_id": task_id,
            "message": f"Task initiated for {request.agent_type}",
            "status": "processing"
        }
        
    except Exception as e:
        logger.error(f"Error executing agent task: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

async def execute_task_background(task_id: str, agent, task: str, data: Dict[str, Any]):
    """Execute agent task in background"""
    try:
        # Update task status
        task_data = {
            "task_id": task_id,
            "status": "processing",
            "started_at": datetime.now().isoformat()
        }
        AGENT_TOOLS["memory_store"]._run("set", f"{task_id}_status", task_data, ttl=3600)
        
        # Execute the task using the agent
        # This is a simplified execution - in practice, you'd use CrewAI's task execution
        result = {"success": True, "message": f"Task '{task}' completed", "data": data}
        
        # Update final status
        final_data = {
            "task_id": task_id,
            "status": "completed",
            "result": result,
            "completed_at": datetime.now().isoformat()
        }
        AGENT_TOOLS["memory_store"]._run("set", f"{task_id}_result", final_data, ttl=3600)
        
    except Exception as e:
        error_data = {
            "task_id": task_id,
            "status": "failed",
            "error": str(e),
            "failed_at": datetime.now().isoformat()
        }
        AGENT_TOOLS["memory_store"]._run("set", f"{task_id}_result", error_data, ttl=3600)

# Get task status
@app.get("/tasks/{task_id}/status")
async def get_task_status(task_id: str):
    """Get status of a specific task"""
    try:
        result = AGENT_TOOLS["memory_store"]._run("get", f"{task_id}_result")
        if result["success"]:
            return result["data"]
        
        status_result = AGENT_TOOLS["memory_store"]._run("get", f"{task_id}_status") 
        if status_result["success"]:
            return status_result["data"]
        
        return {"task_id": task_id, "status": "not_found"}
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# HR Agent Endpoints
@app.post("/hr/onboard-employee")
async def onboard_employee(request: EmployeeOnboardingRequest):
    """Process employee onboarding"""
    try:
        hr_agent = get_agent("hr_agent")
        if not hr_agent:
            raise HTTPException(status_code=404, detail="HR agent not available")
        
        result = hr_agent.process_employee_onboarding(request.dict())
        return result
        
    except Exception as e:
        logger.error(f"Error in employee onboarding: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/hr/process-leave-request")
async def process_leave_request(request: LeaveRequestData):
    """Process leave request"""
    try:
        hr_agent = get_agent("hr_agent")
        if not hr_agent:
            raise HTTPException(status_code=404, detail="HR agent not available")
        
        result = hr_agent.process_leave_request(request.dict())
        return result
        
    except Exception as e:
        logger.error(f"Error processing leave request: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

# Project Agent Endpoints  
@app.post("/projects/optimize-resources")
async def optimize_project_resources(request: ProjectOptimizationRequest):
    """Optimize project resource allocation"""
    try:
        project_agent = get_agent("project_agent")
        if not project_agent:
            raise HTTPException(status_code=404, detail="Project agent not available")
        
        result = project_agent.optimize_resource_allocation(request.project_id)
        return result
        
    except Exception as e:
        logger.error(f"Error optimizing resources: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

# Analytics Agent Endpoints
@app.post("/analytics/employee-report")
async def generate_employee_analytics(request: AnalyticsRequest):
    """Generate employee analytics report"""
    try:
        analytics_agent = get_agent("analytics_agent")
        if not analytics_agent:
            raise HTTPException(status_code=404, detail="Analytics agent not available")
        
        result = analytics_agent.generate_employee_analytics(request.time_period)
        return result
        
    except Exception as e:
        logger.error(f"Error generating analytics: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

# Tools endpoint for direct tool access
@app.post("/tools/{tool_name}/execute")
async def execute_tool(tool_name: str, params: Dict[str, Any]):
    """Execute a specific tool directly"""
    try:
        if tool_name not in AGENT_TOOLS:
            raise HTTPException(status_code=404, detail=f"Tool {tool_name} not found")
        
        tool = AGENT_TOOLS[tool_name]
        result = tool._run(**params)
        return result
        
    except Exception as e:
        logger.error(f"Error executing tool {tool_name}: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

# Database query endpoint
@app.post("/database/query")
async def execute_database_query(query: str, params: Dict[str, Any] = {}):
    """Execute database query"""
    try:
        result = AGENT_TOOLS["database_query"]._run(query, params)
        return result
        
    except Exception as e:
        logger.error(f"Error executing database query: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run(
        "agent_server:app",
        host="0.0.0.0",
        port=8001,
        reload=config.debug,
        log_level=config.agent_log_level.lower()
    )