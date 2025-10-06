#!/bin/bash
# Quick start script for Laravel Octane on Docker

echo "🚀 Starting Laravel Octane on Docker..."

# Build and start
docker-compose up --build -d

echo "✅ Laravel app with Octane running at: http://localhost:8000"
echo "🔧 To see logs: docker-compose logs -f app"
echo "🛑 To stop: docker-compose down"