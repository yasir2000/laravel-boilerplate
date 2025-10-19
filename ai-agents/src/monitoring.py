"""
Agent Monitoring and Performance Tracking System
Tracks agent activities, performance metrics, and system health
"""

import time
import json
import logging
from typing import Dict, Any, List, Optional
from datetime import datetime, timedelta
from dataclasses import dataclass, asdict
from contextlib import contextmanager
import redis
from prometheus_client import Counter, Histogram, Gauge, start_http_server
import psutil

from config.agent_config import config
from tools.agent_tools import AGENT_TOOLS

# Configure logging
logger = logging.getLogger(__name__)

# Prometheus metrics
agent_task_counter = Counter('agent_tasks_total', 'Total agent tasks executed', ['agent_type', 'status'])
agent_execution_time = Histogram('agent_execution_seconds', 'Agent task execution time', ['agent_type'])
agent_memory_usage = Gauge('agent_memory_usage_bytes', 'Agent memory usage', ['agent_type'])
agent_error_counter = Counter('agent_errors_total', 'Total agent errors', ['agent_type', 'error_type'])
system_health_gauge = Gauge('system_health_score', 'Overall system health score')

@dataclass
class AgentMetrics:
    """Agent performance metrics data class"""
    agent_type: str
    task_id: str
    start_time: datetime
    end_time: Optional[datetime] = None
    duration: Optional[float] = None
    status: str = "running"
    error_message: Optional[str] = None
    memory_usage: Optional[float] = None
    cpu_usage: Optional[float] = None
    task_type: Optional[str] = None
    input_data_size: Optional[int] = None
    output_data_size: Optional[int] = None

@dataclass 
class WorkflowMetrics:
    """Workflow performance metrics data class"""
    workflow_id: str
    workflow_type: str
    start_time: datetime
    end_time: Optional[datetime] = None
    duration: Optional[float] = None
    status: str = "running"
    agents_involved: List[str] = None
    tasks_completed: int = 0
    tasks_failed: int = 0
    error_message: Optional[str] = None

class AgentMonitor:
    """Monitors individual agent performance and activities"""
    
    def __init__(self):
        self.redis_client = redis.Redis.from_url(config.redis_url)
        self.active_tasks = {}
        
    def start_task_monitoring(self, agent_type: str, task_id: str, task_type: str = None, input_data: Dict = None) -> AgentMetrics:
        """Start monitoring an agent task"""
        metrics = AgentMetrics(
            agent_type=agent_type,
            task_id=task_id,
            start_time=datetime.now(),
            task_type=task_type,
            input_data_size=len(json.dumps(input_data)) if input_data else 0
        )
        
        self.active_tasks[task_id] = metrics
        
        # Store in Redis for persistence
        self.redis_client.setex(
            f"task_metrics_{task_id}",
            3600,  # 1 hour TTL
            json.dumps(asdict(metrics), default=str)
        )
        
        # Update Prometheus metrics
        agent_task_counter.labels(agent_type=agent_type, status='started').inc()
        
        logger.info(f"Started monitoring task {task_id} for agent {agent_type}")
        return metrics
    
    def end_task_monitoring(self, task_id: str, status: str = "completed", error_message: str = None, output_data: Dict = None):
        """End monitoring an agent task"""
        if task_id not in self.active_tasks:
            logger.warning(f"Task {task_id} not found in active tasks")
            return
        
        metrics = self.active_tasks[task_id]
        metrics.end_time = datetime.now()
        metrics.duration = (metrics.end_time - metrics.start_time).total_seconds()
        metrics.status = status
        metrics.error_message = error_message
        metrics.output_data_size = len(json.dumps(output_data)) if output_data else 0
        
        # Get current system metrics
        process = psutil.Process()
        metrics.memory_usage = process.memory_info().rss
        metrics.cpu_usage = process.cpu_percent()
        
        # Update Prometheus metrics
        agent_task_counter.labels(agent_type=metrics.agent_type, status=status).inc()
        agent_execution_time.labels(agent_type=metrics.agent_type).observe(metrics.duration)
        agent_memory_usage.labels(agent_type=metrics.agent_type).set(metrics.memory_usage)
        
        if status == "failed":
            agent_error_counter.labels(agent_type=metrics.agent_type, error_type="task_failure").inc()
        
        # Store final metrics in Redis
        self.redis_client.setex(
            f"task_metrics_{task_id}",
            86400,  # 24 hours TTL for completed tasks
            json.dumps(asdict(metrics), default=str)
        )
        
        # Remove from active tasks
        del self.active_tasks[task_id]
        
        logger.info(f"Completed monitoring task {task_id} - Duration: {metrics.duration:.2f}s, Status: {status}")
    
    @contextmanager
    def monitor_task(self, agent_type: str, task_id: str, task_type: str = None, input_data: Dict = None):
        """Context manager for automatic task monitoring"""
        self.start_task_monitoring(agent_type, task_id, task_type, input_data)
        try:
            yield
            self.end_task_monitoring(task_id, "completed")
        except Exception as e:
            self.end_task_monitoring(task_id, "failed", str(e))
            raise
    
    def get_task_metrics(self, task_id: str) -> Optional[Dict]:
        """Get metrics for a specific task"""
        try:
            data = self.redis_client.get(f"task_metrics_{task_id}")
            if data:
                return json.loads(data.decode('utf-8'))
            return None
        except Exception as e:
            logger.error(f"Error retrieving task metrics: {str(e)}")
            return None
    
    def get_agent_statistics(self, agent_type: str, time_period: str = "24h") -> Dict[str, Any]:
        """Get performance statistics for an agent"""
        try:
            # Define time windows
            time_windows = {
                "1h": timedelta(hours=1),
                "24h": timedelta(days=1),
                "7d": timedelta(days=7),
                "30d": timedelta(days=30)
            }
            
            window = time_windows.get(time_period, timedelta(days=1))
            cutoff_time = datetime.now() - window
            
            # Get all task metrics for this agent
            pattern = f"task_metrics_*"
            keys = self.redis_client.keys(pattern)
            
            agent_tasks = []
            for key in keys:
                try:
                    data = json.loads(self.redis_client.get(key).decode('utf-8'))
                    if (data.get('agent_type') == agent_type and 
                        datetime.fromisoformat(data.get('start_time')) >= cutoff_time):
                        agent_tasks.append(data)
                except Exception:
                    continue
            
            if not agent_tasks:
                return {
                    "agent_type": agent_type,
                    "time_period": time_period,
                    "total_tasks": 0,
                    "statistics": {}
                }
            
            # Calculate statistics
            completed_tasks = [t for t in agent_tasks if t.get('status') == 'completed']
            failed_tasks = [t for t in agent_tasks if t.get('status') == 'failed']
            
            durations = [float(t.get('duration', 0)) for t in completed_tasks if t.get('duration')]
            
            statistics = {
                "total_tasks": len(agent_tasks),
                "completed_tasks": len(completed_tasks),
                "failed_tasks": len(failed_tasks),
                "success_rate": len(completed_tasks) / len(agent_tasks) if agent_tasks else 0,
                "average_duration": sum(durations) / len(durations) if durations else 0,
                "min_duration": min(durations) if durations else 0,
                "max_duration": max(durations) if durations else 0,
                "total_processing_time": sum(durations),
                "error_rate": len(failed_tasks) / len(agent_tasks) if agent_tasks else 0
            }
            
            return {
                "agent_type": agent_type,
                "time_period": time_period,
                "total_tasks": len(agent_tasks),
                "statistics": statistics,
                "generated_at": datetime.now().isoformat()
            }
            
        except Exception as e:
            logger.error(f"Error generating agent statistics: {str(e)}")
            return {"error": str(e)}

class WorkflowMonitor:
    """Monitors workflow execution and performance"""
    
    def __init__(self):
        self.redis_client = redis.Redis.from_url(config.redis_url)
        self.active_workflows = {}
    
    def start_workflow_monitoring(self, workflow_id: str, workflow_type: str, agents_involved: List[str]) -> WorkflowMetrics:
        """Start monitoring a workflow"""
        metrics = WorkflowMetrics(
            workflow_id=workflow_id,
            workflow_type=workflow_type,
            start_time=datetime.now(),
            agents_involved=agents_involved or []
        )
        
        self.active_workflows[workflow_id] = metrics
        
        # Store in Redis
        self.redis_client.setex(
            f"workflow_metrics_{workflow_id}",
            3600, # 1 hour TTL
            json.dumps(asdict(metrics), default=str)
        )
        
        logger.info(f"Started monitoring workflow {workflow_id} of type {workflow_type}")
        return metrics
    
    def end_workflow_monitoring(self, workflow_id: str, status: str = "completed", tasks_completed: int = 0, tasks_failed: int = 0, error_message: str = None):
        """End monitoring a workflow"""
        if workflow_id not in self.active_workflows:
            logger.warning(f"Workflow {workflow_id} not found in active workflows")
            return
        
        metrics = self.active_workflows[workflow_id]
        metrics.end_time = datetime.now()
        metrics.duration = (metrics.end_time - metrics.start_time).total_seconds()
        metrics.status = status
        metrics.tasks_completed = tasks_completed
        metrics.tasks_failed = tasks_failed
        metrics.error_message = error_message
        
        # Store final metrics
        self.redis_client.setex(
            f"workflow_metrics_{workflow_id}",
            86400, # 24 hours TTL for completed workflows
            json.dumps(asdict(metrics), default=str)
        )
        
        # Remove from active workflows
        del self.active_workflows[workflow_id]
        
        logger.info(f"Completed monitoring workflow {workflow_id} - Duration: {metrics.duration:.2f}s, Status: {status}")

class SystemHealthMonitor:
    """Monitors overall system health and performance"""
    
    def __init__(self):
        self.redis_client = redis.Redis.from_url(config.redis_url)
        
    def check_system_health(self) -> Dict[str, Any]:
        """Perform comprehensive system health check"""
        health_score = 100
        issues = []
        
        # Check Redis connectivity
        try:
            self.redis_client.ping()
            redis_healthy = True
        except Exception as e:
            redis_healthy = False
            health_score -= 20
            issues.append(f"Redis connection failed: {str(e)}")
        
        # Check database connectivity
        try:
            db_result = AGENT_TOOLS["database_query"]._run("SELECT 1")
            db_healthy = db_result.get("success", False)
            if not db_healthy:
                health_score -= 30
                issues.append("Database connection failed")
        except Exception as e:
            db_healthy = False
            health_score -= 30
            issues.append(f"Database error: {str(e)}")
        
        # Check system resources
        cpu_percent = psutil.cpu_percent(interval=1)
        memory_percent = psutil.virtual_memory().percent
        disk_percent = psutil.disk_usage('/').percent
        
        if cpu_percent > 80:
            health_score -= 15
            issues.append(f"High CPU usage: {cpu_percent:.1f}%")
        
        if memory_percent > 80:
            health_score -= 15
            issues.append(f"High memory usage: {memory_percent:.1f}%")
        
        if disk_percent > 90:
            health_score -= 10
            issues.append(f"High disk usage: {disk_percent:.1f}%")
        
        # Check agent availability
        agent_status = {}
        for agent_type in ["hr_agent", "project_agent", "analytics_agent"]:
            try:
                # Simple availability check
                agent_status[agent_type] = "healthy"
            except Exception as e:
                agent_status[agent_type] = f"unhealthy: {str(e)}"
                health_score -= 10
                issues.append(f"Agent {agent_type} unavailable")
        
        health_status = {
            "overall_health_score": max(0, health_score),
            "status": "healthy" if health_score >= 80 else "degraded" if health_score >= 50 else "critical",
            "components": {
                "redis": "healthy" if redis_healthy else "unhealthy",
                "database": "healthy" if db_healthy else "unhealthy",
                "agents": agent_status
            },
            "system_resources": {
                "cpu_usage": cpu_percent,
                "memory_usage": memory_percent,
                "disk_usage": disk_percent
            },
            "issues": issues,
            "timestamp": datetime.now().isoformat()
        }
        
        # Update Prometheus metric
        system_health_gauge.set(health_score)
        
        # Store health status
        self.redis_client.setex(
            "system_health_status",
            300, # 5 minutes TTL
            json.dumps(health_status)
        )
        
        return health_status
    
    def get_system_metrics(self) -> Dict[str, Any]:
        """Get comprehensive system metrics"""
        try:
            # Get recent health status
            health_data = self.redis_client.get("system_health_status")
            health_status = json.loads(health_data.decode('utf-8')) if health_data else {}
            
            # Get active task count
            active_task_pattern = "task_metrics_*"
            active_tasks = len(self.redis_client.keys(active_task_pattern))
            
            # Get workflow metrics
            workflow_pattern = "workflow_metrics_*"
            active_workflows = len(self.redis_client.keys(workflow_pattern))
            
            return {
                "health_status": health_status,
                "active_tasks": active_tasks,
                "active_workflows": active_workflows,
                "uptime": time.time() - psutil.boot_time(),
                "generated_at": datetime.now().isoformat()
            }
            
        except Exception as e:
            logger.error(f"Error generating system metrics: {str(e)}")
            return {"error": str(e)}

# Global monitor instances
agent_monitor = AgentMonitor()
workflow_monitor = WorkflowMonitor()
system_health_monitor = SystemHealthMonitor()

def start_prometheus_server(port: int = 9090):
    """Start Prometheus metrics server"""
    try:
        start_http_server(port)
        logger.info(f"Prometheus metrics server started on port {port}")
    except Exception as e:
        logger.error(f"Failed to start Prometheus server: {str(e)}")

def get_monitoring_summary() -> Dict[str, Any]:
    """Get comprehensive monitoring summary"""
    return {
        "system_health": system_health_monitor.check_system_health(),
        "system_metrics": system_health_monitor.get_system_metrics(),
        "agent_statistics": {
            "hr_agent": agent_monitor.get_agent_statistics("hr_agent"),
            "project_agent": agent_monitor.get_agent_statistics("project_agent"),
            "analytics_agent": agent_monitor.get_agent_statistics("analytics_agent")
        }
    }