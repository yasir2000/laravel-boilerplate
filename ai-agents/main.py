"""
CrewAI Agent System Main Application
Entry point for the collaborative AI agent system
"""

import asyncio
import logging
import signal
import sys
from typing import Dict, Any
import uvicorn
from contextlib import asynccontextmanager

from src.agent_server import app
from src.monitoring import start_prometheus_server, system_health_monitor, get_monitoring_summary
from config.agent_config import config
from agents.core_agents import AGENTS
from workflows.collaborative_workflows import WORKFLOWS

# Configure logging
logging.basicConfig(
    level=getattr(logging, config.agent_log_level),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout),
        logging.FileHandler('logs/agent_system.log') if config.environment == 'production' else logging.NullHandler()
    ]
)

logger = logging.getLogger(__name__)

class AgentSystemManager:
    """Manages the entire AI agent system lifecycle"""
    
    def __init__(self):
        self.running = False
        self.health_check_task = None
        
    async def startup(self):
        """Initialize and start the agent system"""
        logger.info("Starting CrewAI Agent System...")
        
        try:
            # Perform initial health check
            health_status = system_health_monitor.check_system_health()
            logger.info(f"System health check completed. Score: {health_status['overall_health_score']}")
            
            if health_status['overall_health_score'] < 50:
                logger.warning("System health score is low. Issues detected:")
                for issue in health_status.get('issues', []):
                    logger.warning(f"  - {issue}")
            
            # Start Prometheus metrics server
            if config.environment == 'production':
                start_prometheus_server(9090)
            
            # Initialize agents
            logger.info("Initializing agents...")
            for agent_type, agent_instance in AGENTS.items():
                logger.info(f"Agent {agent_type} initialized successfully")
            
            # Initialize workflows
            logger.info("Initializing workflows...")
            for workflow_type in WORKFLOWS.keys():
                logger.info(f"Workflow {workflow_type} ready")
            
            # Start periodic health checks
            self.health_check_task = asyncio.create_task(self.periodic_health_check())
            
            self.running = True
            logger.info("CrewAI Agent System started successfully")
            
        except Exception as e:
            logger.error(f"Failed to start agent system: {str(e)}")
            raise
    
    async def shutdown(self):
        """Gracefully shutdown the agent system"""
        logger.info("Shutting down CrewAI Agent System...")
        
        self.running = False
        
        # Cancel health check task
        if self.health_check_task:
            self.health_check_task.cancel()
            try:
                await self.health_check_task
            except asyncio.CancelledError:
                pass
        
        # Perform final system status log
        try:
            final_status = get_monitoring_summary()
            logger.info(f"Final system status: {final_status['system_health']['status']}")
        except Exception as e:
            logger.error(f"Error getting final status: {str(e)}")
        
        logger.info("CrewAI Agent System shutdown completed")
    
    async def periodic_health_check(self):
        """Perform periodic system health checks"""
        while self.running:
            try:
                await asyncio.sleep(300)  # Check every 5 minutes
                
                health_status = system_health_monitor.check_system_health()
                health_score = health_status['overall_health_score']
                
                if health_score < 70:
                    logger.warning(f"System health degraded. Score: {health_score}")
                    for issue in health_status.get('issues', []):
                        logger.warning(f"  - {issue}")
                elif health_score >= 90:
                    logger.debug(f"System healthy. Score: {health_score}")
                
            except asyncio.CancelledError:
                break
            except Exception as e:
                logger.error(f"Error in health check: {str(e)}")
                await asyncio.sleep(60)  # Wait 1 minute before retrying

# Global system manager
system_manager = AgentSystemManager()

@asynccontextmanager
async def lifespan(app):
    """FastAPI lifespan context manager"""
    # Startup
    await system_manager.startup()
    yield
    # Shutdown
    await system_manager.shutdown()

# Update FastAPI app with lifespan
app.router.lifespan_context = lifespan

def signal_handler(signum, frame):
    """Handle shutdown signals"""
    logger.info(f"Received signal {signum}. Initiating graceful shutdown...")
    sys.exit(0)

def main():
    """Main entry point"""
    # Set up signal handlers
    signal.signal(signal.SIGINT, signal_handler)
    signal.signal(signal.SIGTERM, signal_handler)
    
    # Configure uvicorn
    uvicorn_config = {
        "app": "main:app",
        "host": "0.0.0.0",
        "port": 8001,
        "reload": config.debug and config.environment == 'development',
        "log_level": config.agent_log_level.lower(),
        "access_log": config.debug,
        "workers": 1 if config.debug else 4
    }
    
    logger.info(f"Starting server with config: {uvicorn_config}")
    
    try:
        uvicorn.run(**uvicorn_config)
    except KeyboardInterrupt:
        logger.info("Received KeyboardInterrupt. Shutting down...")
    except Exception as e:
        logger.error(f"Server error: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    main()