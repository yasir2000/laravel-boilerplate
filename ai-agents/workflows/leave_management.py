"""
Comprehensive Leave Management System
Automated leave processing with intelligent coverage and approval routing
"""

from datetime import datetime, timedelta
from typing import Dict, Any, List, Optional
import logging

from agents.core_agents import AGENTS
from agents.specialized_agents import SPECIALIZED_AGENTS
from tools.agent_tools import AGENT_TOOLS

logger = logging.getLogger(__name__)

class LeaveManagementSystem:
    """Complete leave management system with multi-agent collaboration"""
    
    def __init__(self):
        self.active_leave_workflows = {}
        self.coverage_assignments = {}
    
    async def process_leave_request(self, leave_request: Dict[str, Any]) -> Dict[str, Any]:
        """Process complete leave request workflow"""
        workflow_id = f"leave_{leave_request.get('leave_request_id')}_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        
        try:
            logger.info(f"Starting leave management workflow: {workflow_id}")
            
            # Step 1: Validate leave request
            validation_result = await self._validate_leave_request(leave_request)
            if not validation_result.get("valid"):
                return {
                    "success": False,
                    "workflow_id": workflow_id,
                    "error": "Leave request validation failed",
                    "validation_details": validation_result
                }
            
            # Step 2: Check leave balance and eligibility
            eligibility_result = await SPECIALIZED_AGENTS["leave_processing_agent"].process_leave_application(leave_request)
            if not eligibility_result.get("success"):
                return {
                    "success": False,
                    "workflow_id": workflow_id,
                    "error": "Employee not eligible for leave",
                    "eligibility_details": eligibility_result
                }
            
            # Step 3: Find coverage for the leave period
            coverage_result = await SPECIALIZED_AGENTS["coverage_agent"].find_optimal_coverage({
                "employee_id": leave_request.get("employee_id"),
                "start_date": leave_request.get("start_date"),
                "end_date": leave_request.get("end_date"),
                "required_skills": leave_request.get("required_skills", []),
                "department": leave_request.get("department")
            })
            
            # Step 4: Create approval workflow
            approval_result = await self._create_approval_workflow({
                **leave_request,
                "validation": validation_result,
                "eligibility": eligibility_result,
                "coverage": coverage_result
            })
            
            # Step 5: Notify stakeholders
            notification_result = await self._notify_leave_stakeholders({
                **leave_request,
                "workflow_id": workflow_id,
                "approval_workflow": approval_result,
                "coverage_assignments": coverage_result.get("coverage_assignments", [])
            })
            
            # Step 6: Schedule calendar updates (pending approval)
            calendar_result = await self._schedule_calendar_updates({
                **leave_request,
                "workflow_id": workflow_id,
                "approval_pending": True
            })
            
            # Store workflow state
            workflow_state = {
                "workflow_id": workflow_id,
                "leave_request_id": leave_request.get("leave_request_id"),
                "employee_id": leave_request.get("employee_id"),
                "status": "pending_approval",
                "steps_completed": {
                    "validation": validation_result,
                    "eligibility_check": eligibility_result,
                    "coverage_planning": coverage_result,
                    "approval_workflow": approval_result,
                    "stakeholder_notification": notification_result,
                    "calendar_scheduling": calendar_result
                },
                "created_at": datetime.now().isoformat(),
                "next_action": "awaiting_manager_approval"
            }
            
            self.active_leave_workflows[workflow_id] = workflow_state
            
            # Store in persistent memory
            AGENT_TOOLS["memory_store"]._run("set", workflow_id, workflow_state, ttl=2592000)  # 30 days
            
            return {
                "success": True,
                "workflow_id": workflow_id,
                "status": "pending_approval",
                "message": "Leave request submitted for approval",
                "workflow_state": workflow_state,
                "estimated_approval_time": "1-3 business days"
            }
            
        except Exception as e:
            logger.error(f"Error in leave management workflow: {str(e)}")
            return {
                "success": False,
                "workflow_id": workflow_id,
                "error": str(e)
            }
    
    async def process_leave_approval(self, approval_data: Dict[str, Any]) -> Dict[str, Any]:
        """Process leave approval decision"""
        try:
            workflow_id = approval_data.get("workflow_id")
            approver_id = approval_data.get("approver_id")
            decision = approval_data.get("decision")  # "approved" or "rejected"
            comments = approval_data.get("comments", "")
            
            # Get workflow state
            workflow_state = self.active_leave_workflows.get(workflow_id)
            if not workflow_state:
                # Try to retrieve from memory
                memory_result = AGENT_TOOLS["memory_store"]._run("get", workflow_id)
                if memory_result.get("success"):
                    workflow_state = memory_result.get("data")
                else:
                    return {"success": False, "error": "Workflow not found"}
            
            if decision == "approved":
                return await self._process_approval(workflow_state, approver_id, comments)
            else:
                return await self._process_rejection(workflow_state, approver_id, comments)
                
        except Exception as e:
            logger.error(f"Error processing leave approval: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _validate_leave_request(self, leave_request: Dict[str, Any]) -> Dict[str, Any]:
        """Validate leave request data and business rules"""
        try:
            validation_errors = []
            
            # Check required fields
            required_fields = ["employee_id", "leave_type", "start_date", "end_date", "reason"]
            for field in required_fields:
                if not leave_request.get(field):
                    validation_errors.append(f"Missing required field: {field}")
            
            # Validate dates
            try:
                start_date = datetime.strptime(leave_request.get("start_date", ""), "%Y-%m-%d")
                end_date = datetime.strptime(leave_request.get("end_date", ""), "%Y-%m-%d")
                
                if start_date < datetime.now().date():
                    validation_errors.append("Start date cannot be in the past")
                
                if end_date < start_date:
                    validation_errors.append("End date cannot be before start date")
                
                # Check advance notice requirements
                days_notice = (start_date.date() - datetime.now().date()).days
                leave_type = leave_request.get("leave_type")
                
                if leave_type == "vacation" and days_notice < 7:
                    validation_errors.append("Vacation leave requires at least 7 days advance notice")
                elif leave_type == "personal" and days_notice < 3:
                    validation_errors.append("Personal leave requires at least 3 days advance notice")
                
            except ValueError:
                validation_errors.append("Invalid date format. Use YYYY-MM-DD")
            
            # Check for overlapping leave requests
            overlap_check = await self._check_overlapping_requests(leave_request)
            if not overlap_check.get("success"):
                validation_errors.extend(overlap_check.get("errors", []))
            
            return {
                "valid": len(validation_errors) == 0,
                "errors": validation_errors,
                "validation_passed": len(validation_errors) == 0
            }
            
        except Exception as e:
            logger.error(f"Error validating leave request: {str(e)}")
            return {"valid": False, "errors": [str(e)]}
    
    async def _check_overlapping_requests(self, leave_request: Dict[str, Any]) -> Dict[str, Any]:
        """Check for overlapping leave requests"""
        try:
            employee_id = leave_request.get("employee_id")
            start_date = leave_request.get("start_date")
            end_date = leave_request.get("end_date")
            
            overlap_query = """
            SELECT id, start_date, end_date, leave_type, status
            FROM leave_requests
            WHERE employee_id = :employee_id
            AND status IN ('pending', 'approved')
            AND (
                (start_date <= :end_date AND end_date >= :start_date)
            )
            """
            
            overlap_result = AGENT_TOOLS["database_query"]._run(
                overlap_query,
                {
                    "employee_id": employee_id,
                    "start_date": start_date,
                    "end_date": end_date
                }
            )
            
            if overlap_result.get("success") and overlap_result.get("data"):
                overlapping_requests = overlap_result["data"]
                return {
                    "success": False,
                    "errors": [f"Overlapping leave request found: {req['id']}" for req in overlapping_requests]
                }
            
            return {"success": True}
            
        except Exception as e:
            logger.error(f"Error checking overlapping requests: {str(e)}")
            return {"success": False, "errors": [str(e)]}
    
    async def _create_approval_workflow(self, workflow_data: Dict[str, Any]) -> Dict[str, Any]:
        """Create multi-step approval workflow"""
        try:
            employee_id = workflow_data.get("employee_id")
            leave_type = workflow_data.get("leave_type")
            days_requested = workflow_data.get("days_requested", 0)
            
            # Get employee information for approval chain
            employee_query = """
            SELECT e.*, d.name as department_name, m.id as manager_id, m.name as manager_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN employees m ON e.manager_id = m.id
            WHERE e.id = :employee_id
            """
            
            employee_result = AGENT_TOOLS["database_query"]._run(
                employee_query, {"employee_id": employee_id}
            )
            
            if not employee_result.get("success") or not employee_result.get("data"):
                return {"success": False, "error": "Employee not found"}
            
            employee = employee_result["data"][0]
            
            # Determine approval chain based on business rules
            approval_chain = []
            
            # Manager approval (always required)
            if employee.get("manager_id"):
                approval_chain.append({
                    "approver_id": employee["manager_id"],
                    "approver_name": employee.get("manager_name"),
                    "approver_type": "manager",
                    "step_order": 1,
                    "required": True
                })
            
            # HR approval for extended leave
            if days_requested > 5 or leave_type in ["medical", "maternity", "paternity"]:
                approval_chain.append({
                    "approver_id": "hr_director",
                    "approver_name": "HR Director",
                    "approver_type": "hr_director",
                    "step_order": 2,
                    "required": True
                })
            
            # Department head approval for management positions
            if employee.get("position") in ["Manager", "Director", "VP"]:
                approval_chain.append({
                    "approver_id": "department_head",
                    "approver_name": "Department Head",
                    "approver_type": "department_head",
                    "step_order": 3,
                    "required": True
                })
            
            # Create workflow in system
            workflow_create_data = {
                "name": f"Leave Approval - {employee.get('name')} ({leave_type})",
                "model_type": "LeaveRequest",
                "model_id": workflow_data.get("leave_request_id"),
                "steps": [
                    {
                        "name": f"Approval by {step['approver_name']}",
                        "assignee_id": step["approver_id"],
                        "assignee_type": step["approver_type"],
                        "order": step["step_order"],
                        "status": "pending",
                        "required": step["required"]
                    }
                    for step in approval_chain
                ]
            }
            
            workflow_result = AGENT_TOOLS["workflow_engine"]._run("create", workflow_create_data)
            
            return {
                "success": True,
                "approval_chain": approval_chain,
                "workflow_id": workflow_result.get("data", {}).get("id"),
                "total_approvers": len(approval_chain),
                "estimated_approval_time": f"{len(approval_chain)} business days"
            }
            
        except Exception as e:
            logger.error(f"Error creating approval workflow: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _notify_leave_stakeholders(self, notification_data: Dict[str, Any]) -> Dict[str, Any]:
        """Notify all stakeholders about leave request"""
        try:
            notifications_sent = []
            
            # Notify employee
            employee_notification = AGENT_TOOLS["email_sender"]._run(
                notification_data.get("employee_email"),
                "Leave Request Submitted",
                f"""
                Your leave request has been submitted for approval.
                
                Leave Type: {notification_data.get('leave_type')}
                Dates: {notification_data.get('start_date')} to {notification_data.get('end_date')}
                Status: Pending Approval
                
                You will be notified once the approval process is complete.
                """
            )
            notifications_sent.append({
                "recipient": "employee",
                "success": employee_notification.get("success")
            })
            
            # Notify manager(s) in approval chain
            approval_workflow = notification_data.get("approval_workflow", {})
            for approver in approval_workflow.get("approval_chain", []):
                manager_notification = AGENT_TOOLS["email_sender"]._run(
                    f"{approver['approver_id']}@company.com",  # Simplified email
                    "Leave Request Approval Required",
                    f"""
                    A leave request requires your approval:
                    
                    Employee: {notification_data.get('employee_name')}
                    Leave Type: {notification_data.get('leave_type')}
                    Dates: {notification_data.get('start_date')} to {notification_data.get('end_date')}
                    Reason: {notification_data.get('reason')}
                    
                    Please review and approve/reject this request in the HR system.
                    """
                )
                notifications_sent.append({
                    "recipient": approver["approver_name"],
                    "success": manager_notification.get("success")
                })
            
            # Notify coverage team
            for coverage in notification_data.get("coverage_assignments", []):
                coverage_notification = AGENT_TOOLS["email_sender"]._run(
                    coverage.get("email"),
                    "Coverage Assignment Notification",
                    f"""
                    You have been assigned coverage duties:
                    
                    Covering for: {notification_data.get('employee_name')}
                    Coverage Period: {notification_data.get('start_date')} to {notification_data.get('end_date')}
                    Coverage Type: {coverage.get('coverage_type')}
                    
                    This is pending approval. You will be notified if the leave is approved.
                    """
                )
                notifications_sent.append({
                    "recipient": coverage.get("name"),
                    "success": coverage_notification.get("success")
                })
            
            return {
                "success": True,
                "notifications_sent": notifications_sent,
                "total_notifications": len(notifications_sent)
            }
            
        except Exception as e:
            logger.error(f"Error notifying stakeholders: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _schedule_calendar_updates(self, calendar_data: Dict[str, Any]) -> Dict[str, Any]:
        """Schedule calendar updates for when leave is approved"""
        try:
            # This creates a pending calendar update that will be executed upon approval
            calendar_update_plan = {
                "employee_calendar": {
                    "action": "block_time",
                    "start_date": calendar_data.get("start_date"),
                    "end_date": calendar_data.get("end_date"),
                    "title": f"{calendar_data.get('leave_type', 'Leave').title()} - Out of Office",
                    "description": calendar_data.get("reason", "")
                },
                "team_calendar": {
                    "action": "add_coverage_events",
                    "coverage_assignments": calendar_data.get("coverage_assignments", [])
                },
                "meeting_conflicts": {
                    "action": "check_and_reschedule",
                    "date_range": {
                        "start": calendar_data.get("start_date"),
                        "end": calendar_data.get("end_date")
                    }
                }
            }
            
            return {
                "success": True,
                "calendar_update_plan": calendar_update_plan,
                "pending_approval": calendar_data.get("approval_pending", True),
                "message": "Calendar updates scheduled for approval confirmation"
            }
            
        except Exception as e:
            logger.error(f"Error scheduling calendar updates: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _process_approval(self, workflow_state: Dict[str, Any], approver_id: str, comments: str) -> Dict[str, Any]:
        """Process leave approval"""
        try:
            workflow_id = workflow_state.get("workflow_id")
            leave_request_id = workflow_state.get("leave_request_id")
            
            # Update leave request status
            update_query = """
            UPDATE leave_requests 
            SET status = 'approved', 
                approved_by = :approver_id,
                approved_at = NOW(),
                approval_comments = :comments
            WHERE id = :leave_request_id
            """
            
            update_result = AGENT_TOOLS["database_query"]._run(
                update_query,
                {
                    "approver_id": approver_id,
                    "comments": comments,
                    "leave_request_id": leave_request_id
                }
            )
            
            if not update_result.get("success"):
                return {"success": False, "error": "Failed to update leave request"}
            
            # Execute calendar updates
            calendar_result = await self._execute_calendar_updates(workflow_state)
            
            # Finalize coverage assignments
            coverage_result = await self._finalize_coverage_assignments(workflow_state)
            
            # Update leave balance
            balance_result = await self._update_leave_balance(workflow_state)
            
            # Send approval notifications
            notification_result = await self._send_approval_notifications(workflow_state, approver_id, comments)
            
            # Update workflow state
            workflow_state["status"] = "approved"
            workflow_state["approved_by"] = approver_id
            workflow_state["approved_at"] = datetime.now().isoformat()
            workflow_state["approval_comments"] = comments
            
            return {
                "success": True,
                "workflow_id": workflow_id,
                "status": "approved",
                "message": "Leave request approved successfully",
                "execution_results": {
                    "calendar_updates": calendar_result,
                    "coverage_finalization": coverage_result,
                    "balance_update": balance_result,
                    "notifications": notification_result
                }
            }
            
        except Exception as e:
            logger.error(f"Error processing approval: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _process_rejection(self, workflow_state: Dict[str, Any], approver_id: str, comments: str) -> Dict[str, Any]:
        """Process leave rejection"""
        try:
            workflow_id = workflow_state.get("workflow_id")
            leave_request_id = workflow_state.get("leave_request_id")
            
            # Update leave request status
            update_query = """
            UPDATE leave_requests 
            SET status = 'rejected', 
                rejected_by = :approver_id,
                rejected_at = NOW(),
                rejection_comments = :comments
            WHERE id = :leave_request_id
            """
            
            update_result = AGENT_TOOLS["database_query"]._run(
                update_query,
                {
                    "approver_id": approver_id,
                    "comments": comments,
                    "leave_request_id": leave_request_id
                }
            )
            
            if not update_result.get("success"):
                return {"success": False, "error": "Failed to update leave request"}
            
            # Cancel pending coverage assignments
            coverage_cancellation = await self._cancel_coverage_assignments(workflow_state)
            
            # Send rejection notifications
            notification_result = await self._send_rejection_notifications(workflow_state, approver_id, comments)
            
            # Update workflow state
            workflow_state["status"] = "rejected"
            workflow_state["rejected_by"] = approver_id
            workflow_state["rejected_at"] = datetime.now().isoformat()
            workflow_state["rejection_comments"] = comments
            
            return {
                "success": True,
                "workflow_id": workflow_id,
                "status": "rejected",
                "message": "Leave request rejected",
                "execution_results": {
                    "coverage_cancellation": coverage_cancellation,
                    "notifications": notification_result
                }
            }
            
        except Exception as e:
            logger.error(f"Error processing rejection: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _execute_calendar_updates(self, workflow_state: Dict[str, Any]) -> Dict[str, Any]:
        """Execute planned calendar updates"""
        # Implementation would integrate with actual calendar systems
        return {"success": True, "message": "Calendar updates executed"}
    
    async def _finalize_coverage_assignments(self, workflow_state: Dict[str, Any]) -> Dict[str, Any]:
        """Finalize coverage assignments"""
        # Implementation would update coverage assignments in system
        return {"success": True, "message": "Coverage assignments finalized"}
    
    async def _update_leave_balance(self, workflow_state: Dict[str, Any]) -> Dict[str, Any]:
        """Update employee leave balance"""
        # Implementation would deduct leave days from balance
        return {"success": True, "message": "Leave balance updated"}
    
    async def _send_approval_notifications(self, workflow_state: Dict[str, Any], approver_id: str, comments: str) -> Dict[str, Any]:
        """Send approval notifications to all stakeholders"""
        # Implementation would send approval emails
        return {"success": True, "message": "Approval notifications sent"}
    
    async def _send_rejection_notifications(self, workflow_state: Dict[str, Any], approver_id: str, comments: str) -> Dict[str, Any]:
        """Send rejection notifications"""
        # Implementation would send rejection emails
        return {"success": True, "message": "Rejection notifications sent"}
    
    async def _cancel_coverage_assignments(self, workflow_state: Dict[str, Any]) -> Dict[str, Any]:
        """Cancel pending coverage assignments"""
        # Implementation would cancel coverage assignments
        return {"success": True, "message": "Coverage assignments cancelled"}

# Leave Management System Instance
leave_management_system = LeaveManagementSystem()

# Export for external use
__all__ = ["leave_management_system", "LeaveManagementSystem"]