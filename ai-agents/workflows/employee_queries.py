"""
Employee Query Resolution System
Intelligent handling of employee queries with automated responses and escalation
"""

from datetime import datetime, timedelta
from typing import Dict, Any, List, Optional
import logging
import re
import json

from agents.core_agents import AGENTS
from agents.specialized_agents import SPECIALIZED_AGENTS
from tools.agent_tools import AGENT_TOOLS

logger = logging.getLogger(__name__)

class EmployeeQueryResolutionSystem:
    """Comprehensive employee query handling with intelligent triage and automation"""
    
    def __init__(self):
        self.active_query_workflows = {}
        self.knowledge_base = self._initialize_knowledge_base()
        self.query_categories = self._initialize_query_categories()
        self.escalation_rules = self._initialize_escalation_rules()
    
    async def process_employee_query(self, query_data: Dict[str, Any]) -> Dict[str, Any]:
        """Process employee query with intelligent resolution workflow"""
        workflow_id = f"query_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        
        try:
            logger.info(f"Starting employee query resolution workflow: {workflow_id}")
            
            # Step 1: Parse and categorize query
            query_analysis = await self._analyze_query(query_data)
            
            # Step 2: Determine urgency and priority
            priority_assessment = await self._assess_query_priority(query_data, query_analysis)
            
            # Step 3: Search knowledge base for existing solutions
            knowledge_search = await self._search_knowledge_base(query_analysis)
            
            # Step 4: Generate automated response if possible
            auto_response = await self._generate_automated_response(
                query_data, query_analysis, knowledge_search
            )
            
            # Step 5: Handle escalation if automated response insufficient
            escalation_result = await self._handle_query_escalation(
                query_data, query_analysis, auto_response
            )
            
            # Step 6: Route to appropriate specialist if needed
            specialist_routing = await self._route_to_specialist(
                query_data, query_analysis, escalation_result
            )
            
            # Step 7: Track query resolution progress
            tracking_setup = await self._setup_query_tracking(
                workflow_id, query_data, query_analysis
            )
            
            # Step 8: Schedule follow-up if required
            followup_scheduling = await self._schedule_query_followup(
                workflow_id, priority_assessment, specialist_routing
            )
            
            # Create comprehensive workflow state
            workflow_state = {
                "workflow_id": workflow_id,
                "employee_id": query_data.get("employee_id"),
                "query_type": query_analysis.get("category"),
                "priority": priority_assessment.get("priority"),
                "status": self._determine_initial_status(auto_response, escalation_result),
                "resolution_method": self._determine_resolution_method(auto_response, escalation_result),
                "estimated_resolution": priority_assessment.get("estimated_resolution"),
                "steps_completed": {
                    "query_analysis": query_analysis,
                    "priority_assessment": priority_assessment,
                    "knowledge_search": knowledge_search,
                    "auto_response": auto_response,
                    "escalation_handling": escalation_result,
                    "specialist_routing": specialist_routing,
                    "tracking_setup": tracking_setup,
                    "followup_scheduling": followup_scheduling
                },
                "created_at": datetime.now().isoformat(),
                "assigned_to": specialist_routing.get("assigned_agent"),
                "response_sent": auto_response.get("response_sent", False)
            }
            
            self.active_query_workflows[workflow_id] = workflow_state
            
            # Store in persistent memory
            AGENT_TOOLS["memory_store"]._run("set", workflow_id, workflow_state, ttl=2592000)  # 30 days
            
            return {
                "success": True,
                "workflow_id": workflow_id,
                "query_category": query_analysis.get("category"),
                "priority": priority_assessment.get("priority"),
                "resolution_method": workflow_state["resolution_method"],
                "response_provided": auto_response.get("response_sent", False),
                "estimated_resolution": priority_assessment.get("estimated_resolution"),
                "workflow_state": workflow_state,
                "next_actions": self._determine_next_actions(workflow_state)
            }
            
        except Exception as e:
            logger.error(f"Error in employee query resolution workflow: {str(e)}")
            return {
                "success": False,
                "workflow_id": workflow_id,
                "error": str(e)
            }
    
    async def _analyze_query(self, query_data: Dict[str, Any]) -> Dict[str, Any]:
        """Analyze and categorize employee query"""
        try:
            query_text = query_data.get("query", "").lower()
            employee_id = query_data.get("employee_id")
            
            # Extract key information using NLP
            key_entities = await self._extract_entities(query_text)
            
            # Categorize query
            category = await self._categorize_query(query_text, key_entities)
            
            # Identify intent
            intent = await self._identify_query_intent(query_text, category)
            
            # Extract context
            context = await self._extract_query_context(query_data, employee_id)
            
            # Determine complexity
            complexity = await self._assess_query_complexity(query_text, category, context)
            
            return {
                "success": True,
                "category": category,
                "intent": intent,
                "entities": key_entities,
                "context": context,
                "complexity": complexity,
                "confidence_score": self._calculate_analysis_confidence(category, intent, key_entities),
                "requires_human_review": complexity == "high" or category == "sensitive"
            }
            
        except Exception as e:
            logger.error(f"Error analyzing query: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _assess_query_priority(self, query_data: Dict[str, Any], query_analysis: Dict[str, Any]) -> Dict[str, Any]:
        """Assess query priority and urgency"""
        try:
            category = query_analysis.get("category")
            complexity = query_analysis.get("complexity")
            employee_id = query_data.get("employee_id")
            
            # Check for urgent keywords
            urgent_indicators = await self._check_urgent_indicators(query_data.get("query", ""))
            
            # Check employee status
            employee_context = await self._get_employee_context(employee_id)
            
            # Determine base priority
            base_priority = self._get_base_priority(category)
            
            # Apply modifiers
            priority_modifiers = self._apply_priority_modifiers(
                urgent_indicators, employee_context, complexity
            )
            
            # Calculate final priority
            final_priority = self._calculate_final_priority(base_priority, priority_modifiers)
            
            # Estimate resolution time
            estimated_resolution = self._estimate_resolution_time(final_priority, category, complexity)
            
            return {
                "success": True,
                "priority": final_priority,
                "urgency_level": urgent_indicators.get("level", "normal"),
                "estimated_resolution": estimated_resolution,
                "requires_immediate_attention": urgent_indicators.get("immediate", False),
                "priority_factors": {
                    "base_priority": base_priority,
                    "urgent_indicators": urgent_indicators,
                    "employee_context": employee_context,
                    "complexity_factor": complexity,
                    "modifiers": priority_modifiers
                }
            }
            
        except Exception as e:
            logger.error(f"Error assessing query priority: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _search_knowledge_base(self, query_analysis: Dict[str, Any]) -> Dict[str, Any]:
        """Search knowledge base for relevant solutions"""
        try:
            category = query_analysis.get("category")
            entities = query_analysis.get("entities", [])
            intent = query_analysis.get("intent")
            
            # Search by category
            category_results = self._search_by_category(category)
            
            # Search by entities
            entity_results = self._search_by_entities(entities)
            
            # Search by intent
            intent_results = self._search_by_intent(intent)
            
            # Combine and rank results
            combined_results = self._combine_search_results(
                category_results, entity_results, intent_results
            )
            
            # Find best matches
            best_matches = self._rank_knowledge_results(combined_results, query_analysis)
            
            return {
                "success": True,
                "total_results": len(combined_results),
                "best_matches": best_matches[:5],  # Top 5 matches
                "has_exact_match": len(best_matches) > 0 and best_matches[0].get("confidence", 0) > 0.8,
                "search_confidence": best_matches[0].get("confidence", 0) if best_matches else 0
            }
            
        except Exception as e:
            logger.error(f"Error searching knowledge base: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _generate_automated_response(self, query_data: Dict[str, Any], 
                                         query_analysis: Dict[str, Any], 
                                         knowledge_search: Dict[str, Any]) -> Dict[str, Any]:
        """Generate automated response if possible"""
        try:
            # Check if automated response is appropriate
            can_auto_respond = self._can_provide_automated_response(query_analysis, knowledge_search)
            
            if not can_auto_respond:
                return {
                    "success": True,
                    "can_auto_respond": False,
                    "reason": "Query requires human attention",
                    "response_sent": False
                }
            
            # Get best knowledge match
            best_match = knowledge_search.get("best_matches", [{}])[0]
            
            # Generate personalized response
            response_content = await self._generate_response_content(
                query_data, query_analysis, best_match
            )
            
            # Send response to employee
            send_result = await self._send_response_to_employee(
                query_data.get("employee_id"), response_content
            )
            
            # Log the automated response
            await self._log_automated_response(query_data, response_content, send_result)
            
            return {
                "success": True,
                "can_auto_respond": True,
                "response_content": response_content,
                "response_sent": send_result.get("success", False),
                "send_result": send_result,
                "knowledge_source": best_match.get("source"),
                "confidence": best_match.get("confidence", 0)
            }
            
        except Exception as e:
            logger.error(f"Error generating automated response: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _handle_query_escalation(self, query_data: Dict[str, Any], 
                                     query_analysis: Dict[str, Any], 
                                     auto_response: Dict[str, Any]) -> Dict[str, Any]:
        """Handle query escalation when needed"""
        try:
            needs_escalation = self._needs_escalation(query_analysis, auto_response)
            
            if not needs_escalation:
                return {
                    "success": True,
                    "escalated": False,
                    "reason": "Query resolved automatically"
                }
            
            # Determine escalation path
            escalation_path = self._determine_escalation_path(query_analysis)
            
            # Create escalation ticket
            escalation_ticket = await self._create_escalation_ticket(
                query_data, query_analysis, escalation_path
            )
            
            # Notify escalation recipients
            notification_result = await self._notify_escalation_recipients(
                escalation_ticket, escalation_path
            )
            
            return {
                "success": True,
                "escalated": True,
                "escalation_path": escalation_path,
                "escalation_ticket": escalation_ticket,
                "notification_result": notification_result,
                "escalation_priority": self._get_escalation_priority(query_analysis)
            }
            
        except Exception as e:
            logger.error(f"Error handling query escalation: {str(e)}")
            return {"success": False, "error": str(e)}
    
    async def _route_to_specialist(self, query_data: Dict[str, Any], 
                                 query_analysis: Dict[str, Any], 
                                 escalation_result: Dict[str, Any]) -> Dict[str, Any]:
        """Route query to appropriate specialist agent"""
        try:
            if not escalation_result.get("escalated", False):
                return {
                    "success": True,
                    "routed": False,
                    "reason": "Query resolved without specialist"
                }
            
            # Determine appropriate specialist
            specialist_agent = self._select_specialist_agent(query_analysis)
            
            if not specialist_agent:
                return {
                    "success": True,
                    "routed": False,
                    "reason": "No specialist required"
                }
            
            # Prepare handover data
            handover_data = self._prepare_specialist_handover(
                query_data, query_analysis, escalation_result
            )
            
            # Route to specialist
            routing_result = await self._route_to_agent(specialist_agent, handover_data)
            
            # Update tracking
            tracking_update = await self._update_specialist_tracking(
                query_data.get("employee_id"), specialist_agent, routing_result
            )
            
            return {
                "success": True,
                "routed": True,
                "assigned_agent": specialist_agent,
                "routing_result": routing_result,
                "handover_data": handover_data,
                "tracking_update": tracking_update,
                "estimated_response_time": self._get_specialist_response_time(specialist_agent)
            }
            
        except Exception as e:
            logger.error(f"Error routing to specialist: {str(e)}")
            return {"success": False, "error": str(e)}
    
    def _initialize_knowledge_base(self) -> Dict[str, Any]:
        """Initialize knowledge base with common queries and responses"""
        return {
            "payroll": {
                "salary_inquiry": {
                    "response": "Your salary information can be found in your employee portal under 'Payroll' section. If you need specific details, please contact HR.",
                    "confidence": 0.9,
                    "source": "hr_policy"
                },
                "tax_questions": {
                    "response": "For tax-related questions, please refer to your tax documents in the employee portal or contact our payroll team.",
                    "confidence": 0.8,
                    "source": "payroll_faq"
                }
            },
            "benefits": {
                "health_insurance": {
                    "response": "Health insurance information and enrollment details are available in your benefits portal. Open enrollment period is typically in November.",
                    "confidence": 0.9,
                    "source": "benefits_guide"
                },
                "vacation_policy": {
                    "response": "Our vacation policy allows for accrual of PTO based on tenure. Please check your current balance in the time-off system.",
                    "confidence": 0.85,
                    "source": "employee_handbook"
                }
            },
            "it_support": {
                "password_reset": {
                    "response": "To reset your password, visit the IT self-service portal or contact the IT helpdesk at ext. 4357.",
                    "confidence": 0.95,
                    "source": "it_procedures"
                },
                "software_access": {
                    "response": "For software access requests, please submit a ticket through the IT portal with business justification.",
                    "confidence": 0.9,
                    "source": "it_procedures"
                }
            },
            "general": {
                "company_policies": {
                    "response": "Company policies are available in the employee handbook accessible through the company intranet.",
                    "confidence": 0.8,
                    "source": "employee_handbook"
                }
            }
        }
    
    def _initialize_query_categories(self) -> List[str]:
        """Initialize query categories"""
        return [
            "payroll", "benefits", "it_support", "hr_policy", "time_off", 
            "performance", "training", "facilities", "general", "sensitive"
        ]
    
    def _initialize_escalation_rules(self) -> Dict[str, Any]:
        """Initialize escalation rules"""
        return {
            "payroll": {"agent": "payroll_agent", "priority": "high"},
            "benefits": {"agent": "hr_agent", "priority": "medium"},
            "it_support": {"agent": "it_support_agent", "priority": "medium"},
            "hr_policy": {"agent": "hr_agent", "priority": "medium"},
            "time_off": {"agent": "leave_processing_agent", "priority": "medium"},
            "performance": {"agent": "hr_agent", "priority": "high"},
            "training": {"agent": "training_agent", "priority": "low"},
            "facilities": {"agent": "project_manager_agent", "priority": "low"},
            "sensitive": {"agent": "hr_agent", "priority": "critical"},
            "general": {"agent": "hr_agent", "priority": "low"}
        }
    
    async def _extract_entities(self, query_text: str) -> List[Dict[str, Any]]:
        """Extract key entities from query text"""
        entities = []
        
        # Extract dates
        date_patterns = [
            r'\b\d{1,2}/\d{1,2}/\d{4}\b',
            r'\b\d{1,2}-\d{1,2}-\d{4}\b',
            r'\btoday\b', r'\btomorrow\b', r'\byesterday\b'
        ]
        
        for pattern in date_patterns:
            matches = re.findall(pattern, query_text, re.IGNORECASE)
            for match in matches:
                entities.append({"type": "date", "value": match})
        
        # Extract amounts
        amount_pattern = r'\$\d+(?:,\d{3})*(?:\.\d{2})?'
        amounts = re.findall(amount_pattern, query_text)
        for amount in amounts:
            entities.append({"type": "amount", "value": amount})
        
        # Extract employee references
        employee_patterns = [
            r'\bemployee\s+#?\d+\b',
            r'\bemp\s+#?\d+\b'
        ]
        
        for pattern in employee_patterns:
            matches = re.findall(pattern, query_text, re.IGNORECASE)
            for match in matches:
                entities.append({"type": "employee_id", "value": match})
        
        return entities
    
    async def _categorize_query(self, query_text: str, entities: List[Dict[str, Any]]) -> str:
        """Categorize query based on content"""
        # Keywords for each category
        category_keywords = {
            "payroll": ["salary", "pay", "paycheck", "tax", "withholding", "deduction", "overtime"],
            "benefits": ["insurance", "health", "dental", "vision", "401k", "retirement", "pto", "vacation"],
            "it_support": ["password", "login", "computer", "software", "access", "network", "email"],
            "hr_policy": ["policy", "handbook", "procedure", "code of conduct", "harassment"],
            "time_off": ["vacation", "pto", "sick", "leave", "time off", "holiday"],
            "performance": ["review", "evaluation", "feedback", "goal", "promotion"],
            "training": ["training", "course", "certification", "development", "workshop"],
            "facilities": ["office", "parking", "desk", "building", "facilities", "maintenance"],
            "sensitive": ["discrimination", "harassment", "complaint", "grievance", "legal"]
        }
        
        # Count keyword matches
        category_scores = {}
        for category, keywords in category_keywords.items():
            score = sum(1 for keyword in keywords if keyword in query_text.lower())
            if score > 0:
                category_scores[category] = score
        
        # Return category with highest score
        if category_scores:
            return max(category_scores, key=category_scores.get)
        
        return "general"
    
    def _can_provide_automated_response(self, query_analysis: Dict[str, Any], 
                                      knowledge_search: Dict[str, Any]) -> bool:
        """Determine if automated response is appropriate"""
        # Don't auto-respond to sensitive queries
        if query_analysis.get("category") == "sensitive":
            return False
        
        # Don't auto-respond to high complexity queries
        if query_analysis.get("complexity") == "high":
            return False
        
        # Don't auto-respond if no good knowledge match
        if not knowledge_search.get("has_exact_match"):
            return False
        
        # Don't auto-respond if confidence is too low
        if knowledge_search.get("search_confidence", 0) < 0.7:
            return False
        
        return True
    
    def _determine_initial_status(self, auto_response: Dict[str, Any], 
                                escalation_result: Dict[str, Any]) -> str:
        """Determine initial query status"""
        if auto_response.get("response_sent"):
            return "resolved"
        elif escalation_result.get("escalated"):
            return "escalated"
        else:
            return "pending"
    
    def _determine_resolution_method(self, auto_response: Dict[str, Any], 
                                   escalation_result: Dict[str, Any]) -> str:
        """Determine resolution method"""
        if auto_response.get("response_sent"):
            return "automated"
        elif escalation_result.get("escalated"):
            return "escalated"
        else:
            return "pending_review"
    
    def _determine_next_actions(self, workflow_state: Dict[str, Any]) -> List[str]:
        """Determine next actions based on workflow state"""
        actions = []
        
        status = workflow_state.get("status")
        resolution_method = workflow_state.get("resolution_method")
        
        if status == "resolved":
            actions.append("Monitor for follow-up questions")
        elif status == "escalated":
            actions.append("Track specialist response")
            actions.append("Follow up on resolution timeline")
        elif status == "pending":
            actions.append("Review query manually")
            actions.append("Assign to appropriate agent")
        
        if workflow_state.get("priority") == "critical":
            actions.append("Immediate attention required")
        
        return actions

# Employee Query Resolution System Instance
employee_query_system = EmployeeQueryResolutionSystem()

# Export for external use
__all__ = ["employee_query_system", "EmployeeQueryResolutionSystem"]