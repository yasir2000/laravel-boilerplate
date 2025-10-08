#!/usr/bin/env python3
"""
Apache Camel ERP Integration - Advanced Testing Suite
Comprehensive testing framework for validating ERP integration functionality
"""

import os
import sys
import json
import time
import requests
import subprocess
import logging
from datetime import datetime
from typing import Dict, List, Optional, Any
from dataclasses import dataclass
from concurrent.futures import ThreadPoolExecutor, as_completed

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(f'test_results_{datetime.now().strftime("%Y%m%d_%H%M%S")}.log'),
        logging.StreamHandler(sys.stdout)
    ]
)

logger = logging.getLogger(__name__)

@dataclass
class TestResult:
    """Test result data structure"""
    name: str
    passed: bool
    duration: float
    error_message: Optional[str] = None
    details: Optional[Dict[str, Any]] = None

class ERPIntegrationTester:
    """Comprehensive ERP Integration Testing Framework"""
    
    def __init__(self):
        self.config = {
            'integration_service_url': 'http://localhost:8083',
            'prometheus_url': 'http://localhost:9090',
            'grafana_url': 'http://localhost:3000',
            'alertmanager_url': 'http://localhost:9093',
            'timeout': 30,
            'retry_attempts': 3,
            'retry_delay': 2
        }
        
        self.results: List[TestResult] = []
        self.start_time = time.time()
    
    def http_request(self, method: str, url: str, **kwargs) -> requests.Response:
        """Make HTTP request with retry logic"""
        for attempt in range(self.config['retry_attempts']):
            try:
                response = requests.request(
                    method, url, 
                    timeout=self.config['timeout'],
                    **kwargs
                )
                return response
            except requests.RequestException as e:
                if attempt == self.config['retry_attempts'] - 1:
                    raise e
                time.sleep(self.config['retry_delay'])
        
    def run_test(self, test_name: str, test_func, *args, **kwargs) -> TestResult:
        """Execute a test and record results"""
        logger.info(f"Running test: {test_name}")
        start_time = time.time()
        
        try:
            result = test_func(*args, **kwargs)
            duration = time.time() - start_time
            
            if result:
                logger.info(f"✅ PASS: {test_name} ({duration:.2f}s)")
                test_result = TestResult(test_name, True, duration)
            else:
                logger.error(f"❌ FAIL: {test_name} ({duration:.2f}s)")
                test_result = TestResult(test_name, False, duration)
                
        except Exception as e:
            duration = time.time() - start_time
            logger.error(f"❌ ERROR: {test_name} ({duration:.2f}s) - {str(e)}")
            test_result = TestResult(test_name, False, duration, str(e))
        
        self.results.append(test_result)
        return test_result
    
    # Infrastructure Tests
    def test_service_health(self) -> bool:
        """Test basic service health endpoints"""
        services = {
            'Integration Service': f"{self.config['integration_service_url']}/health",
            'Prometheus': f"{self.config['prometheus_url']}/-/healthy",
            'Grafana': f"{self.config['grafana_url']}/api/health",
            'Alertmanager': f"{self.config['alertmanager_url']}/-/healthy"
        }
        
        all_healthy = True
        for service, url in services.items():
            try:
                response = self.http_request('GET', url)
                if response.status_code == 200:
                    logger.info(f"✅ {service} is healthy")
                else:
                    logger.error(f"❌ {service} health check failed: {response.status_code}")
                    all_healthy = False
            except Exception as e:
                logger.error(f"❌ {service} health check failed: {str(e)}")
                all_healthy = False
        
        return all_healthy
    
    def test_docker_containers(self) -> bool:
        """Test Docker container status"""
        try:
            result = subprocess.run(
                ['docker', 'ps', '--format', 'table {{.Names}}\t{{.Status}}'],
                capture_output=True, text=True, check=True
            )
            
            required_containers = [
                'integration-service', 'integration-db', 'rabbitmq', 
                'redis', 'prometheus', 'grafana', 'alertmanager'
            ]
            
            running_containers = result.stdout.lower()
            all_running = True
            
            for container in required_containers:
                if container in running_containers and 'up' in running_containers:
                    logger.info(f"✅ Container {container} is running")
                else:
                    logger.error(f"❌ Container {container} is not running properly")
                    all_running = False
            
            return all_running
            
        except subprocess.CalledProcessError as e:
            logger.error(f"Failed to check Docker containers: {e}")
            return False
    
    # API Endpoint Tests
    def test_rest_endpoints(self) -> bool:
        """Test REST API endpoints"""
        endpoints = [
            ('GET', '/health', 200),
            ('GET', '/integration/status', 200),
            ('POST', '/employee/sync', [200, 202, 400, 404]),
            ('POST', '/payroll/sync', [200, 202, 400, 404]),
            ('POST', '/accounting/sync', [200, 202, 400, 404]),
            ('GET', '/employee/status', [200, 404]),
            ('GET', '/payroll/status', [200, 404]),
            ('GET', '/accounting/status', [200, 404])
        ]
        
        all_passed = True
        base_url = self.config['integration_service_url']
        
        for method, endpoint, expected_codes in endpoints:
            try:
                url = f"{base_url}{endpoint}"
                response = self.http_request(method, url)
                
                if isinstance(expected_codes, list):
                    success = response.status_code in expected_codes
                else:
                    success = response.status_code == expected_codes
                
                if success:
                    logger.info(f"✅ {method} {endpoint}: {response.status_code}")
                else:
                    logger.error(f"❌ {method} {endpoint}: Expected {expected_codes}, got {response.status_code}")
                    all_passed = False
                    
            except Exception as e:
                logger.error(f"❌ {method} {endpoint}: {str(e)}")
                all_passed = False
        
        return all_passed
    
    def test_actuator_endpoints(self) -> bool:
        """Test Spring Boot Actuator endpoints"""
        actuator_endpoints = [
            '/actuator/health',
            '/actuator/info',
            '/actuator/metrics',
            '/actuator/prometheus'
        ]
        
        all_passed = True
        base_url = self.config['integration_service_url']
        
        for endpoint in actuator_endpoints:
            try:
                url = f"{base_url}{endpoint}"
                response = self.http_request('GET', url)
                
                if response.status_code in [200, 401, 403]:  # 401/403 acceptable if secured
                    logger.info(f"✅ Actuator {endpoint}: {response.status_code}")
                else:
                    logger.error(f"❌ Actuator {endpoint}: {response.status_code}")
                    all_passed = False
                    
            except Exception as e:
                logger.error(f"❌ Actuator {endpoint}: {str(e)}")
                all_passed = False
        
        return all_passed
    
    # Camel Routes Tests
    def test_camel_routes(self) -> bool:
        """Test Apache Camel routes status"""
        try:
            # Test Camel context
            url = f"{self.config['integration_service_url']}/actuator/camel"
            response = self.http_request('GET', url)
            
            if response.status_code == 200:
                camel_info = response.json()
                if camel_info.get('camelContext', {}).get('status') == 'Started':
                    logger.info("✅ Camel Context is running")
                    
                    # Check route count
                    route_count = len(camel_info.get('routes', []))
                    if route_count >= 70:  # We expect 73+ routes
                        logger.info(f"✅ Route count check: {route_count} routes")
                        return True
                    else:
                        logger.error(f"❌ Insufficient routes: {route_count} (expected >= 70)")
                        return False
                else:
                    logger.error("❌ Camel Context is not running")
                    return False
            else:
                logger.error(f"❌ Failed to get Camel info: {response.status_code}")
                return False
                
        except Exception as e:
            logger.error(f"❌ Camel routes test failed: {str(e)}")
            return False
    
    # Monitoring Tests
    def test_metrics_collection(self) -> bool:
        """Test metrics collection and Prometheus integration"""
        try:
            # Test Prometheus metrics endpoint
            url = f"{self.config['prometheus_url']}/api/v1/query"
            params = {'query': 'up{job="integration-service"}'}
            
            response = self.http_request('GET', url, params=params)
            
            if response.status_code == 200:
                data = response.json()
                if data['status'] == 'success' and data['data']['result']:
                    logger.info("✅ Prometheus metrics collection working")
                    return True
                else:
                    logger.error("❌ No metrics data from integration service")
                    return False
            else:
                logger.error(f"❌ Prometheus query failed: {response.status_code}")
                return False
                
        except Exception as e:
            logger.error(f"❌ Metrics collection test failed: {str(e)}")
            return False
    
    def test_alert_rules(self) -> bool:
        """Test alert rules configuration"""
        try:
            url = f"{self.config['prometheus_url']}/api/v1/rules"
            response = self.http_request('GET', url)
            
            if response.status_code == 200:
                rules_data = response.json()
                if rules_data['status'] == 'success':
                    rule_groups = rules_data['data']['groups']
                    
                    # Check for our alert rule group
                    erp_rules = [g for g in rule_groups if g['name'] == 'apache-camel-erp-integration']
                    if erp_rules:
                        rule_count = len(erp_rules[0]['rules'])
                        logger.info(f"✅ Alert rules loaded: {rule_count} rules")
                        return True
                    else:
                        logger.error("❌ ERP integration alert rules not found")
                        return False
                else:
                    logger.error("❌ Failed to get rules data")
                    return False
            else:
                logger.error(f"❌ Alert rules query failed: {response.status_code}")
                return False
                
        except Exception as e:
            logger.error(f"❌ Alert rules test failed: {str(e)}")
            return False
    
    # Performance Tests
    def test_performance(self) -> bool:
        """Test system performance characteristics"""
        try:
            # Test response time
            url = f"{self.config['integration_service_url']}/health"
            
            response_times = []
            for i in range(5):
                start_time = time.time()
                response = self.http_request('GET', url)
                end_time = time.time()
                
                if response.status_code == 200:
                    response_times.append(end_time - start_time)
                else:
                    logger.error(f"❌ Performance test request {i+1} failed")
                    return False
            
            avg_response_time = sum(response_times) / len(response_times)
            max_response_time = max(response_times)
            
            if avg_response_time < 2.0 and max_response_time < 5.0:
                logger.info(f"✅ Performance test passed - Avg: {avg_response_time:.3f}s, Max: {max_response_time:.3f}s")
                return True
            else:
                logger.error(f"❌ Performance test failed - Avg: {avg_response_time:.3f}s, Max: {max_response_time:.3f}s")
                return False
                
        except Exception as e:
            logger.error(f"❌ Performance test failed: {str(e)}")
            return False
    
    # Data Validation Tests
    def test_database_connectivity(self) -> bool:
        """Test database connectivity and structure"""
        try:
            # Test database connection through Docker
            result = subprocess.run([
                'docker', 'exec', 'integration-db', 
                'pg_isready', '-U', 'integration_user', '-d', 'integration_db'
            ], capture_output=True, text=True)
            
            if result.returncode == 0:
                logger.info("✅ Database connectivity test passed")
                return True
            else:
                logger.error(f"❌ Database connectivity test failed: {result.stderr}")
                return False
                
        except Exception as e:
            logger.error(f"❌ Database connectivity test failed: {str(e)}")
            return False
    
    # Comprehensive Test Suite
    def run_comprehensive_tests(self) -> Dict[str, Any]:
        """Run the complete test suite"""
        logger.info("🚀 Starting comprehensive ERP integration testing...")
        
        # Infrastructure Tests
        self.run_test("Service Health Check", self.test_service_health)
        self.run_test("Docker Containers Status", self.test_docker_containers)
        self.run_test("Database Connectivity", self.test_database_connectivity)
        
        # API Tests
        self.run_test("REST Endpoints", self.test_rest_endpoints)
        self.run_test("Actuator Endpoints", self.test_actuator_endpoints)
        
        # Camel Tests
        self.run_test("Camel Routes Status", self.test_camel_routes)
        
        # Monitoring Tests
        self.run_test("Metrics Collection", self.test_metrics_collection)
        self.run_test("Alert Rules Configuration", self.test_alert_rules)
        
        # Performance Tests
        self.run_test("System Performance", self.test_performance)
        
        return self.generate_report()
    
    def generate_report(self) -> Dict[str, Any]:
        """Generate comprehensive test report"""
        total_tests = len(self.results)
        passed_tests = sum(1 for r in self.results if r.passed)
        failed_tests = total_tests - passed_tests
        success_rate = (passed_tests / total_tests * 100) if total_tests > 0 else 0
        total_duration = time.time() - self.start_time
        
        report = {
            'summary': {
                'total_tests': total_tests,
                'passed_tests': passed_tests,
                'failed_tests': failed_tests,
                'success_rate': round(success_rate, 2),
                'total_duration': round(total_duration, 2),
                'timestamp': datetime.now().isoformat()
            },
            'test_results': [
                {
                    'name': r.name,
                    'passed': r.passed,
                    'duration': round(r.duration, 3),
                    'error_message': r.error_message
                } for r in self.results
            ],
            'recommendations': []
        }
        
        # Add recommendations based on results
        if success_rate >= 95:
            report['recommendations'].append("✅ System is ready for production deployment")
        elif success_rate >= 80:
            report['recommendations'].append("⚠️ Most tests passed. Review failed tests before production")
        else:
            report['recommendations'].append("❌ Multiple failures detected. System needs fixes before production")
        
        if failed_tests > 0:
            failed_test_names = [r.name for r in self.results if not r.passed]
            report['recommendations'].append(f"Failed tests to investigate: {', '.join(failed_test_names)}")
        
        # Log summary
        logger.info("=" * 80)
        logger.info("                    TEST EXECUTION SUMMARY")
        logger.info("=" * 80)
        logger.info(f"Total Tests: {total_tests}")
        logger.info(f"Passed: {passed_tests}")
        logger.info(f"Failed: {failed_tests}")
        logger.info(f"Success Rate: {success_rate:.1f}%")
        logger.info(f"Total Duration: {total_duration:.2f} seconds")
        logger.info("=" * 80)
        
        for recommendation in report['recommendations']:
            logger.info(recommendation)
        
        return report

def main():
    """Main execution function"""
    tester = ERPIntegrationTester()
    
    try:
        report = tester.run_comprehensive_tests()
        
        # Save detailed report
        report_file = f"test_report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
        with open(report_file, 'w') as f:
            json.dump(report, f, indent=2)
        
        logger.info(f"📊 Detailed report saved to: {report_file}")
        
        # Exit with appropriate code
        if report['summary']['failed_tests'] == 0:
            sys.exit(0)
        else:
            sys.exit(1)
            
    except KeyboardInterrupt:
        logger.info("⚠️ Testing interrupted by user")
        sys.exit(130)
    except Exception as e:
        logger.error(f"❌ Unexpected error during testing: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    main()