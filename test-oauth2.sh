#!/bin/bash
# OAuth2 Authentication Test Script

echo "🔐 Testing OAuth2 Authentication"
echo "==============================="

BASE_URL="http://localhost:8083"
OAUTH2_URL="http://localhost:9000"

# Test 1: Check OAuth2 endpoints
echo ""
echo "1. Testing OAuth2 endpoints..."

if curl -s "${OAUTH2_URL}/.well-known/openid_configuration" &>/dev/null; then
    echo "✅ OAuth2 discovery endpoint accessible"
else
    echo "⚠️  OAuth2 discovery endpoint not accessible"
fi

# Test 2: Authentication flow
echo ""
echo "2. Testing authentication flow..."

# Try to access protected endpoint without authentication
if curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/api/erp/status" | grep -q "401"; then
    echo "✅ Protected endpoint correctly returns 401 without authentication"
else
    echo "⚠️  Protected endpoint authentication not working as expected"
fi

# Test 3: JWT token validation
echo ""
echo "3. Testing JWT token validation..."

# Login and get token
if command -v jq &> /dev/null; then
    TOKEN=$(curl -s -X POST "${BASE_URL}/api/auth/login" \
        -H "Content-Type: application/json" \
        -d '{"username":"admin","password":"admin123"}' | jq -r '.token')
    
    if [ "$TOKEN" != "null" ] && [ "$TOKEN" != "" ]; then
        echo "✅ JWT token obtained successfully"
        
        # Test protected endpoint with token
        if curl -s -H "Authorization: Bearer $TOKEN" "${BASE_URL}/api/auth/userinfo" &>/dev/null; then
            echo "✅ Protected endpoint accessible with valid token"
        else
            echo "⚠️  Protected endpoint not accessible with token"
        fi
    else
        echo "⚠️  Failed to obtain JWT token"
    fi
else
    echo "ℹ️  jq not available, skipping JWT test"
fi

echo ""
echo "🔐 OAuth2 test completed"
