#!/bin/bash

# CrewAI Agent System Startup Script
# This script sets up and starts the AI agent system

set -e

echo "==================================="
echo "CrewAI Agent System Setup"
echo "==================================="

# Check if Python 3.8+ is available
python_version=$(python3 --version 2>&1 | cut -d' ' -f2 | cut -d'.' -f1-2)
required_version="3.8"

if ! python3 -c "import sys; exit(0 if sys.version_info >= (3, 8) else 1)" 2>/dev/null; then
    echo "Error: Python 3.8 or higher is required"
    echo "Current version: $python_version"
    exit 1
fi

echo "✓ Python version check passed: $python_version"

# Create virtual environment if it doesn't exist
if [ ! -d "venv" ]; then
    echo "Creating Python virtual environment..."
    python3 -m venv venv
    echo "✓ Virtual environment created"
fi

# Activate virtual environment
echo "Activating virtual environment..."
source venv/bin/activate
echo "✓ Virtual environment activated"

# Upgrade pip
echo "Upgrading pip..."
pip install --upgrade pip

# Install dependencies
echo "Installing Python dependencies..."
pip install -r requirements.txt
echo "✓ Dependencies installed"

# Create logs directory
if [ ! -d "logs" ]; then
    mkdir -p logs
    echo "✓ Logs directory created"
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "Creating .env file from template..."
    cp .env.example .env
    echo "⚠️  Please configure your .env file before starting the system"
    echo "   Required settings:"
    echo "   - OPENAI_API_KEY or other AI model API keys"
    echo "   - Database connection settings"
    echo "   - Redis connection settings"
    echo ""
    read -p "Press Enter to continue after configuring .env file..."
fi

echo "✓ Environment configuration checked"

# Check database connectivity
echo "Testing database connection..."
python3 -c "
from config.agent_config import config
from tools.agent_tools import AGENT_TOOLS
try:
    result = AGENT_TOOLS['database_query']._run('SELECT 1')
    if result['success']:
        print('✓ Database connection successful')
    else:
        print('✗ Database connection failed:', result.get('error'))
        exit(1)
except Exception as e:
    print('✗ Database connection error:', str(e))
    exit(1)
"

# Check Redis connectivity
echo "Testing Redis connection..."
python3 -c "
from config.agent_config import config
import redis
try:
    client = redis.Redis.from_url(config.redis_url)
    client.ping()
    print('✓ Redis connection successful')
except Exception as e:
    print('✗ Redis connection error:', str(e))
    exit(1)
"

echo ""
echo "==================================="
echo "Setup completed successfully!"
echo "==================================="
echo ""
echo "To start the AI agent system:"
echo "  1. Activate virtual environment: source venv/bin/activate"
echo "  2. Start the system: python main.py"
echo ""
echo "Or use the start script: ./start.sh"
echo ""

# Make start script executable
chmod +x start.sh

echo "System is ready to start!"