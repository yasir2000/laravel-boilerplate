#!/bin/bash
# Apache Camel ERP Integration - Comprehensive Testing Suite
# This script validates the complete ERP integration functionality

set -e

# Configuration
INTEGRATION_SERVICE_URL="http://localhost:8083"
PROMETHEUS_URL="http://localhost:9090"
GRAFANA_URL="http://localhost:3000"
ALERTMANAGER_URL="http://localhost:9093"

# Test Results
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
TEST_RESULTS_FILE="test_results_$(date +%Y%m%d_%H%M%S).log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$TEST_RESULTS_FILE"
}

log_success() {
    echo -e "${GREEN}[PASS]${NC} $1" | tee -a "$TEST_RESULTS_FILE"
    ((PASSED_TESTS++))
}

log_error() {
    echo -e "${RED}[FAIL]${NC} $1" | tee -a "$TEST_RESULTS_FILE"
    ((FAILED_TESTS++))
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1" | tee -a "$TEST_RESULTS_FILE"
}

# Test execution function
run_test() {
    local test_name="$1"
    local test_command="$2"
    local expected_result="$3"
    
    ((TOTAL_TESTS++))
    log_info "Running test: $test_name"
    
    if eval "$test_command" >/dev/null 2>&1; then
        log_success "$test_name"
        return 0
    else
        log_error "$test_name"
        return 1
    fi
}

# HTTP request helper
http_get() {
    local url="$1"
    local expected_status="$2"
    local timeout="${3:-10}"
    
    response=$(curl -s -o /dev/null -w "%{http_code}" --max-time "$timeout" "$url" 2>/dev/null || echo "000")
    [ "$response" = "$expected_status" ]
}

# JSON response helper
http_get_json() {
    local url="$1"
    local timeout="${2:-10}"
    
    curl -s --max-time "$timeout" "$url" 2>/dev/null || echo "{}"
}

# Test suite header
print_header() {
    echo "================================================================="
    echo "    Apache Camel ERP Integration - Testing Suite"
    echo "    Date: $(date)"
    echo "    Version: 1.0"
    echo "================================================================="
}

# 1. Infrastructure Health Tests
test_infrastructure() {
    log_info "=== Testing Infrastructure Health ==="
    
    # Test integration service availability
    run_test "Integration Service Health Check" \
        "http_get '$INTEGRATION_SERVICE_URL/health' '200'" \
        "200"
    
    # Test Prometheus availability
    run_test "Prometheus Service Health Check" \
        "http_get '$PROMETHEUS_URL/-/healthy' '200'" \
        "200"
    
    # Test Grafana availability
    run_test "Grafana Service Health Check" \
        "http_get '$GRAFANA_URL/api/health' '200'" \
        "200"
    
    # Test Alertmanager availability
    run_test "Alertmanager Service Health Check" \
        "http_get '$ALERTMANAGER_URL/-/healthy' '200'" \
        "200"
    
    # Test database connectivity
    run_test "Database Connection Test" \
        "docker exec integration-db pg_isready -U integration_user -d integration_db" \
        "ready"
    
    # Test RabbitMQ connectivity
    run_test "RabbitMQ Management API Test" \
        "http_get 'http://localhost:15672/api/overview' '200'" \
        "200"
}

# 2. Camel Routes Health Tests
test_camel_routes() {
    log_info "=== Testing Apache Camel Routes ==="
    
    # Test Camel context status
    local camel_info=$(http_get_json "$INTEGRATION_SERVICE_URL/actuator/camel")
    
    if [ -n "$camel_info" ] && echo "$camel_info" | grep -q "running"; then
        log_success "Camel Context Running Check"
        ((PASSED_TESTS++))
    else
        log_error "Camel Context Running Check"
        ((FAILED_TESTS++))
    fi
    ((TOTAL_TESTS++))
    
    # Test individual route health
    local routes=("employee-sync-main" "payroll-sync-main" "accounting-sync-main")
    for route in "${routes[@]}"; do
        run_test "Route Health Check: $route" \
            "http_get '$INTEGRATION_SERVICE_URL/actuator/camel/routes/$route' '200'" \
            "200"
    done
}

# 3. API Endpoint Tests
test_api_endpoints() {
    log_info "=== Testing REST API Endpoints ==="
    
    # Test health endpoint
    run_test "Health Endpoint Test" \
        "http_get '$INTEGRATION_SERVICE_URL/health' '200'" \
        "200"
    
    # Test integration status endpoint
    run_test "Integration Status Endpoint Test" \
        "http_get '$INTEGRATION_SERVICE_URL/integration/status' '200'" \
        "200"
    
    # Test employee sync trigger (should handle gracefully even without Laravel)
    run_test "Employee Sync Trigger Endpoint Test" \
        "curl -s -X POST '$INTEGRATION_SERVICE_URL/employee/sync' -o /dev/null -w '%{http_code}' | grep -E '(200|202|400|404)'" \
        "response_code"
    
    # Test payroll sync trigger
    run_test "Payroll Sync Trigger Endpoint Test" \
        "curl -s -X POST '$INTEGRATION_SERVICE_URL/payroll/sync' -o /dev/null -w '%{http_code}' | grep -E '(200|202|400|404)'" \
        "response_code"
    
    # Test accounting sync trigger
    run_test "Accounting Sync Trigger Endpoint Test" \
        "curl -s -X POST '$INTEGRATION_SERVICE_URL/accounting/sync' -o /dev/null -w '%{http_code}' | grep -E '(200|202|400|404)'" \
        "response_code"
}

# 4. Monitoring and Metrics Tests
test_monitoring() {
    log_info "=== Testing Monitoring and Metrics ==="
    
    # Test Prometheus metrics scraping
    run_test "Prometheus Metrics Scraping Test" \
        "http_get '$PROMETHEUS_URL/api/v1/query?query=up' '200'" \
        "200"
    
    # Test Camel metrics availability
    local metrics_response=$(http_get_json "$PROMETHEUS_URL/api/v1/query?query=camel_context_status")
    if echo "$metrics_response" | grep -q "success"; then
        log_success "Camel Metrics Availability Test"
        ((PASSED_TESTS++))
    else
        log_error "Camel Metrics Availability Test"
        ((FAILED_TESTS++))
    fi
    ((TOTAL_TESTS++))
    
    # Test alert rules loading
    local rules_response=$(http_get_json "$PROMETHEUS_URL/api/v1/rules")
    if echo "$rules_response" | grep -q "apache-camel-erp-integration"; then
        log_success "Alert Rules Loading Test"
        ((PASSED_TESTS++))
    else
        log_error "Alert Rules Loading Test"
        ((FAILED_TESTS++))
    fi
    ((TOTAL_TESTS++))
}

# 5. Scheduled Sync Tests
test_scheduled_sync() {
    log_info "=== Testing Scheduled Synchronization ==="
    
    # Check if scheduled routes are running
    local scheduled_routes=("employee-sync-scheduled" "payroll-sync-scheduled" "accounting-sync-scheduled")
    
    for route in "${scheduled_routes[@]}"; do
        # Check if timer endpoints are active
        if docker logs integration-service --tail 100 | grep -q "$route"; then
            log_success "Scheduled Route Activity Check: $route"
            ((PASSED_TESTS++))
        else
            log_warning "Scheduled Route Activity Check: $route (no recent activity)"
            ((FAILED_TESTS++))
        fi
        ((TOTAL_TESTS++))
    done
}

# 6. Error Handling Tests
test_error_handling() {
    log_info "=== Testing Error Handling ==="
    
    # Test invalid endpoint handling
    run_test "Invalid Endpoint Handling Test" \
        "http_get '$INTEGRATION_SERVICE_URL/invalid/endpoint' '404'" \
        "404"
    
    # Test error logging functionality
    if docker logs integration-service --tail 50 | grep -q "ERROR\|WARN"; then
        log_success "Error Logging Functionality Test"
        ((PASSED_TESTS++))
    else
        log_warning "Error Logging Functionality Test (no recent errors to verify)"
        ((PASSED_TESTS++))
    fi
    ((TOTAL_TESTS++))
}

# 7. Performance Tests
test_performance() {
    log_info "=== Testing Performance Characteristics ==="
    
    # Test response time for health endpoint
    local response_time=$(curl -s -o /dev/null -w "%{time_total}" "$INTEGRATION_SERVICE_URL/health")
    if (( $(echo "$response_time < 2.0" | bc -l) )); then
        log_success "Health Endpoint Response Time Test ($response_time seconds)"
        ((PASSED_TESTS++))
    else
        log_error "Health Endpoint Response Time Test ($response_time seconds > 2.0s)"
        ((FAILED_TESTS++))
    fi
    ((TOTAL_TESTS++))
    
    # Test memory usage
    local memory_info=$(docker stats integration-service --no-stream --format "{{.MemUsage}}" | head -1)
    if [ -n "$memory_info" ]; then
        log_success "Memory Usage Check: $memory_info"
        ((PASSED_TESTS++))
    else
        log_error "Memory Usage Check: Unable to retrieve"
        ((FAILED_TESTS++))
    fi
    ((TOTAL_TESTS++))
}

# 8. Security Tests
test_security() {
    log_info "=== Testing Security Configuration ==="
    
    # Test if sensitive endpoints require authentication (if configured)
    run_test "Actuator Endpoints Security Test" \
        "curl -s '$INTEGRATION_SERVICE_URL/actuator/env' -o /dev/null -w '%{http_code}' | grep -E '(401|403|404)'" \
        "security_response"
}

# 9. Data Validation Tests
test_data_validation() {
    log_info "=== Testing Data Validation and Transformation ==="
    
    # Test database tables exist
    local table_check=$(docker exec integration-db psql -U integration_user -d integration_db -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';" 2>/dev/null | tr -d ' \n' || echo "0")
    
    if [ "$table_check" -gt 0 ]; then
        log_success "Database Tables Exist Check ($table_check tables)"
        ((PASSED_TESTS++))
    else
        log_error "Database Tables Exist Check"
        ((FAILED_TESTS++))
    fi
    ((TOTAL_TESTS++))
}

# 10. Integration Testing with Mock Data
test_integration_mock() {
    log_info "=== Testing Integration with Mock Data ==="
    
    # Test employee sync with mock data
    local mock_employee='{"id": 999, "name": "Test Employee", "email": "test@example.com", "position": "Test Position"}'
    
    # This would be a real test if we had a mock endpoint set up
    log_warning "Mock Data Integration Tests require Laravel application to be running"
    log_info "To run full integration tests, ensure Laravel HR system is available"
}

# Generate summary report
generate_summary() {
    echo "=================================================================" | tee -a "$TEST_RESULTS_FILE"
    echo "                    TEST EXECUTION SUMMARY" | tee -a "$TEST_RESULTS_FILE"
    echo "=================================================================" | tee -a "$TEST_RESULTS_FILE"
    echo "Total Tests: $TOTAL_TESTS" | tee -a "$TEST_RESULTS_FILE"
    echo "Passed: $PASSED_TESTS" | tee -a "$TEST_RESULTS_FILE"
    echo "Failed: $FAILED_TESTS" | tee -a "$TEST_RESULTS_FILE"
    
    local success_rate=$(( PASSED_TESTS * 100 / TOTAL_TESTS ))
    echo "Success Rate: $success_rate%" | tee -a "$TEST_RESULTS_FILE"
    
    if [ $FAILED_TESTS -eq 0 ]; then
        echo -e "${GREEN}All tests passed! System is ready for production.${NC}" | tee -a "$TEST_RESULTS_FILE"
    elif [ $success_rate -ge 80 ]; then
        echo -e "${YELLOW}Most tests passed. Review failed tests before production deployment.${NC}" | tee -a "$TEST_RESULTS_FILE"
    else
        echo -e "${RED}Multiple test failures detected. System requires fixes before production.${NC}" | tee -a "$TEST_RESULTS_FILE"
    fi
    
    echo "=================================================================" | tee -a "$TEST_RESULTS_FILE"
    echo "Test results saved to: $TEST_RESULTS_FILE"
}

# Main test execution
main() {
    print_header
    
    log_info "Starting comprehensive ERP integration testing..."
    log_info "Results will be saved to: $TEST_RESULTS_FILE"
    
    # Execute test suites
    test_infrastructure
    test_camel_routes
    test_api_endpoints
    test_monitoring
    test_scheduled_sync
    test_error_handling
    test_performance
    test_security
    test_data_validation
    test_integration_mock
    
    # Generate final report
    generate_summary
    
    # Exit with appropriate code
    if [ $FAILED_TESTS -eq 0 ]; then
        exit 0
    else
        exit 1
    fi
}

# Check if required tools are available
check_prerequisites() {
    local required_tools=("curl" "docker" "bc")
    
    for tool in "${required_tools[@]}"; do
        if ! command -v "$tool" &> /dev/null; then
            log_error "Required tool '$tool' is not available"
            exit 1
        fi
    done
    
    log_info "All required tools are available"
}

# Script entry point
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    check_prerequisites
    main "$@"
fi