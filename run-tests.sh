#!/bin/bash

# Playwright Test Runner Script for HR Management System
# This script sets up the environment and runs the test suite

set -e

echo "🎭 HR Management System - Playwright Test Runner"
echo "================================================="

# Check if Laravel server is running
check_server() {
    echo "🔍 Checking if Laravel server is running..."
    if curl -s http://localhost:8000 > /dev/null; then
        echo "✅ Laravel server is running"
        return 0
    else
        echo "❌ Laravel server is not running"
        return 1
    fi
}

# Start Laravel server if not running
start_server() {
    echo "🚀 Starting Laravel server..."
    php artisan serve &
    SERVER_PID=$!
    
    # Wait for server to start
    echo "⏳ Waiting for server to start..."
    sleep 5
    
    # Check if server started successfully
    if check_server; then
        echo "✅ Server started successfully (PID: $SERVER_PID)"
    else
        echo "❌ Failed to start server"
        exit 1
    fi
}

# Stop Laravel server
stop_server() {
    if [ ! -z "$SERVER_PID" ]; then
        echo "🛑 Stopping Laravel server (PID: $SERVER_PID)..."
        kill $SERVER_PID
    fi
}

# Cleanup on exit
cleanup() {
    echo "🧹 Cleaning up..."
    stop_server
}
trap cleanup EXIT

# Main execution
main() {
    # Check if server is already running
    if ! check_server; then
        start_server
    fi
    
    # Ensure test database is set up
    echo "🗄️ Setting up test database..."
    php artisan migrate:fresh --seed --force --env=testing
    
    # Create test user if not exists
    echo "👤 Creating test user..."
    php artisan tinker --execute="
    if (!\App\Models\User::where('email', 'test@example.com')->exists()) {
        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);
        echo 'Test user created successfully\n';
    } else {
        echo 'Test user already exists\n';
    }
    "
    
    # Install Playwright browsers if needed
    echo "🌐 Ensuring Playwright browsers are installed..."
    npx playwright install --with-deps
    
    # Run tests
    echo "🧪 Running Playwright tests..."
    npx playwright test "$@"
    
    # Show report if tests completed
    if [ $? -eq 0 ]; then
        echo "🎉 Tests completed successfully!"
        echo "📊 Opening test report..."
        npx playwright show-report
    else
        echo "❌ Some tests failed. Check the report for details."
        echo "📊 Test report available at: playwright-report/index.html"
    fi
}

# Handle command line arguments
case "${1:-}" in
    "help"|"-h"|"--help")
        echo "Usage: $0 [options]"
        echo ""
        echo "Options:"
        echo "  help, -h, --help    Show this help message"
        echo "  setup               Set up test environment only"
        echo "  headed              Run tests with visible browser"
        echo "  debug               Run tests in debug mode"
        echo "  ui                  Open Playwright test UI"
        echo ""
        echo "Examples:"
        echo "  $0                  Run all tests"
        echo "  $0 headed           Run tests with visible browser"
        echo "  $0 tests/e2e/hr/    Run only HR tests"
        ;;
    "setup")
        echo "🔧 Setting up test environment..."
        if ! check_server; then
            start_server
        fi
        php artisan migrate:fresh --seed --force --env=testing
        npx playwright install --with-deps
        echo "✅ Test environment ready!"
        ;;
    "headed")
        main --headed
        ;;
    "debug")
        main --debug
        ;;
    "ui")
        if ! check_server; then
            start_server
        fi
        npx playwright test --ui
        ;;
    *)
        main "$@"
        ;;
esac