"""
Specialized AI Agents for HR System
Extended agent implementations for comprehensive HR automation
"""

from crewai import Agent
from typing import Dict, Any, List, Optional
from datetime import datetime, timedelta
import logging

from config.agent_config import config
from tools.agent_tools import AGENT_TOOLS

logger = logging.getLogger(__name__)

class ITSupportAgent:
    """IT Support Agent for system provisioning and technical tasks"""
    
    def __init__(self):
        self.agent = Agent(
            role="IT Support Specialist",
            goal="Provide technical support, system provisioning, and IT infrastructure management for employees",
            backstory="""You are an experienced IT support professional with expertise in system 
            administration, account provisioning, and technical troubleshooting. You excel at 
            automating IT processes while ensuring security and compliance standards.""",
            verbose=config.debug,
            allow_delegation=True,
            tools=[
                AGENT_TOOLS["database_query"],
                AGENT_TOOLS["laravel_api"],
                AGENT_TOOLS["email_sender"],
                AGENT_TOOLS["memory_store"]
            ]
        )
    
    def provision_user_accounts(self, employee_data: Dict[str, Any]) -> Dict[str, Any]:
        """Provision system accounts and access for new employee"""
        try:
            employee_id = employee_data.get('employee_id')
            name = employee_data.get('name')
            email = employee_data.get('email')
            department = employee_data.get('department')
            position = employee_data.get('position')
            
            # Create user account
            account_result = AGENT_TOOLS["laravel_api"]._run(
                "users",
                "POST",
                {
                    "name": name,
                    "email": email,
                    "employee_id": employee_id,
                    "department": department,
                    "position": position,
                    "status": "active"
                }
            )
            
            if not account_result.get("success"):
                return {"success": False, "error": "Failed to create user account"}
            
            # Generate temporary password
            temp_password = f"Welcome{employee_id}!"
            
            # Set up system permissions based on department and role
            permissions = self._determine_permissions(department, position)
            
            permission_result = AGENT_TOOLS["laravel_api"]._run(
                f"users/{account_result['data']['id']}/permissions",
                "POST",
                {"permissions": permissions}
            )
            
            # Send account details to employee
            email_body = f"""
            Welcome to our company, {name}!
            
            Your system accounts have been provisioned:
            - Username: {email}
            - Temporary Password: {temp_password}
            
            Please log in and change your password at: {config.laravel_api_url}/login
            
            Your access includes:
            {chr(10).join([f"- {perm}" for perm in permissions])}
            
            If you have any technical issues, please contact IT support.
            
            Best regards,
            IT Support Team
            """
            
            AGENT_TOOLS["email_sender"]._run(
                email,
                "Your System Account Has Been Created",
                email_body
            )
            
            # Store account provisioning details
            AGENT_TOOLS["memory_store"]._run(
                "set",
                f"it_provisioning_{employee_id}",
                {
                    "employee_id": employee_id,
                    "user_id": account_result['data']['id'],
                    "permissions": permissions,
                    "provisioned_at": datetime.now().isoformat(),
                    "status": "completed"
                },
                ttl=86400
            )
            
            return {
                "success": True,
                "user_id": account_result['data']['id'],
                "permissions": permissions,
                "message": f"System accounts provisioned for {name}"
            }
            
        except Exception as e:
            logger.error(f"Error in IT provisioning: {str(e)}")
            return {"success": False, "error": str(e)}
    
    def _determine_permissions(self, department: str, position: str) -> List[str]:
        """Determine system permissions based on department and position"""
        base_permissions = ["read_profile", "update_profile", "view_company_directory"]
        
        department_permissions = {
            "HR": ["manage_employees", "view_payroll", "manage_benefits"],
            "Finance": ["view_financial_data", "manage_payroll", "generate_reports"],
            "IT": ["manage_systems", "user_administration", "technical_support"],
            "Management": ["view_all_reports", "approve_requests", "manage_departments"]
        }
        
        position_permissions = {
            "Manager": ["approve_leave", "view_team_reports", "manage_team"],
            "Director": ["approve_budget", "strategic_planning", "executive_reports"],
            "Administrator": ["system_admin", "manage_policies", "audit_access"]
        }
        
        permissions = base_permissions.copy()
        permissions.extend(department_permissions.get(department, []))
        permissions.extend(position_permissions.get(position, []))
        
        return list(set(permissions))  # Remove duplicates

class ComplianceAgent:
    """Compliance Agent for regulatory compliance and document verification"""
    
    def __init__(self):
        self.agent = Agent(
            role="Compliance Officer",
            goal="Ensure regulatory compliance, document verification, and policy adherence across all HR processes",
            backstory="""You are a meticulous compliance professional with deep knowledge of 
            employment law, data protection regulations, and corporate policies. You excel at 
            identifying compliance risks and ensuring all processes meet regulatory standards.""",
            verbose=config.debug,
            allow_delegation=True,
            tools=[
                AGENT_TOOLS["database_query"],
                AGENT_TOOLS["laravel_api"],
                AGENT_TOOLS["email_sender"],
                AGENT_TOOLS["memory_store"]
            ]
        )
    
    def verify_employee_documents(self, employee_data: Dict[str, Any]) -> Dict[str, Any]:
        """Verify employee documents for compliance"""
        try:
            employee_id = employee_data.get('employee_id')
            
            # Get employee documents
            docs_result = AGENT_TOOLS["database_query"]._run(
                "SELECT * FROM employee_documents WHERE employee_id = :employee_id",
                {"employee_id": employee_id}
            )
            
            if not docs_result.get("success"):
                return {"success": False, "error": "Failed to retrieve employee documents"}
            
            documents = docs_result.get("data", [])
            
            # Define required documents
            required_docs = [
                "government_id",
                "tax_form",
                "emergency_contact",
                "bank_details",
                "employment_contract"
            ]
            
            verification_results = {}
            missing_documents = []
            
            for doc_type in required_docs:
                doc = next((d for d in documents if d['document_type'] == doc_type), None)
                
                if not doc:
                    missing_documents.append(doc_type)
                    verification_results[doc_type] = {"status": "missing", "verified": False}
                else:
                    # Perform document verification
                    verification = self._verify_document(doc)
                    verification_results[doc_type] = verification
            
            # Update compliance status
            compliance_status = "compliant" if not missing_documents else "pending"
            
            AGENT_TOOLS["database_query"]._run(
                "UPDATE employees SET compliance_status = :status WHERE id = :employee_id",
                {"status": compliance_status, "employee_id": employee_id}
            )
            
            # Store verification results
            AGENT_TOOLS["memory_store"]._run(
                "set",
                f"compliance_verification_{employee_id}",
                {
                    "employee_id": employee_id,
                    "verification_results": verification_results,
                    "missing_documents": missing_documents,
                    "compliance_status": compliance_status,
                    "verified_at": datetime.now().isoformat()
                },
                ttl=86400
            )
            
            # Send notification if documents are missing
            if missing_documents:
                self._send_missing_documents_notification(employee_data, missing_documents)
            
            return {
                "success": True,
                "compliance_status": compliance_status,
                "verification_results": verification_results,
                "missing_documents": missing_documents,
                "message": f"Document verification completed for employee {employee_id}"
            }
            
        except Exception as e:
            logger.error(f"Error in compliance verification: {str(e)}")
            return {"success": False, "error": str(e)}
    
    def _verify_document(self, document: Dict[str, Any]) -> Dict[str, Any]:
        """Verify individual document"""
        # Basic document verification logic
        verification = {
            "status": "verified",
            "verified": True,
            "verified_at": datetime.now().isoformat(),
            "issues": []
        }
        
        # Check document expiration
        if document.get('expiry_date'):
            expiry_date = datetime.fromisoformat(document['expiry_date'])
            if expiry_date <= datetime.now():
                verification["status"] = "expired"
                verification["verified"] = False
                verification["issues"].append("Document has expired")
        
        # Check document completeness
        if not document.get('file_path') or not document.get('file_name'):
            verification["status"] = "incomplete"
            verification["verified"] = False
            verification["issues"].append("Document file is missing")
        
        return verification
    
    def _send_missing_documents_notification(self, employee_data: Dict[str, Any], missing_docs: List[str]):
        """Send notification about missing documents"""
        email_body = f"""
        Dear {employee_data.get('name')},
        
        We are missing the following documents for your employment file:
        
        {chr(10).join([f"- {doc.replace('_', ' ').title()}" for doc in missing_docs])}
        
        Please upload these documents through the employee portal to complete your onboarding process.
        
        If you have any questions, please contact HR.
        
        Best regards,
        HR Compliance Team
        """
        
        AGENT_TOOLS["email_sender"]._run(
            employee_data.get('email'),
            "Missing Employment Documents",
            email_body
        )

class TrainingAgent:
    """Training Agent for employee training and development"""
    
    def __init__(self):
        self.agent = Agent(
            role="Training Coordinator",
            goal="Manage employee training programs, orientation sessions, and professional development",
            backstory="""You are an experienced training professional with expertise in learning 
            and development, curriculum design, and training delivery. You excel at creating 
            personalized learning paths and ensuring effective knowledge transfer.""",
            verbose=config.debug,
            allow_delegation=True,
            tools=[
                AGENT_TOOLS["database_query"],
                AGENT_TOOLS["laravel_api"],
                AGENT_TOOLS["email_sender"],
                AGENT_TOOLS["memory_store"]
            ]
        )
    
    def schedule_orientation_training(self, employee_data: Dict[str, Any]) -> Dict[str, Any]:
        """Schedule orientation and training sessions for new employee"""
        try:
            employee_id = employee_data.get('employee_id')
            name = employee_data.get('name')
            department = employee_data.get('department')
            position = employee_data.get('position')
            start_date = employee_data.get('start_date', datetime.now().date())
            
            # Determine required training modules
            training_modules = self._get_required_training(department, position)
            
            # Schedule training sessions
            training_schedule = []
            current_date = datetime.strptime(str(start_date), "%Y-%m-%d") + timedelta(days=1)
            
            for module in training_modules:
                session = {
                    "module_name": module["name"],
                    "module_type": module["type"],
                    "duration": module["duration"],
                    "scheduled_date": current_date.strftime("%Y-%m-%d"),
                    "scheduled_time": "09:00:00",
                    "trainer": module.get("trainer", "HR Training Team"),
                    "location": module.get("location", "Training Room A"),
                    "status": "scheduled"
                }
                
                # Create training session in database
                session_result = AGENT_TOOLS["laravel_api"]._run(
                    "training-sessions",
                    "POST",
                    {
                        "employee_id": employee_id,
                        "module_name": session["module_name"],
                        "scheduled_date": session["scheduled_date"],
                        "scheduled_time": session["scheduled_time"],
                        "duration": session["duration"],
                        "trainer": session["trainer"],
                        "location": session["location"],
                        "status": "scheduled"
                    }
                )
                
                if session_result.get("success"):
                    session["session_id"] = session_result["data"]["id"]
                
                training_schedule.append(session)
                current_date += timedelta(days=1)
            
            # Send training schedule to employee
            self._send_training_schedule_email(employee_data, training_schedule)
            
            # Store training plan
            AGENT_TOOLS["memory_store"]._run(
                "set",
                f"training_schedule_{employee_id}",
                {
                    "employee_id": employee_id,
                    "training_schedule": training_schedule,
                    "total_modules": len(training_modules),
                    "estimated_completion": (current_date - timedelta(days=1)).strftime("%Y-%m-%d"),
                    "created_at": datetime.now().isoformat()
                },
                ttl=86400
            )
            
            return {
                "success": True,
                "training_schedule": training_schedule,
                "total_modules": len(training_modules),
                "estimated_completion": (current_date - timedelta(days=1)).strftime("%Y-%m-%d"),
                "message": f"Training schedule created for {name}"
            }
            
        except Exception as e:
            logger.error(f"Error in training scheduling: {str(e)}")
            return {"success": False, "error": str(e)}
    
    def _get_required_training(self, department: str, position: str) -> List[Dict[str, Any]]:
        """Get required training modules based on department and position"""
        base_training = [
            {
                "name": "Company Orientation",
                "type": "orientation",
                "duration": 4,  # hours
                "description": "Introduction to company culture, values, and policies"
            },
            {
                "name": "IT Systems Training",
                "type": "technical",
                "duration": 2,
                "description": "Training on company IT systems and tools"
            },
            {
                "name": "Health & Safety",
                "type": "compliance",
                "duration": 1,
                "description": "Workplace health and safety guidelines"
            }
        ]
        
        department_training = {
            "HR": [
                {"name": "HR Policies & Procedures", "type": "functional", "duration": 3},
                {"name": "Employment Law Basics", "type": "compliance", "duration": 2},
                {"name": "HRIS System Training", "type": "technical", "duration": 2}
            ],
            "Finance": [
                {"name": "Financial Systems Training", "type": "technical", "duration": 4},
                {"name": "Compliance & Audit Procedures", "type": "compliance", "duration": 2},
                {"name": "Financial Reporting Standards", "type": "functional", "duration": 3}
            ],
            "IT": [
                {"name": "System Administration", "type": "technical", "duration": 8},
                {"name": "Security Protocols", "type": "compliance", "duration": 2},
                {"name": "Infrastructure Overview", "type": "technical", "duration": 4}
            ]
        }
        
        position_training = {
            "Manager": [
                {"name": "Leadership Fundamentals", "type": "leadership", "duration": 4},
                {"name": "Performance Management", "type": "management", "duration": 3},
                {"name": "Team Building", "type": "leadership", "duration": 2}
            ],
            "Director": [
                {"name": "Strategic Planning", "type": "executive", "duration": 6},
                {"name": "Budget Management", "type": "financial", "duration": 4},
                {"name": "Executive Communication", "type": "leadership", "duration": 3}
            ]
        }
        
        training_modules = base_training.copy()
        training_modules.extend(department_training.get(department, []))
        training_modules.extend(position_training.get(position, []))
        
        return training_modules
    
    def _send_training_schedule_email(self, employee_data: Dict[str, Any], schedule: List[Dict[str, Any]]):
        """Send training schedule email to employee"""
        schedule_text = "\n".join([
            f"- {session['module_name']}: {session['scheduled_date']} at {session['scheduled_time']} ({session['duration']} hours)"
            for session in schedule
        ])
        
        email_body = f"""
        Dear {employee_data.get('name')},
        
        Welcome to our company! We have scheduled your orientation and training sessions:
        
        {schedule_text}
        
        Training Details:
        - All sessions will be held in the locations specified
        - Please bring a notebook and pen for taking notes
        - Training materials will be provided
        - If you need to reschedule any session, please contact HR
        
        We look forward to your successful onboarding!
        
        Best regards,
        Training Team
        """
        
        AGENT_TOOLS["email_sender"]._run(
            employee_data.get('email'),
            "Your Training Schedule",
            email_body
        )

class PayrollAgent:
    """Payroll Agent for payroll setup and processing"""
    
    def __init__(self):
        self.agent = Agent(
            role="Payroll Specialist",
            goal="Manage payroll setup, processing, and integration with external payroll systems",
            backstory="""You are an experienced payroll professional with expertise in payroll 
            processing, tax calculations, and benefits administration. You excel at ensuring 
            accurate and timely payroll operations while maintaining compliance.""",
            verbose=config.debug,
            allow_delegation=True,
            tools=[
                AGENT_TOOLS["database_query"],
                AGENT_TOOLS["laravel_api"],
                AGENT_TOOLS["email_sender"],
                AGENT_TOOLS["memory_store"]
            ]
        )
    
    def setup_employee_payroll(self, employee_data: Dict[str, Any]) -> Dict[str, Any]:
        """Set up payroll for new employee"""
        try:
            employee_id = employee_data.get('employee_id')
            salary = employee_data.get('salary')
            department = employee_data.get('department')
            position = employee_data.get('position')
            start_date = employee_data.get('start_date')
            
            # Calculate payroll components
            payroll_setup = self._calculate_payroll_components(salary, department, position)
            
            # Create payroll record
            payroll_result = AGENT_TOOLS["laravel_api"]._run(
                "payroll-setup",
                "POST",
                {
                    "employee_id": employee_id,
                    "basic_salary": payroll_setup["basic_salary"],
                    "allowances": payroll_setup["allowances"],
                    "deductions": payroll_setup["deductions"],
                    "tax_bracket": payroll_setup["tax_bracket"],
                    "benefits": payroll_setup["benefits"],
                    "pay_frequency": payroll_setup["pay_frequency"],
                    "effective_date": start_date,
                    "status": "active"
                }
            )
            
            if not payroll_result.get("success"):
                return {"success": False, "error": "Failed to create payroll record"}
            
            # Store payroll setup details
            AGENT_TOOLS["memory_store"]._run(
                "set",
                f"payroll_setup_{employee_id}",
                {
                    "employee_id": employee_id,
                    "payroll_id": payroll_result["data"]["id"],
                    "payroll_setup": payroll_setup,
                    "setup_date": datetime.now().isoformat(),
                    "status": "completed"
                },
                ttl=86400
            )
            
            return {
                "success": True,
                "payroll_id": payroll_result["data"]["id"],
                "payroll_setup": payroll_setup,
                "message": f"Payroll setup completed for employee {employee_id}"
            }
            
        except Exception as e:
            logger.error(f"Error in payroll setup: {str(e)}")
            return {"success": False, "error": str(e)}
    
    def _calculate_payroll_components(self, salary: float, department: str, position: str) -> Dict[str, Any]:
        """Calculate payroll components based on salary and position"""
        # Base payroll calculation
        basic_salary = salary
        
        # Allowances based on position and department
        allowances = {
            "transportation": min(500, salary * 0.05),  # 5% or max $500
            "meal": 200,
            "communication": 100 if position in ["Manager", "Director"] else 50
        }
        
        # Department-specific allowances
        if department == "IT":
            allowances["technical"] = 300
        elif department == "Sales":
            allowances["commission_base"] = salary * 0.1
        
        # Benefits calculation
        benefits = {
            "health_insurance": salary * 0.08,  # 8% of salary
            "retirement_401k": salary * 0.06,  # 6% company match
            "life_insurance": min(100, salary * 0.001)  # 0.1% or max $100
        }
        
        # Tax calculations (simplified)
        tax_bracket = self._determine_tax_bracket(salary)
        
        # Deductions
        deductions = {
            "federal_tax": salary * tax_bracket["federal_rate"],
            "state_tax": salary * tax_bracket["state_rate"],
            "social_security": salary * 0.062,  # 6.2%
            "medicare": salary * 0.0145,  # 1.45%
            "health_insurance_employee": salary * 0.02  # 2% employee contribution
        }
        
        return {
            "basic_salary": basic_salary,
            "allowances": allowances,
            "benefits": benefits,
            "deductions": deductions,
            "tax_bracket": tax_bracket,
            "pay_frequency": "bi-weekly",
            "net_pay": basic_salary + sum(allowances.values()) - sum(deductions.values())
        }
    
    def _determine_tax_bracket(self, salary: float) -> Dict[str, Any]:
        """Determine tax bracket based on salary"""
        if salary <= 50000:
            return {"bracket": "low", "federal_rate": 0.12, "state_rate": 0.05}
        elif salary <= 100000:
            return {"bracket": "medium", "federal_rate": 0.22, "state_rate": 0.06}
        else:
            return {"bracket": "high", "federal_rate": 0.32, "state_rate": 0.08}
    
    async def detect_payroll_exceptions(self, payroll_data: Dict[str, Any]) -> Dict[str, Any]:
        """Detect payroll exceptions and anomalies"""
        try:
            payroll_period = payroll_data.get('payroll_period')
            
            # Query payroll data for anomaly detection
            payroll_query = """
            SELECT e.id, e.name, e.salary, e.hourly_rate,
                   p.gross_pay, p.net_pay, p.hours_worked, p.overtime_hours,
                   p.deductions, p.bonuses, p.commissions
            FROM employees e
            JOIN payroll_records p ON e.id = p.employee_id
            WHERE p.payroll_period = :period
            """
            
            payroll_result = AGENT_TOOLS["database_query"]._run(
                payroll_query, {"period": payroll_period}
            )
            
            if not payroll_result.get("success"):
                return {"success": False, "error": "Failed to retrieve payroll data"}
            
            exceptions = []
            for record in payroll_result["data"]:
                # Check for salary vs gross pay discrepancies
                expected_gross = record["salary"] / 26 if record["salary"] else record["hourly_rate"] * record["hours_worked"]
                if abs(record["gross_pay"] - expected_gross) > 50:  # $50 threshold
                    exceptions.append({
                        "employee_id": record["id"],
                        "employee_name": record["name"],
                        "exception_type": "gross_pay_discrepancy",
                        "expected": expected_gross,
                        "actual": record["gross_pay"],
                        "variance": record["gross_pay"] - expected_gross
                    })
                
                # Check for excessive overtime
                if record["overtime_hours"] > 20:
                    exceptions.append({
                        "employee_id": record["id"],
                        "employee_name": record["name"],
                        "exception_type": "excessive_overtime",
                        "overtime_hours": record["overtime_hours"],
                        "requires_approval": True
                    })
                
                # Check for unusual deductions
                if record["deductions"] > record["gross_pay"] * 0.5:
                    exceptions.append({
                        "employee_id": record["id"],
                        "employee_name": record["name"],
                        "exception_type": "excessive_deductions",
                        "deduction_amount": record["deductions"],
                        "gross_pay": record["gross_pay"]
                    })
            
            return {
                "success": True,
                "exceptions_found": len(exceptions) > 0,
                "exceptions": exceptions,
                "total_exceptions": len(exceptions)
            }
            
        except Exception as e:
            logger.error(f"Error detecting payroll exceptions: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def apply_payroll_adjustments(self, adjustment_data: Dict[str, Any]) -> Dict[str, Any]:
        """Apply approved payroll adjustments"""
        try:
            approved_resolutions = adjustment_data.get('approved_resolutions', [])
            payroll_period = adjustment_data.get('payroll_period')
            
            adjustments_applied = []
            
            for resolution in approved_resolutions:
                if resolution.get('approved'):
                    # Apply the adjustment to payroll record
                    update_query = """
                    UPDATE payroll_records 
                    SET gross_pay = :new_gross_pay,
                        net_pay = :new_net_pay,
                        adjustment_reason = :reason,
                        adjusted_at = NOW()
                    WHERE employee_id = :employee_id AND payroll_period = :period
                    """
                    
                    adjustment_result = AGENT_TOOLS["database_query"]._run(
                        update_query,
                        {
                            "new_gross_pay": resolution.get('adjusted_gross_pay'),
                            "new_net_pay": resolution.get('adjusted_net_pay'),
                            "reason": resolution.get('adjustment_reason'),
                            "employee_id": resolution.get('employee_id'),
                            "period": payroll_period
                        }
                    )
                    
                    adjustments_applied.append({
                        "employee_id": resolution.get('employee_id'),
                        "adjustment_type": resolution.get('exception_type'),
                        "success": adjustment_result.get('success')
                    })
            
            return {
                "success": True,
                "adjustments_applied": len(adjustments_applied),
                "details": adjustments_applied
            }
            
        except Exception as e:
            logger.error(f"Error applying payroll adjustments: {str(e)}")
            return {"success": False, "error": str(e)}

class LeaveProcessingAgent:
    """Agent specialized in leave request processing and workflow management"""
    
    def __init__(self):
        self.agent = Agent(
            role="Leave Processing Specialist",
            goal="Process leave requests efficiently with proper validation and workflow management",
            backstory="""You are a leave management specialist with expertise in HR policies, 
            leave regulations, and workflow automation. You ensure all leave requests are processed
            according to company policies and legal requirements.""",
            verbose=True
        )
    
    async def process_leave_application(self, leave_data: Dict[str, Any]) -> Dict[str, Any]:
        """Process comprehensive leave application"""
        try:
            # Validate leave eligibility
            eligibility_check = await self._check_leave_eligibility(leave_data)
            
            if not eligibility_check.get("eligible"):
                return {
                    "success": False,
                    "error": "Employee not eligible for leave",
                    "details": eligibility_check
                }
            
            # Calculate leave impact
            impact_analysis = await self._analyze_leave_impact(leave_data)
            
            # Create leave workflow
            workflow_data = {
                "leave_request_id": leave_data.get("leave_request_id"),
                "employee_id": leave_data.get("employee_id"),
                "leave_type": leave_data.get("leave_type"),
                "eligibility": eligibility_check,
                "impact_analysis": impact_analysis,
                "status": "processing"
            }
            
            return {
                "success": True,
                "workflow_data": workflow_data,
                "next_steps": ["manager_approval", "coverage_assignment", "calendar_update"]
            }
            
        except Exception as e:
            logger.error(f"Error processing leave application: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _check_leave_eligibility(self, leave_data: Dict[str, Any]) -> Dict[str, Any]:
        """Check employee eligibility for requested leave"""
        employee_id = leave_data.get("employee_id")
        leave_type = leave_data.get("leave_type")
        
        # Get employee leave balance
        balance_query = """
        SELECT lb.*, e.hire_date, e.employment_status
        FROM leave_balances lb
        JOIN employees e ON lb.employee_id = e.id
        WHERE lb.employee_id = :employee_id AND lb.leave_type = :leave_type
        """
        
        balance_result = AGENT_TOOLS["database_query"]._run(
            balance_query, {"employee_id": employee_id, "leave_type": leave_type}
        )
        
        if balance_result.get("success") and balance_result.get("data"):
            balance_info = balance_result["data"][0]
            requested_days = (
                datetime.strptime(leave_data.get("end_date"), "%Y-%m-%d") -
                datetime.strptime(leave_data.get("start_date"), "%Y-%m-%d")
            ).days + 1
            
            return {
                "eligible": balance_info["available_days"] >= requested_days,
                "available_days": balance_info["available_days"],
                "requested_days": requested_days,
                "employment_status": balance_info["employment_status"]
            }
        else:
            return {"eligible": False, "error": "Leave balance not found"}
    
    async def _analyze_leave_impact(self, leave_data: Dict[str, Any]) -> Dict[str, Any]:
        """Analyze the impact of leave on team and projects"""
        employee_id = leave_data.get("employee_id")
        start_date = leave_data.get("start_date")
        end_date = leave_data.get("end_date")
        
        # Check for project deadlines during leave period
        project_query = """
        SELECT p.id, p.name, p.deadline, t.name as task_name, t.deadline as task_deadline
        FROM projects p
        JOIN tasks t ON p.id = t.project_id
        WHERE t.assigned_to = :employee_id
        AND (t.deadline BETWEEN :start_date AND :end_date)
        """
        
        project_result = AGENT_TOOLS["database_query"]._run(
            project_query,
            {"employee_id": employee_id, "start_date": start_date, "end_date": end_date}
        )
        
        impact_level = "low"
        affected_projects = []
        
        if project_result.get("success") and project_result.get("data"):
            affected_projects = project_result["data"]
            impact_level = "high" if len(affected_projects) > 2 else "medium"
        
        return {
            "impact_level": impact_level,
            "affected_projects": affected_projects,
            "coverage_required": impact_level in ["medium", "high"]
        }

class CoverageAgent:
    """Agent specialized in finding and managing employee coverage"""
    
    def __init__(self):
        self.agent = Agent(
            role="Coverage Coordinator",
            goal="Find optimal coverage solutions for employee absences",
            backstory="""You are a coverage coordination specialist with expertise in resource 
            management and team optimization. You excel at finding the best coverage solutions
            while maintaining team productivity and employee satisfaction.""",
            verbose=True
        )
    
    async def find_optimal_coverage(self, coverage_request: Dict[str, Any]) -> Dict[str, Any]:
        """Find optimal coverage for employee absence"""
        try:
            employee_id = coverage_request.get("employee_id")
            start_date = coverage_request.get("start_date")
            end_date = coverage_request.get("end_date")
            required_skills = coverage_request.get("required_skills", [])
            
            # Find available team members with matching skills
            coverage_query = """
            SELECT e.id, e.name, e.email, e.skills, e.current_workload,
                   d.name as department_name
            FROM employees e
            JOIN departments d ON e.department_id = d.id
            WHERE e.id != :employee_id
            AND e.active = true
            AND e.current_workload < 90
            AND NOT EXISTS (
                SELECT 1 FROM leave_requests lr
                WHERE lr.employee_id = e.id
                AND lr.status = 'approved'
                AND (lr.start_date <= :end_date AND lr.end_date >= :start_date)
            )
            ORDER BY e.current_workload ASC, e.experience_level DESC
            """
            
            coverage_result = AGENT_TOOLS["database_query"]._run(
                coverage_query,
                {"employee_id": employee_id, "start_date": start_date, "end_date": end_date}
            )
            
            if coverage_result.get("success") and coverage_result.get("data"):
                # Score potential coverage based on skills match and availability
                scored_candidates = []
                for candidate in coverage_result["data"]:
                    skill_match_score = self._calculate_skill_match(
                        candidate.get("skills", "").split(","),
                        required_skills
                    )
                    workload_score = 100 - candidate.get("current_workload", 0)
                    total_score = (skill_match_score * 0.7) + (workload_score * 0.3)
                    
                    scored_candidates.append({
                        **candidate,
                        "skill_match_score": skill_match_score,
                        "workload_score": workload_score,
                        "total_score": total_score
                    })
                
                # Sort by total score
                scored_candidates.sort(key=lambda x: x["total_score"], reverse=True)
                
                return {
                    "success": True,
                    "coverage_found": True,
                    "primary_coverage": scored_candidates[0] if scored_candidates else None,
                    "backup_coverage": scored_candidates[1] if len(scored_candidates) > 1 else None,
                    "all_candidates": scored_candidates[:5]  # Top 5 candidates
                }
            else:
                return {
                    "success": True,
                    "coverage_found": False,
                    "message": "No suitable coverage candidates found"
                }
                
        except Exception as e:
            logger.error(f"Error finding optimal coverage: {str(e)}")
            return {"success": False, "error": str(e)}
    
    def _calculate_skill_match(self, candidate_skills: List[str], required_skills: List[str]) -> float:
        """Calculate skill match percentage between candidate and requirements"""
        if not required_skills:
            return 100.0
        
        candidate_skills = [skill.strip().lower() for skill in candidate_skills if skill.strip()]
        required_skills = [skill.strip().lower() for skill in required_skills]
        
        matches = sum(1 for skill in required_skills if skill in candidate_skills)
        return (matches / len(required_skills)) * 100

# Extended agent registry with all specialized agents
SPECIALIZED_AGENTS = {
    "it_support_agent": ITSupportAgent(),
    "compliance_agent": ComplianceAgent(),
    "training_agent": TrainingAgent(),
    "payroll_agent": PayrollAgent(),
    "leave_processing_agent": LeaveProcessingAgent(),
    "coverage_agent": CoverageAgent()
}