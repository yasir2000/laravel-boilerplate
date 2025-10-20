"""
Performance Review Coordination System
360-degree performance review automation with multi-agent collaboration
"""

from datetime import datetime, timedelta
from typing import Dict, Any, List, Optional
import logging

from agents.core_agents import AGENTS
from agents.specialized_agents import SPECIALIZED_AGENTS
from tools.agent_tools import AGENT_TOOLS

logger = logging.getLogger(__name__)

class PerformanceReviewSystem:
    """Complete performance review coordination system"""
    
    def __init__(self):
        self.active_reviews = {}
        self.review_templates = self._load_review_templates()
    
    async def initiate_performance_review(self, review_data: Dict[str, Any]) -> Dict[str, Any]:
        """Initiate comprehensive performance review process"""
        review_id = f"review_{review_data.get('employee_id')}_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        
        try:
            logger.info(f"Initiating performance review: {review_id}")
            
            # Step 1: Validate review parameters
            validation_result = await self._validate_review_parameters(review_data)
            if not validation_result.get("valid"):
                return {
                    "success": False,
                    "review_id": review_id,
                    "error": "Review validation failed",
                    "validation_details": validation_result
                }
            
            # Step 2: Set up review framework
            framework_result = await self._setup_review_framework(review_data, review_id)
            
            # Step 3: Collect baseline performance data
            data_collection_result = await self._initiate_data_collection(review_data, review_id)
            
            # Step 4: Identify and notify reviewers
            reviewer_setup_result = await self._setup_reviewers(review_data, review_id)
            
            # Step 5: Schedule review timeline
            timeline_result = await self._create_review_timeline(review_data, review_id)
            
            # Step 6: Initialize feedback collection system
            feedback_system_result = await self._initialize_feedback_system(review_data, review_id)
            
            # Create review workflow
            workflow_data = {
                "name": f"Performance Review - {review_data.get('employee_name')}",
                "model_type": "PerformanceReview",
                "model_id": review_id,
                "steps": [
                    {"name": "Data Collection", "assignee_type": "system", "order": 1, "status": "in_progress"},
                    {"name": "Self Assessment", "assignee_type": "employee", "order": 2, "status": "pending"},
                    {"name": "Peer Feedback", "assignee_type": "peers", "order": 3, "status": "pending"},
                    {"name": "Manager Review", "assignee_type": "manager", "order": 4, "status": "pending"},
                    {"name": "HR Review", "assignee_type": "hr", "order": 5, "status": "pending"},
                    {"name": "Review Meeting", "assignee_type": "manager", "order": 6, "status": "pending"}
                ]
            }
            
            workflow_result = AGENT_TOOLS["workflow_engine"]._run("create", workflow_data)
            
            # Store review state
            review_state = {
                "review_id": review_id,
                "employee_id": review_data.get("employee_id"),
                "review_type": review_data.get("review_type", "annual"),
                "review_period": review_data.get("review_period"),
                "status": "data_collection",
                "workflow_id": workflow_result.get("data", {}).get("id"),
                "steps_completed": {
                    "validation": validation_result,
                    "framework_setup": framework_result,
                    "data_collection_initiation": data_collection_result,
                    "reviewer_setup": reviewer_setup_result,
                    "timeline_creation": timeline_result,
                    "feedback_system_init": feedback_system_result
                },
                "created_at": datetime.now().isoformat(),
                "timeline": timeline_result.get("timeline", {}),
                "reviewers": reviewer_setup_result.get("reviewers", [])
            }
            
            self.active_reviews[review_id] = review_state
            
            # Store in persistent memory
            AGENT_TOOLS["memory_store"]._run("set", review_id, review_state, ttl=7776000)  # 90 days
            
            return {
                "success": True,
                "review_id": review_id,
                "status": "initiated",
                "message": "Performance review process initiated successfully",
                "review_state": review_state,
                "next_actions": [
                    "Employee self-assessment will be requested",
                    "Peer feedback collection will begin",
                    "Performance data collection is in progress"
                ],
                "estimated_completion": timeline_result.get("estimated_completion")
            }
            
        except Exception as e:
            logger.error(f"Error initiating performance review: {str(e)}")
            return {
                "success": False,
                "review_id": review_id,
                "error": str(e)
            }
    
    async def collect_performance_data(self, review_id: str) -> Dict[str, Any]:
        """Collect comprehensive performance data from various sources"""
        try:
            # Get review state
            review_state = await self._get_review_state(review_id)
            if not review_state:
                return {"success": False, "error": "Review not found"}
            
            employee_id = review_state.get("employee_id")
            review_period = review_state.get("review_period")
            
            # Collect project performance data
            project_data = await self._collect_project_performance(employee_id, review_period)
            
            # Collect goal achievement data
            goal_data = await self._collect_goal_achievement(employee_id, review_period)
            
            # Collect attendance and punctuality data
            attendance_data = await self._collect_attendance_data(employee_id, review_period)
            
            # Collect learning and development data
            learning_data = await self._collect_learning_data(employee_id, review_period)
            
            # Collect collaboration metrics
            collaboration_data = await self._collect_collaboration_metrics(employee_id, review_period)
            
            # Use Analytics Agent for advanced insights
            analytics_result = AGENTS["analytics_agent"].generate_employee_analytics("last_year")
            
            # Compile comprehensive performance data
            performance_data = {
                "employee_id": employee_id,
                "review_period": review_period,
                "data_collection_date": datetime.now().isoformat(),
                "project_performance": project_data,
                "goal_achievement": goal_data,
                "attendance_metrics": attendance_data,
                "learning_development": learning_data,
                "collaboration_metrics": collaboration_data,
                "analytics_insights": analytics_result.get("analytics", {}),
                "data_completeness_score": self._calculate_data_completeness([
                    project_data, goal_data, attendance_data, learning_data, collaboration_data
                ])
            }
            
            # Update review state
            review_state["performance_data"] = performance_data
            review_state["data_collection_completed"] = True
            review_state["status"] = "self_assessment"
            
            # Store updated state
            AGENT_TOOLS["memory_store"]._run("set", review_id, review_state, ttl=7776000)
            
            return {
                "success": True,
                "review_id": review_id,
                "performance_data": performance_data,
                "message": "Performance data collection completed",
                "next_step": "employee_self_assessment"
            }
            
        except Exception as e:
            logger.error(f"Error collecting performance data: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def coordinate_feedback_collection(self, review_id: str) -> Dict[str, Any]:
        """Coordinate feedback collection from multiple reviewers"""
        try:
            review_state = await self._get_review_state(review_id)
            if not review_state:
                return {"success": False, "error": "Review not found"}
            
            reviewers = review_state.get("reviewers", [])
            employee_id = review_state.get("employee_id")
            
            feedback_coordination_results = []
            
            # Request self-assessment from employee
            self_assessment_result = await self._request_self_assessment(employee_id, review_id)
            feedback_coordination_results.append({
                "type": "self_assessment",
                "result": self_assessment_result
            })
            
            # Request peer feedback
            for peer_reviewer in [r for r in reviewers if r.get("type") == "peer"]:
                peer_feedback_result = await self._request_peer_feedback(peer_reviewer, employee_id, review_id)
                feedback_coordination_results.append({
                    "type": "peer_feedback",
                    "reviewer": peer_reviewer.get("name"),
                    "result": peer_feedback_result
                })
            
            # Request manager feedback
            manager_reviewers = [r for r in reviewers if r.get("type") == "manager"]
            if manager_reviewers:
                manager_feedback_result = await self._request_manager_feedback(
                    manager_reviewers[0], employee_id, review_id
                )
                feedback_coordination_results.append({
                    "type": "manager_feedback",
                    "result": manager_feedback_result
                })
            
            # Request direct report feedback (if applicable)
            direct_report_reviewers = [r for r in reviewers if r.get("type") == "direct_report"]
            for direct_report in direct_report_reviewers:
                direct_report_result = await self._request_direct_report_feedback(
                    direct_report, employee_id, review_id
                )
                feedback_coordination_results.append({
                    "type": "direct_report_feedback",
                    "reviewer": direct_report.get("name"),
                    "result": direct_report_result
                })
            
            # Set up feedback tracking
            feedback_tracking = {
                "total_requested": len(feedback_coordination_results),
                "requests_sent": len([r for r in feedback_coordination_results if r["result"].get("success")]),
                "feedback_deadline": (datetime.now() + timedelta(days=7)).isoformat(),
                "reminder_schedule": [
                    (datetime.now() + timedelta(days=3)).isoformat(),  # 3-day reminder
                    (datetime.now() + timedelta(days=6)).isoformat()   # 1-day reminder
                ]
            }
            
            # Update review state
            review_state["feedback_coordination"] = {
                "coordination_results": feedback_coordination_results,
                "tracking": feedback_tracking,
                "status": "feedback_collection_active"
            }
            review_state["status"] = "feedback_collection"
            
            AGENT_TOOLS["memory_store"]._run("set", review_id, review_state, ttl=7776000)
            
            return {
                "success": True,
                "review_id": review_id,
                "coordination_results": feedback_coordination_results,
                "tracking": feedback_tracking,
                "message": f"Feedback collection coordinated for {len(reviewers)} reviewers",
                "next_step": "monitor_feedback_collection"
            }
            
        except Exception as e:
            logger.error(f"Error coordinating feedback collection: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def compile_review_analysis(self, review_id: str) -> Dict[str, Any]:
        """Compile comprehensive review analysis from all data sources"""
        try:
            review_state = await self._get_review_state(review_id)
            if not review_state:
                return {"success": False, "error": "Review not found"}
            
            # Get collected data
            performance_data = review_state.get("performance_data", {})
            feedback_data = await self._collect_all_feedback(review_id)
            
            # Analyze performance metrics
            performance_analysis = await self._analyze_performance_metrics(performance_data)
            
            # Analyze feedback themes
            feedback_analysis = await self._analyze_feedback_themes(feedback_data)
            
            # Calculate overall ratings
            overall_ratings = await self._calculate_overall_ratings(performance_analysis, feedback_analysis)
            
            # Generate development recommendations
            development_recommendations = await self._generate_development_recommendations(
                performance_analysis, feedback_analysis, overall_ratings
            )
            
            # Identify strengths and improvement areas
            strengths_and_improvements = await self._identify_strengths_and_improvements(
                performance_analysis, feedback_analysis
            )
            
            # Create comprehensive analysis
            comprehensive_analysis = {
                "review_id": review_id,
                "employee_id": review_state.get("employee_id"),
                "analysis_date": datetime.now().isoformat(),
                "performance_analysis": performance_analysis,
                "feedback_analysis": feedback_analysis,
                "overall_ratings": overall_ratings,
                "strengths": strengths_and_improvements.get("strengths", []),
                "improvement_areas": strengths_and_improvements.get("improvement_areas", []),
                "development_recommendations": development_recommendations,
                "career_progression_readiness": self._assess_career_readiness(overall_ratings),
                "compensation_recommendations": self._generate_compensation_recommendations(overall_ratings),
                "analysis_confidence_score": self._calculate_analysis_confidence(
                    performance_data, feedback_data
                )
            }
            
            # Update review state
            review_state["comprehensive_analysis"] = comprehensive_analysis
            review_state["status"] = "analysis_complete"
            
            AGENT_TOOLS["memory_store"]._run("set", review_id, review_state, ttl=7776000)
            
            return {
                "success": True,
                "review_id": review_id,
                "comprehensive_analysis": comprehensive_analysis,
                "message": "Performance review analysis completed",
                "next_step": "schedule_review_meeting"
            }
            
        except Exception as e:
            logger.error(f"Error compiling review analysis: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def schedule_review_meeting(self, review_id: str, meeting_preferences: Dict[str, Any] = None) -> Dict[str, Any]:
        """Schedule performance review meeting"""
        try:
            review_state = await self._get_review_state(review_id)
            if not review_state:
                return {"success": False, "error": "Review not found"}
            
            employee_id = review_state.get("employee_id")
            
            # Get employee and manager information
            employee_info = await self._get_employee_info(employee_id)
            manager_info = await self._get_manager_info(employee_id)
            
            # Find optimal meeting time
            optimal_time = await self._find_optimal_meeting_time(
                [employee_info, manager_info], 
                meeting_preferences or {}
            )
            
            # Create meeting
            meeting_data = {
                "title": f"Performance Review - {employee_info.get('name')}",
                "description": "Annual performance review discussion",
                "start_time": optimal_time.get("start_time"),
                "end_time": optimal_time.get("end_time"),
                "attendees": [
                    {"id": employee_id, "name": employee_info.get("name"), "required": True},
                    {"id": manager_info.get("id"), "name": manager_info.get("name"), "required": True}
                ],
                "location": meeting_preferences.get("location", "Conference Room A"),
                "meeting_type": "performance_review",
                "review_id": review_id
            }
            
            # Send meeting invitations
            invitation_result = await self._send_meeting_invitations(meeting_data)
            
            # Prepare meeting materials
            materials_result = await self._prepare_meeting_materials(review_id, meeting_data)
            
            # Update review state
            review_state["meeting_scheduled"] = {
                "meeting_data": meeting_data,
                "invitations_sent": invitation_result,
                "materials_prepared": materials_result,
                "scheduled_at": datetime.now().isoformat()
            }
            review_state["status"] = "meeting_scheduled"
            
            AGENT_TOOLS["memory_store"]._run("set", review_id, review_state, ttl=7776000)
            
            return {
                "success": True,
                "review_id": review_id,
                "meeting_data": meeting_data,
                "invitations_sent": invitation_result.get("success", False),
                "materials_prepared": materials_result.get("success", False),
                "message": "Performance review meeting scheduled successfully",
                "next_step": "conduct_review_meeting"
            }
            
        except Exception as e:
            logger.error(f"Error scheduling review meeting: {str(e)}")
            return {"success": False, "error": str(e)}
    
    # Helper methods implementation
    
    def _load_review_templates(self) -> Dict[str, Any]:
        """Load performance review templates"""
        return {
            "annual": {
                "sections": ["goals", "performance", "competencies", "development"],
                "rating_scale": "1-5",
                "required_reviewers": ["manager", "self"]
            },
            "quarterly": {
                "sections": ["goals", "performance", "recent_projects"],
                "rating_scale": "1-5",
                "required_reviewers": ["manager", "self"]
            },
            "360": {
                "sections": ["leadership", "collaboration", "performance", "competencies"],
                "rating_scale": "1-5",
                "required_reviewers": ["manager", "peers", "direct_reports", "self"]
            }
        }
    
    async def _validate_review_parameters(self, review_data: Dict[str, Any]) -> Dict[str, Any]:
        """Validate review parameters"""
        errors = []
        
        if not review_data.get("employee_id"):
            errors.append("Employee ID is required")
        
        if not review_data.get("review_type"):
            errors.append("Review type is required")
        
        if review_data.get("review_type") not in self.review_templates:
            errors.append(f"Invalid review type: {review_data.get('review_type')}")
        
        return {
            "valid": len(errors) == 0,
            "errors": errors
        }
    
    async def _get_review_state(self, review_id: str) -> Optional[Dict[str, Any]]:
        """Get review state from memory or active reviews"""
        if review_id in self.active_reviews:
            return self.active_reviews[review_id]
        
        memory_result = AGENT_TOOLS["memory_store"]._run("get", review_id)
        if memory_result.get("success"):
            return memory_result.get("data")
        
        return None
    
    # Additional helper methods would be implemented here...
    # (For brevity, showing key structure)
    
    async def _setup_review_framework(self, review_data: Dict[str, Any], review_id: str) -> Dict[str, Any]:
        """Set up review framework"""
        return {"success": True, "framework": "established"}
    
    async def _initiate_data_collection(self, review_data: Dict[str, Any], review_id: str) -> Dict[str, Any]:
        """Initiate data collection"""
        return {"success": True, "collection": "initiated"}
    
    async def _setup_reviewers(self, review_data: Dict[str, Any], review_id: str) -> Dict[str, Any]:
        """Set up reviewers"""
        return {"success": True, "reviewers": []}
    
    async def _create_review_timeline(self, review_data: Dict[str, Any], review_id: str) -> Dict[str, Any]:
        """Create review timeline"""
        return {"success": True, "timeline": {}, "estimated_completion": "3 weeks"}

# Performance Review System Instance
performance_review_system = PerformanceReviewSystem()

# Export for external use
__all__ = ["performance_review_system", "PerformanceReviewSystem"]