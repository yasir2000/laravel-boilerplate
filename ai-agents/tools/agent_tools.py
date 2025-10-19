"""
CrewAI Agent Tools Registry
Custom tools for Laravel HR system integration
"""

import json
import requests
import pandas as pd
from typing import Any, Dict, List, Optional
from datetime import datetime, timedelta
from crewai_tools import BaseTool
from sqlalchemy import create_engine, text
import redis
import smtplib
from email.mime.text import MimeType
from email.mime.multipart import MimeMultipart
from twilio.rest import Client as TwilioClient

from config.agent_config import config

class DatabaseQueryTool(BaseTool):
    """Tool for querying the Laravel database"""
    
    name: str = "Database Query Tool"
    description: str = "Execute SQL queries against the Laravel PostgreSQL database"
    
    def __init__(self):
        super().__init__()
        self.engine = create_engine(config.database_url)
    
    def _run(self, query: str, params: Optional[Dict] = None) -> Dict[str, Any]:
        """Execute database query and return results"""
        try:
            with self.engine.connect() as conn:
                result = conn.execute(text(query), params or {})
                
                if result.returns_rows:
                    columns = result.keys()
                    rows = result.fetchall()
                    return {
                        "success": True,
                        "data": [dict(zip(columns, row)) for row in rows],
                        "row_count": len(rows)
                    }
                else:
                    return {
                        "success": True,
                        "rows_affected": result.rowcount
                    }
        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }

class LaravelAPITool(BaseTool):
    """Tool for calling Laravel API endpoints"""
    
    name: str = "Laravel API Tool"
    description: str = "Make HTTP requests to Laravel API endpoints"
    
    def _run(self, endpoint: str, method: str = "GET", data: Optional[Dict] = None, headers: Optional[Dict] = None) -> Dict[str, Any]:
        """Make API request to Laravel application"""
        try:
            url = f"{config.laravel_api_url}/api/{endpoint.lstrip('/')}"
            
            default_headers = {
                "Authorization": f"Bearer {config.laravel_api_token}",
                "Content-Type": "application/json",
                "Accept": "application/json"
            }
            
            if headers:
                default_headers.update(headers)
            
            response = requests.request(
                method=method.upper(),
                url=url,
                json=data,
                headers=default_headers,
                timeout=30
            )
            
            return {
                "success": response.status_code < 400,
                "status_code": response.status_code,
                "data": response.json() if response.text else None,
                "headers": dict(response.headers)
            }
            
        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }

class EmailSenderTool(BaseTool):
    """Tool for sending emails"""
    
    name: str = "Email Sender Tool"  
    description: str = "Send emails through SMTP server"
    
    def _run(self, to_email: str, subject: str, body: str, is_html: bool = False) -> Dict[str, Any]:
        """Send email notification"""
        try:
            msg = MimeMultipart()
            msg['From'] = config.smtp_username
            msg['To'] = to_email
            msg['Subject'] = subject
            
            msg.attach(MimeText(body, 'html' if is_html else 'plain'))
            
            server = smtplib.SMTP(config.smtp_host, config.smtp_port)
            server.starttls()
            server.login(config.smtp_username, config.smtp_password)
            
            text = msg.as_string()
            server.sendmail(config.smtp_username, to_email, text)
            server.quit()
            
            return {
                "success": True,
                "message": f"Email sent successfully to {to_email}"
            }
            
        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }

class SMSSenderTool(BaseTool):
    """Tool for sending SMS messages"""
    
    name: str = "SMS Sender Tool"
    description: str = "Send SMS messages through Twilio"
    
    def __init__(self):
        super().__init__()
        self.client = TwilioClient(config.twilio_account_sid, config.twilio_auth_token)
    
    def _run(self, to_phone: str, message: str) -> Dict[str, Any]:
        """Send SMS notification"""
        try:
            message = self.client.messages.create(
                body=message,
                from_=config.twilio_phone_number,
                to=to_phone
            )
            
            return {
                "success": True,
                "message_sid": message.sid,
                "status": message.status
            }
            
        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }

class DataAnalyzerTool(BaseTool):
    """Tool for analyzing data and generating insights"""
    
    name: str = "Data Analyzer Tool"
    description: str = "Analyze datasets and generate business insights"
    
    def _run(self, query: str, analysis_type: str = "summary") -> Dict[str, Any]:
        """Analyze data from database query"""
        try:
            # Get data from database
            db_tool = DatabaseQueryTool()
            result = db_tool._run(query)
            
            if not result["success"]:
                return result
            
            # Convert to DataFrame for analysis
            df = pd.DataFrame(result["data"])
            
            if analysis_type == "summary":
                analysis = {
                    "total_rows": len(df),
                    "columns": list(df.columns),
                    "data_types": df.dtypes.to_dict(),
                    "missing_values": df.isnull().sum().to_dict(),
                    "summary_stats": df.describe().to_dict() if len(df) > 0 else {}
                }
            
            elif analysis_type == "trends":
                # Basic trend analysis for time-series data
                date_columns = df.select_dtypes(include=['datetime64']).columns
                analysis = {
                    "time_columns": list(date_columns),
                    "trends": {}
                }
                
                for col in date_columns:
                    df_sorted = df.sort_values(col)
                    analysis["trends"][col] = {
                        "start_date": str(df_sorted[col].min()),
                        "end_date": str(df_sorted[col].max()),
                        "time_span_days": (df_sorted[col].max() - df_sorted[col].min()).days
                    }
            
            else:
                analysis = {"message": f"Analysis type '{analysis_type}' not supported"}
            
            return {
                "success": True,
                "analysis": analysis,
                "row_count": len(df)
            }
            
        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }

class WorkflowEngineTool(BaseTool):
    """Tool for managing workflow operations"""
    
    name: str = "Workflow Engine Tool"
    description: str = "Create and manage automated workflows"
    
    def _run(self, action: str, workflow_data: Dict[str, Any]) -> Dict[str, Any]:
        """Execute workflow operations"""
        try:
            api_tool = LaravelAPITool()
            
            if action == "create":
                return api_tool._run("workflows", "POST", workflow_data)
            
            elif action == "trigger":
                workflow_id = workflow_data.get("workflow_id")
                return api_tool._run(f"workflows/{workflow_id}/trigger", "POST", workflow_data)
            
            elif action == "status":
                workflow_id = workflow_data.get("workflow_id")
                return api_tool._run(f"workflows/{workflow_id}/status", "GET")
            
            elif action == "approve":
                step_id = workflow_data.get("step_id")
                return api_tool._run(f"workflow-steps/{step_id}/approve", "POST", workflow_data)
            
            else:
                return {
                    "success": False,
                    "error": f"Unsupported workflow action: {action}"
                }
                
        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }

class MemoryStoreTool(BaseTool):
    """Tool for managing agent memory and context"""
    
    name: str = "Memory Store Tool"
    description: str = "Store and retrieve agent memory and context data"
    
    def __init__(self):
        super().__init__()
        self.redis_client = redis.Redis.from_url(config.redis_url)
    
    def _run(self, action: str, key: str, data: Optional[Any] = None, ttl: Optional[int] = None) -> Dict[str, Any]:
        """Manage memory operations"""
        try:
            if action == "set":
                if ttl:
                    self.redis_client.setex(key, ttl, json.dumps(data))
                else:
                    self.redis_client.set(key, json.dumps(data))
                return {"success": True, "message": f"Data stored with key: {key}"}
            
            elif action == "get":
                result = self.redis_client.get(key)
                if result:
                    return {
                        "success": True,
                        "data": json.loads(result.decode('utf-8'))
                    }
                else:
                    return {
                        "success": False,
                        "error": f"No data found for key: {key}"
                    }
            
            elif action == "delete":
                deleted = self.redis_client.delete(key)
                return {
                    "success": True,
                    "deleted": bool(deleted)
                }
            
            elif action == "exists":
                exists = self.redis_client.exists(key)
                return {
                    "success": True,
                    "exists": bool(exists)
                }
            
            else:
                return {
                    "success": False,
                    "error": f"Unsupported memory action: {action}"
                }
                
        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }

class ReportGeneratorTool(BaseTool):
    """Tool for generating business reports"""
    
    name: str = "Report Generator Tool"
    description: str = "Generate formatted business reports and analytics"
    
    def _run(self, report_type: str, data_query: str, format_type: str = "json") -> Dict[str, Any]:
        """Generate business report"""
        try:
            # Get data using analyzer tool
            analyzer = DataAnalyzerTool()
            data_result = analyzer._run(data_query, "summary")
            
            if not data_result["success"]:
                return data_result
            
            # Generate report based on type
            report_data = {
                "report_type": report_type,
                "generated_at": datetime.now().isoformat(),
                "data_summary": data_result["analysis"],
                "total_records": data_result["row_count"]
            }
            
            if report_type == "employee_summary":
                report_data.update({
                    "title": "Employee Summary Report",
                    "description": "Overview of employee data and metrics"
                })
            
            elif report_type == "project_status":
                report_data.update({
                    "title": "Project Status Report", 
                    "description": "Current status of all active projects"
                })
            
            elif report_type == "attendance_analysis":
                report_data.update({
                    "title": "Attendance Analysis Report",
                    "description": "Employee attendance patterns and insights"
                })
            
            return {
                "success": True,
                "report": report_data,
                "format": format_type
            }
            
        except Exception as e:
            return {
                "success": False,
                "error": str(e)
            }

# Tool registry for easy access
AGENT_TOOLS = {
    "database_query": DatabaseQueryTool(),
    "laravel_api": LaravelAPITool(), 
    "email_sender": EmailSenderTool(),
    "sms_sender": SMSSenderTool(),
    "data_analyzer": DataAnalyzerTool(),
    "workflow_engine": WorkflowEngineTool(),
    "memory_store": MemoryStoreTool(),
    "report_generator": ReportGeneratorTool()
}