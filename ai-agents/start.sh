#!/bin/bash

# CrewAI Agent System Start Script
# Starts the AI agent system with proper environment setup

set -e

echo "Starting CrewAI Agent System..."

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo "Virtual environment not found. Please run setup.sh first."
    exit 1
fi

# Activate virtual environment
source venv/bin/activate

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "Error: .env file not found. Please run setup.sh first."
    exit 1
fi

# Create logs directory if it doesn't exist
mkdir -p logs

# Start the application
echo "Launching AI agent system..."
echo "Server will be available at: http://localhost:8001"
echo "Health check endpoint: http://localhost:8001/health"
echo "API documentation: http://localhost:8001/docs"
echo ""
echo "Press Ctrl+C to stop the system"
echo ""

# Run the main application
python main.py