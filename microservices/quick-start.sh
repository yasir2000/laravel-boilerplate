#!/bin/bash
# Quick Start Script for Apache Camel ERP Integration System

echo "🚀 Starting Apache Camel ERP Integration System..."

# Check if Docker is running
if ! docker info &> /dev/null; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Start all services
echo "📦 Starting all services..."
docker-compose up -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 30

# Run health check
echo "🔍 Running health check..."
if [ -f "testing/windows-test-suite.py" ]; then
    python testing/windows-test-suite.py
else
    echo "ℹ️  Test suite not found. Checking basic connectivity..."
    
    # Basic connectivity checks
    services=("integration-service:8083" "grafana:3000" "prometheus:9090")
    
    for service in "${services[@]}"; do
        IFS=':' read -r name port <<< "$service"
        if curl -f -s "http://localhost:$port/" > /dev/null 2>&1; then
            echo "✅ $name is accessible"
        else
            echo "⚠️  $name may not be ready yet"
        fi
    done
fi

echo ""
echo "🎉 System startup complete!"
echo ""
echo "📊 Access URLs:"
echo "   • Grafana Dashboard: http://localhost:3000 (admin/admin)"
echo "   • Prometheus: http://localhost:9090"
echo "   • RabbitMQ Management: http://localhost:15672 (guest/guest)"
echo "   • Integration Service: http://localhost:8083"
echo ""
echo "📚 Documentation:"
echo "   • Production Guide: PRODUCTION_DEPLOYMENT_GUIDE.md"
echo "   • Operational Status: OPERATIONAL_STATUS.md"
echo "   • Project Summary: PROJECT_COMPLETION_SUMMARY.md"
echo ""
