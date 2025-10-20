#!/usr/bin/env php
<?php

// Simple test script for AI Agents API endpoints
$baseUrl = 'http://127.0.0.1:8000';

echo "🤖 Testing AI Agents API Endpoints\n";
echo "================================\n\n";

// Test public health endpoint
echo "1. Testing public health endpoint...\n";
$response = file_get_contents($baseUrl . '/api/health');
if ($response) {
    $data = json_decode($response, true);
    echo "✅ Health endpoint working: " . $data['status'] . "\n";
} else {
    echo "❌ Health endpoint failed\n";
}

echo "\n";

// Test AI Agents endpoints (these require authentication, so we'll test if they respond properly)
$endpoints = [
    '/api/ai-agents/agents/status' => 'Agents Status',
    '/api/ai-agents/system/health' => 'System Health',
    '/api/ai-agents/workflows/active' => 'Active Workflows',
    '/api/ai-agents/activity/feed' => 'Activity Feed'
];

echo "2. Testing AI Agents API endpoints (should return 401 without auth)...\n";

foreach ($endpoints as $path => $name) {
    echo "Testing $name: ";
    
    // Use curl to test endpoints and capture HTTP status
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 401) {
        echo "✅ Properly protected (401 Unauthorized)\n";
    } elseif ($httpCode == 200) {
        echo "⚠️  Accessible without auth (200 OK)\n";
    } else {
        echo "❌ Unexpected status: $httpCode\n";
    }
}

echo "\n";

// Test the dashboard route
echo "3. Testing AI Agents dashboard route...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/ai-agents');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 302) {
    echo "✅ Dashboard route exists (redirects to login: 302)\n";
} elseif ($httpCode == 200) {
    echo "✅ Dashboard route accessible (200 OK)\n";
} else {
    echo "❌ Dashboard route issue: HTTP $httpCode\n";
}

echo "\n";

// Test if frontend files exist
echo "4. Checking frontend files...\n";
$frontendFiles = [
    'resources/views/ai-agents/dashboard.blade.php' => 'Dashboard Blade Template',
    'public/js/agents/AgentsDashboard.js' => 'Main Dashboard JS',
    'public/js/agents/AgentsController.js' => 'Controller JS',
    'public/js/agents/AgentsModel.js' => 'Model JS',
    'public/js/agents/WorkflowStartDialog.js' => 'Workflow Dialog JS',
    'public/css/agents-dashboard.css' => 'Dashboard CSS'
];

$basePath = dirname(__FILE__);
foreach ($frontendFiles as $file => $name) {
    $fullPath = $basePath . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✅ $name exists\n";
    } else {
        echo "❌ $name missing: $fullPath\n";
    }
}

echo "\n";

// Test if backend files exist
echo "5. Checking backend files...\n";
$backendFiles = [
    'app/Http/Controllers/API/AIAgentsController.php' => 'API Controller',
    'app/Services/AIAgentService.php' => 'Agent Service',
    'routes/api.php' => 'API Routes'
];

foreach ($backendFiles as $file => $name) {
    $fullPath = $basePath . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✅ $name exists\n";
    } else {
        echo "❌ $name missing: $fullPath\n";
    }
}

echo "\n";
echo "🎉 Test completed!\n";
echo "📝 Next steps:\n";
echo "   1. Log in to the application\n";
echo "   2. Navigate to /ai-agents to see the dashboard\n";
echo "   3. Set up authentication tokens to test protected endpoints\n";
echo "   4. Start the Python AI agents service for full functionality\n";