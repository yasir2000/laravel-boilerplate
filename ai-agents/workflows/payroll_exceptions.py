"""
Payroll Exception Handling System
Automated detection and resolution of payroll discrepancies with Frappe integration
"""

from datetime import datetime, timedelta
from typing import Dict, Any, List, Optional
import logging
import requests
import json

from agents.core_agents import AGENTS
from agents.specialized_agents import SPECIALIZED_AGENTS
from tools.agent_tools import AGENT_TOOLS

logger = logging.getLogger(__name__)

class PayrollExceptionHandler:
    """Comprehensive payroll exception handling with intelligent resolution"""
    
    def __init__(self):
        self.active_exception_workflows = {}
        self.frappe_config = self._load_frappe_config()
        self.exception_rules = self._load_exception_rules()
    
    async def process_payroll_exceptions(self, payroll_data: Dict[str, Any]) -> Dict[str, Any]:
        """Process comprehensive payroll exception handling workflow"""
        workflow_id = f"payroll_exceptions_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        
        try:
            logger.info(f"Starting payroll exception handling workflow: {workflow_id}")
            
            # Step 1: Detect payroll exceptions
            detection_result = await SPECIALIZED_AGENTS["payroll_agent"].detect_payroll_exceptions(payroll_data)
            
            if not detection_result.get("exceptions_found"):
                return {
                    "success": True,
                    "workflow_id": workflow_id,
                    "message": "No payroll exceptions detected",
                    "status": "clean_payroll",
                    "exceptions_count": 0
                }
            
            # Step 2: Categorize and prioritize exceptions
            categorization_result = await self._categorize_exceptions(detection_result.get("exceptions", []))
            
            # Step 3: Analyze exception patterns
            pattern_analysis = await self._analyze_exception_patterns(detection_result, payroll_data)
            
            # Step 4: Generate automatic resolutions
            auto_resolution_result = await self._generate_automatic_resolutions(categorization_result)
            
            # Step 5: Handle complex exceptions requiring escalation
            escalation_result = await self._handle_exception_escalations(categorization_result)
            
            # Step 6: Integrate with Frappe for payroll adjustments
            frappe_integration_result = await self._integrate_with_frappe(
                auto_resolution_result, payroll_data
            )
            
            # Step 7: Create approval workflows for significant adjustments
            approval_workflow_result = await self._create_approval_workflows(escalation_result)
            
            # Step 8: Generate exception reports
            reporting_result = await self._generate_exception_reports(
                detection_result, categorization_result, pattern_analysis
            )
            
            # Create comprehensive workflow state
            workflow_state = {
                "workflow_id": workflow_id,
                "payroll_period": payroll_data.get("payroll_period"),
                "status": "processing",
                "exceptions_detected": len(detection_result.get("exceptions", [])),
                "auto_resolved": len(auto_resolution_result.get("resolved", [])),
                "escalated": len(escalation_result.get("escalated", [])),
                "steps_completed": {
                    "exception_detection": detection_result,
                    "categorization": categorization_result,
                    "pattern_analysis": pattern_analysis,
                    "auto_resolution": auto_resolution_result,
                    "escalation_handling": escalation_result,
                    "frappe_integration": frappe_integration_result,
                    "approval_workflows": approval_workflow_result,
                    "reporting": reporting_result
                },
                "created_at": datetime.now().isoformat(),
                "estimated_resolution_time": self._estimate_resolution_time(categorization_result)
            }
            
            self.active_exception_workflows[workflow_id] = workflow_state
            
            # Store in persistent memory
            AGENT_TOOLS["memory_store"]._run("set", workflow_id, workflow_state, ttl=2592000)  # 30 days
            
            return {
                "success": True,
                "workflow_id": workflow_id,
                "status": "processing",
                "exceptions_detected": workflow_state["exceptions_detected"],
                "auto_resolved": workflow_state["auto_resolved"],
                "escalated": workflow_state["escalated"],
                "workflow_state": workflow_state,
                "next_actions": self._determine_next_actions(workflow_state)
            }
            
        except Exception as e:
            logger.error(f"Error in payroll exception handling workflow: {str(e)}")
            return {
                "success": False,
                "workflow_id": workflow_id,
                "error": str(e)
            }
    
    async def _categorize_exceptions(self, exceptions: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Categorize exceptions by type, severity, and resolution complexity"""
        try:
            categorized_exceptions = {
                "critical": [],      # Immediate attention required
                "high": [],          # Requires approval
                "medium": [],        # Can be auto-resolved with validation
                "low": []           # Simple auto-resolution
            }
            
            resolution_complexity = {
                "automatic": [],     # Can be auto-resolved
                "approval_required": [],  # Needs manager/HR approval
                "manual_review": []  # Complex cases requiring human review
            }
            
            for exception in exceptions:
                # Categorize by severity
                severity = self._determine_exception_severity(exception)
                categorized_exceptions[severity].append(exception)
                
                # Categorize by resolution complexity
                complexity = self._determine_resolution_complexity(exception)
                resolution_complexity[complexity].append(exception)
            
            # Generate category insights
            category_insights = {
                "total_exceptions": len(exceptions),
                "severity_distribution": {
                    severity: len(excs) for severity, excs in categorized_exceptions.items()
                },
                "complexity_distribution": {
                    complexity: len(excs) for complexity, excs in resolution_complexity.items()
                },
                "most_common_type": self._find_most_common_exception_type(exceptions),
                "affected_employees": list(set([exc.get("employee_id") for exc in exceptions])),
                "total_variance_amount": sum([
                    abs(exc.get("variance", 0)) for exc in exceptions 
                    if exc.get("exception_type") == "gross_pay_discrepancy"
                ])
            }
            
            return {
                "success": True,
                "categorized_exceptions": categorized_exceptions,
                "resolution_complexity": resolution_complexity,
                "category_insights": category_insights
            }
            
        except Exception as e:
            logger.error(f"Error categorizing exceptions: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _analyze_exception_patterns(self, detection_result: Dict[str, Any], payroll_data: Dict[str, Any]) -> Dict[str, Any]:
        """Analyze patterns in payroll exceptions for systemic issues"""
        try:
            exceptions = detection_result.get("exceptions", [])
            payroll_period = payroll_data.get("payroll_period")
            
            # Analyze temporal patterns
            temporal_patterns = await self._analyze_temporal_patterns(exceptions, payroll_period)
            
            # Analyze departmental patterns
            departmental_patterns = await self._analyze_departmental_patterns(exceptions)
            
            # Analyze employee patterns
            employee_patterns = await self._analyze_employee_patterns(exceptions)
            
            # Analyze system patterns
            system_patterns = await self._analyze_system_patterns(exceptions)
            
            # Identify root causes
            root_cause_analysis = await self._identify_root_causes(
                temporal_patterns, departmental_patterns, employee_patterns, system_patterns
            )
            
            # Generate recommendations
            pattern_recommendations = await self._generate_pattern_recommendations(root_cause_analysis)
            
            return {
                "success": True,
                "pattern_analysis": {
                    "temporal_patterns": temporal_patterns,
                    "departmental_patterns": departmental_patterns,
                    "employee_patterns": employee_patterns,
                    "system_patterns": system_patterns,
                    "root_cause_analysis": root_cause_analysis,
                    "recommendations": pattern_recommendations
                },
                "analysis_confidence": self._calculate_pattern_confidence(exceptions)
            }
            
        except Exception as e:
            logger.error(f"Error analyzing exception patterns: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _generate_automatic_resolutions(self, categorization_result: Dict[str, Any]) -> Dict[str, Any]:
        """Generate automatic resolutions for suitable exceptions"""
        try:
            automatic_exceptions = categorization_result.get("resolution_complexity", {}).get("automatic", [])
            resolved_exceptions = []
            failed_resolutions = []
            
            for exception in automatic_exceptions:
                resolution_result = await self._auto_resolve_exception(exception)
                
                if resolution_result.get("success"):
                    resolved_exceptions.append({
                        **exception,
                        "resolution": resolution_result.get("resolution"),
                        "resolved_at": datetime.now().isoformat(),
                        "resolution_method": "automatic"
                    })
                else:
                    failed_resolutions.append({
                        **exception,
                        "resolution_error": resolution_result.get("error"),
                        "escalated_to": "manual_review"
                    })
            
            # Apply automatic adjustments
            adjustment_results = []
            for resolved_exception in resolved_exceptions:
                adjustment_result = await self._apply_automatic_adjustment(resolved_exception)
                adjustment_results.append(adjustment_result)
            
            return {
                "success": True,
                "resolved": resolved_exceptions,
                "failed": failed_resolutions,
                "adjustment_results": adjustment_results,
                "total_auto_resolved": len(resolved_exceptions),
                "auto_resolution_rate": len(resolved_exceptions) / len(automatic_exceptions) if automatic_exceptions else 0
            }
            
        except Exception as e:
            logger.error(f"Error generating automatic resolutions: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _integrate_with_frappe(self, auto_resolution_result: Dict[str, Any], payroll_data: Dict[str, Any]) -> Dict[str, Any]:
        """Integrate with Frappe for payroll adjustments"""
        try:
            if not self.frappe_config.get("enabled"):
                return {
                    "success": True,
                    "message": "Frappe integration disabled",
                    "adjustments_applied": 0
                }
            
            resolved_exceptions = auto_resolution_result.get("resolved", [])
            frappe_adjustments = []
            
            for exception in resolved_exceptions:
                # Prepare Frappe adjustment data
                frappe_adjustment_data = self._prepare_frappe_adjustment(exception, payroll_data)
                
                # Apply adjustment in Frappe
                frappe_result = await self._apply_frappe_adjustment(frappe_adjustment_data)
                
                frappe_adjustments.append({
                    "employee_id": exception.get("employee_id"),
                    "exception_type": exception.get("exception_type"),
                    "frappe_result": frappe_result,
                    "adjustment_applied": frappe_result.get("success", False)
                })
            
            # Sync with Frappe payroll system
            sync_result = await self._sync_with_frappe_payroll(payroll_data.get("payroll_period"))
            
            return {
                "success": True,
                "frappe_adjustments": frappe_adjustments,
                "adjustments_applied": len([adj for adj in frappe_adjustments if adj["adjustment_applied"]]),
                "sync_result": sync_result,
                "frappe_integration_status": "completed"
            }
            
        except Exception as e:
            logger.error(f"Error integrating with Frappe: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _handle_exception_escalations(self, categorization_result: Dict[str, Any]) -> Dict[str, Any]:
        """Handle exceptions requiring escalation"""
        try:
            approval_required = categorization_result.get("resolution_complexity", {}).get("approval_required", [])
            manual_review = categorization_result.get("resolution_complexity", {}).get("manual_review", [])
            
            escalation_workflows = []
            
            # Handle approval-required exceptions
            for exception in approval_required:
                approval_workflow = await self._create_approval_workflow(exception)
                escalation_workflows.append({
                    "exception": exception,
                    "escalation_type": "approval_required",
                    "workflow": approval_workflow
                })
            
            # Handle manual review exceptions
            for exception in manual_review:
                manual_review_workflow = await self._create_manual_review_workflow(exception)
                escalation_workflows.append({
                    "exception": exception,
                    "escalation_type": "manual_review",
                    "workflow": manual_review_workflow
                })
            
            # Notify relevant stakeholders
            notification_results = await self._notify_escalation_stakeholders(escalation_workflows)
            
            return {
                "success": True,
                "escalated": escalation_workflows,
                "total_escalated": len(escalation_workflows),
                "notification_results": notification_results,
                "estimated_resolution_time": self._estimate_escalation_resolution_time(escalation_workflows)
            }
            
        except Exception as e:
            logger.error(f"Error handling escalations: {str(e)}")
            return {"success": False, "error": str(e)}
    
    def _load_frappe_config(self) -> Dict[str, Any]:
        """Load Frappe integration configuration"""
        return {
            "enabled": True,
            "base_url": "https://your-frappe-instance.com",
            "api_key": "your-frappe-api-key",
            "api_secret": "your-frappe-api-secret",
            "payroll_module": "HR",
            "salary_slip_doctype": "Salary Slip",
            "employee_doctype": "Employee"
        }
    
    def _load_exception_rules(self) -> Dict[str, Any]:
        """Load payroll exception rules and thresholds"""
        return {
            "gross_pay_discrepancy": {
                "auto_resolve_threshold": 10.00,  # $10
                "approval_threshold": 100.00,     # $100
                "critical_threshold": 500.00      # $500
            },
            "excessive_overtime": {
                "auto_approve_threshold": 5,      # 5 hours
                "manager_approval_threshold": 15, # 15 hours
                "hr_approval_threshold": 25       # 25 hours
            },
            "deduction_anomaly": {
                "percentage_threshold": 50,       # 50% of gross pay
                "auto_resolve_threshold": 25.00,  # $25
                "manual_review_threshold": 100.00 # $100
            }
        }
    
    def _determine_exception_severity(self, exception: Dict[str, Any]) -> str:
        """Determine exception severity level"""
        exception_type = exception.get("exception_type")
        
        if exception_type == "gross_pay_discrepancy":
            variance = abs(exception.get("variance", 0))
            if variance >= 500:
                return "critical"
            elif variance >= 100:
                return "high"
            elif variance >= 25:
                return "medium"
            else:
                return "low"
        
        elif exception_type == "excessive_overtime":
            overtime_hours = exception.get("overtime_hours", 0)
            if overtime_hours >= 25:
                return "critical"
            elif overtime_hours >= 15:
                return "high"
            elif overtime_hours >= 10:
                return "medium"
            else:
                return "low"
        
        elif exception_type == "excessive_deductions":
            deduction_amount = exception.get("deduction_amount", 0)
            gross_pay = exception.get("gross_pay", 1)
            percentage = (deduction_amount / gross_pay) * 100
            
            if percentage >= 75:
                return "critical"
            elif percentage >= 60:
                return "high"
            elif percentage >= 50:
                return "medium"
            else:
                return "low"
        
        return "medium"  # Default
    
    def _determine_resolution_complexity(self, exception: Dict[str, Any]) -> str:
        """Determine resolution complexity"""
        exception_type = exception.get("exception_type")
        severity = self._determine_exception_severity(exception)
        
        if severity == "critical":
            return "manual_review"
        elif severity == "high":
            return "approval_required"
        elif severity == "medium":
            if exception_type == "gross_pay_discrepancy":
                variance = abs(exception.get("variance", 0))
                return "automatic" if variance <= 10 else "approval_required"
            else:
                return "approval_required"
        else:  # low severity
            return "automatic"
    
    async def _auto_resolve_exception(self, exception: Dict[str, Any]) -> Dict[str, Any]:
        """Automatically resolve simple exceptions"""
        try:
            exception_type = exception.get("exception_type")
            
            if exception_type == "gross_pay_discrepancy":
                variance = exception.get("variance", 0)
                if abs(variance) <= 10:  # Auto-resolve small discrepancies
                    return {
                        "success": True,
                        "resolution": {
                            "action": "gross_pay_adjustment",
                            "adjustment_amount": variance,
                            "new_gross_pay": exception.get("expected"),
                            "reason": "Minor calculation error auto-corrected"
                        }
                    }
            
            elif exception_type == "deduction_anomaly":
                if exception.get("deduction_amount", 0) <= 25:  # Small deduction issues
                    return {
                        "success": True,
                        "resolution": {
                            "action": "deduction_correction",
                            "corrected_deduction": exception.get("expected_deduction", 0),
                            "reason": "Minor deduction calculation error corrected"
                        }
                    }
            
            return {"success": False, "error": "Exception cannot be auto-resolved"}
            
        except Exception as e:
            logger.error(f"Error auto-resolving exception: {str(e)}")
            return {"success": False, "error": str(e)}
    
    def _prepare_frappe_adjustment(self, exception: Dict[str, Any], payroll_data: Dict[str, Any]) -> Dict[str, Any]:
        """Prepare adjustment data for Frappe"""
        return {
            "doctype": "Salary Slip",
            "employee": exception.get("employee_id"),
            "payroll_period": payroll_data.get("payroll_period"),
            "adjustment_type": exception.get("exception_type"),
            "adjustment_amount": exception.get("resolution", {}).get("adjustment_amount", 0),
            "reason": exception.get("resolution", {}).get("reason", "Payroll exception correction")
        }
    
    async def _apply_frappe_adjustment(self, adjustment_data: Dict[str, Any]) -> Dict[str, Any]:
        """Apply adjustment in Frappe system"""
        try:
            if not self.frappe_config.get("enabled"):
                return {"success": False, "error": "Frappe integration disabled"}
            
            # This would make actual API calls to Frappe
            # For demonstration, we'll simulate the integration
            
            headers = {
                "Authorization": f"token {self.frappe_config['api_key']}:{self.frappe_config['api_secret']}",
                "Content-Type": "application/json"
            }
            
            url = f"{self.frappe_config['base_url']}/api/resource/Salary Slip"
            
            # Simulate Frappe API call
            # response = requests.post(url, headers=headers, json=adjustment_data)
            
            # For demonstration
            return {
                "success": True,
                "frappe_doc_id": f"SAL-SLIP-{datetime.now().strftime('%Y%m%d%H%M%S')}",
                "adjustment_applied": True,
                "message": "Adjustment applied successfully in Frappe"
            }
            
        except Exception as e:
            logger.error(f"Error applying Frappe adjustment: {str(e)}")
            return {"success": False, "error": str(e)}
    
    # Additional helper methods would be implemented here...
    
    def _estimate_resolution_time(self, categorization_result: Dict[str, Any]) -> str:
        """Estimate total resolution time for exceptions"""
        complexity_dist = categorization_result.get("complexity_distribution", {})
        
        auto_count = complexity_dist.get("automatic", 0)
        approval_count = complexity_dist.get("approval_required", 0)
        manual_count = complexity_dist.get("manual_review", 0)
        
        # Calculate estimated time
        total_hours = (auto_count * 0.5) + (approval_count * 24) + (manual_count * 72)
        
        if total_hours <= 1:
            return "Within 1 hour"
        elif total_hours <= 24:
            return "Within 1 business day"
        elif total_hours <= 72:
            return "1-3 business days"
        else:
            return "3+ business days"
    
    def _determine_next_actions(self, workflow_state: Dict[str, Any]) -> List[str]:
        """Determine next actions based on workflow state"""
        actions = []
        
        if workflow_state["auto_resolved"] > 0:
            actions.append("Review automatic adjustments")
        
        if workflow_state["escalated"] > 0:
            actions.append("Process escalated exceptions")
        
        actions.append("Monitor payroll processing")
        actions.append("Generate exception reports")
        
        return actions

# Payroll Exception Handler Instance
payroll_exception_handler = PayrollExceptionHandler()

# Export for external use
__all__ = ["payroll_exception_handler", "PayrollExceptionHandler"]