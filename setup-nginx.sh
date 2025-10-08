#!/bin/bash
# Nginx Reverse Proxy Configuration Script
# Sets up production-grade Nginx reverse proxy with SSL, load balancing, and security

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🌐 Nginx Reverse Proxy Configuration${NC}"
echo -e "${BLUE}===================================${NC}"
echo ""

# Create Nginx directory structure
NGINX_DIR="microservices/nginx"
CONF_DIR="$NGINX_DIR/conf"
SSL_DIR="$NGINX_DIR/ssl"
LOGS_DIR="$NGINX_DIR/logs"

echo -e "${BLUE}📁 Creating Nginx directory structure...${NC}"
mkdir -p "$CONF_DIR" "$SSL_DIR" "$LOGS_DIR"
chmod 755 "$NGINX_DIR" "$CONF_DIR" "$LOGS_DIR"
chmod 700 "$SSL_DIR"

echo -e "${GREEN}✅ Nginx directories created${NC}"

# Domain configuration
echo ""
echo -e "${YELLOW}🌐 Domain Configuration:${NC}"
read -p "Enter your domain name (e.g., erp-integration.yourcompany.com): " domain_name
domain_name=${domain_name:-erp-integration.yourcompany.com}

read -p "Enter additional domains (comma-separated, optional): " additional_domains

# Service ports configuration
echo ""
echo -e "${YELLOW}🔧 Service Ports Configuration:${NC}"
echo "Default service ports:"
echo "- HR Integration Service: 8083"
echo "- OAuth2 Server: 9000"
echo "- Monitoring (Prometheus): 9090"
echo "- Monitoring (Grafana): 3000"
echo ""

read -p "HR Integration Service port (default: 8083): " hr_port
hr_port=${hr_port:-8083}

read -p "OAuth2 Server port (default: 9000): " oauth_port
oauth_port=${oauth_port:-9000}

read -p "Enable monitoring endpoints? (y/n): " enable_monitoring
enable_monitoring=${enable_monitoring:-y}

# SSL Configuration
echo ""
echo -e "${YELLOW}🔐 SSL Configuration:${NC}"
echo "1. Use Let's Encrypt certificates"
echo "2. Use existing SSL certificates"
echo "3. Generate self-signed certificates (development only)"
echo ""

read -p "Select SSL option (1-3): " ssl_choice

case $ssl_choice in
    1)
        ssl_type="letsencrypt"
        cert_path="/etc/letsencrypt/live/${domain_name}/fullchain.pem"
        key_path="/etc/letsencrypt/live/${domain_name}/privkey.pem"
        ;;
    2)
        ssl_type="existing"
        read -p "Enter path to SSL certificate: " cert_path
        read -p "Enter path to SSL private key: " key_path
        ;;
    3)
        ssl_type="selfsigned"
        cert_path="/etc/nginx/ssl/${domain_name}.crt"
        key_path="/etc/nginx/ssl/${domain_name}.key"
        
        # Generate self-signed certificate
        echo -e "${BLUE}🔐 Generating self-signed SSL certificate...${NC}"
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout "$SSL_DIR/${domain_name}.key" \
            -out "$SSL_DIR/${domain_name}.crt" \
            -subj "/C=US/ST=State/L=City/O=Organization/CN=${domain_name}"
        echo -e "${GREEN}✅ Self-signed certificate generated${NC}"
        ;;
esac

# Create main Nginx configuration
cat > "$CONF_DIR/nginx.conf" << EOF
# Main Nginx Configuration
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

# Worker connections optimization
events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    # Basic settings
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    
    # Logging format
    log_format main '\$remote_addr - \$remote_user [\$time_local] "\$request" '
                   '\$status \$body_bytes_sent "\$http_referer" '
                   '"\$http_user_agent" "\$http_x_forwarded_for" '
                   'rt=\$request_time uct="\$upstream_connect_time" '
                   'uht="\$upstream_header_time" urt="\$upstream_response_time"';
    
    access_log /var/log/nginx/access.log main;
    
    # Performance optimizations
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    client_max_body_size 100M;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 10240;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/x-javascript
        application/xml+rss
        application/javascript
        application/json;
    
    # Rate limiting
    limit_req_zone \$binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone \$binary_remote_addr zone=login:10m rate=1r/s;
    
    # Upstream definitions
    upstream hr_integration {
        least_conn;
        server integration-service:${hr_port} max_fails=3 fail_timeout=30s;
        keepalive 32;
    }
    
    upstream oauth2_server {
        least_conn;
        server oauth2-server:${oauth_port} max_fails=3 fail_timeout=30s;
        keepalive 16;
    }
EOF

if [ "$enable_monitoring" = "y" ]; then
    cat >> "$CONF_DIR/nginx.conf" << EOF
    
    upstream prometheus {
        server prometheus:9090;
    }
    
    upstream grafana {
        server grafana:3000;
    }
EOF
fi

cat >> "$CONF_DIR/nginx.conf" << EOF
    
    # Security headers
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none';" always;
    
    # Include site configurations
    include /etc/nginx/conf.d/*.conf;
}
EOF

# Create site-specific configuration
cat > "$CONF_DIR/default.conf" << EOF
# HTTP to HTTPS redirect
server {
    listen 80;
    server_name ${domain_name}$(if [ -n "$additional_domains" ]; then echo " $additional_domains"; fi);
    
    # Let's Encrypt challenge
    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }
    
    # Redirect everything else to HTTPS
    location / {
        return 301 https://\$server_name\$request_uri;
    }
}

# Main HTTPS server
server {
    listen 443 ssl http2;
    server_name ${domain_name}$(if [ -n "$additional_domains" ]; then echo " $additional_domains"; fi);
    
    # SSL Configuration
    ssl_certificate ${cert_path};
    ssl_certificate_key ${key_path};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;
    
    # OCSP stapling
    ssl_stapling on;
    ssl_stapling_verify on;
    
    # Security headers
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    
    # Main application
    location / {
        proxy_pass http://hr_integration;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header X-Forwarded-Host \$server_name;
        
        # Timeouts
        proxy_connect_timeout 30s;
        proxy_send_timeout 30s;
        proxy_read_timeout 30s;
        
        # Buffering
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
        
        # Rate limiting
        limit_req zone=api burst=20 nodelay;
    }
    
    # API endpoints
    location /api/ {
        proxy_pass http://hr_integration;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        # API-specific timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Rate limiting for API
        limit_req zone=api burst=10 nodelay;
        
        # CORS headers
        add_header Access-Control-Allow-Origin "*";
        add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
        add_header Access-Control-Allow-Headers "Authorization, Content-Type, X-Requested-With";
        
        if (\$request_method = 'OPTIONS') {
            return 204;
        }
    }
    
    # OAuth2 endpoints
    location /oauth2/ {
        proxy_pass http://oauth2_server;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        # Rate limiting for auth
        limit_req zone=login burst=5 nodelay;
    }
    
    # Login endpoints
    location /login/ {
        proxy_pass http://oauth2_server;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        # Rate limiting for login
        limit_req zone=login burst=3 nodelay;
    }
    
    # WebSocket support for real-time updates
    location /ws/ {
        proxy_pass http://hr_integration;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        # WebSocket timeouts
        proxy_read_timeout 86400;
    }
    
    # Health check endpoint
    location /nginx-health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
    
    # Static files caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)\$ {
        proxy_pass http://hr_integration;
        expires 1y;
        add_header Cache-Control "public, no-transform";
        add_header Vary Accept-Encoding;
    }
EOF

if [ "$enable_monitoring" = "y" ]; then
    cat >> "$CONF_DIR/default.conf" << EOF
    
    # Monitoring endpoints (protected)
    location /prometheus/ {
        auth_basic "Monitoring Access";
        auth_basic_user_file /etc/nginx/.htpasswd;
        
        proxy_pass http://prometheus/;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
    
    location /grafana/ {
        auth_basic "Monitoring Access";
        auth_basic_user_file /etc/nginx/.htpasswd;
        
        proxy_pass http://grafana/;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
EOF
fi

cat >> "$CONF_DIR/default.conf" << EOF
}
EOF

# Create monitoring basic auth file
if [ "$enable_monitoring" = "y" ]; then
    echo ""
    read -p "Enter monitoring username (default: admin): " monitoring_user
    monitoring_user=${monitoring_user:-admin}
    
    read -s -p "Enter monitoring password: " monitoring_pass
    echo ""
    
    # Generate htpasswd entry
    monitoring_hash=$(openssl passwd -apr1 "$monitoring_pass")
    echo "${monitoring_user}:${monitoring_hash}" > "$CONF_DIR/.htpasswd"
    
    echo -e "${GREEN}✅ Monitoring authentication configured${NC}"
fi

# Create Docker Compose configuration for Nginx
cat > "nginx-docker-compose.yml" << EOF
# Nginx Reverse Proxy Docker Compose Configuration
version: '3.8'

services:
  nginx:
    image: nginx:1.24-alpine
    container_name: nginx-proxy
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./microservices/nginx/conf/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./microservices/nginx/conf/default.conf:/etc/nginx/conf.d/default.conf:ro
EOF

if [ "$enable_monitoring" = "y" ]; then
    cat >> "nginx-docker-compose.yml" << EOF
      - ./microservices/nginx/conf/.htpasswd:/etc/nginx/.htpasswd:ro
EOF
fi

case $ssl_type in
    "letsencrypt")
        cat >> "nginx-docker-compose.yml" << EOF
      - /etc/letsencrypt:/etc/letsencrypt:ro
      - ./microservices/nginx/ssl:/var/www/certbot:ro
EOF
        ;;
    "existing")
        cat >> "nginx-docker-compose.yml" << EOF
      - ${cert_path}:${cert_path}:ro
      - ${key_path}:${key_path}:ro
EOF
        ;;
    "selfsigned")
        cat >> "nginx-docker-compose.yml" << EOF
      - ./microservices/nginx/ssl:/etc/nginx/ssl:ro
EOF
        ;;
esac

cat >> "nginx-docker-compose.yml" << EOF
      - ./microservices/nginx/logs:/var/log/nginx
    networks:
      - integration-network
    depends_on:
      - integration-service
EOF

if [ "$oauth_choice" = "1" ]; then
    cat >> "nginx-docker-compose.yml" << EOF
      - oauth2-server
EOF
fi

cat >> "nginx-docker-compose.yml" << EOF
    restart: unless-stopped
    labels:
      - "traefik.enable=false"
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/nginx-health"]
      interval: 30s
      timeout: 10s
      retries: 3

networks:
  integration-network:
    external: true
EOF

# Create Nginx management script
cat > "manage-nginx.sh" << 'EOF'
#!/bin/bash
# Nginx Management Script

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Functions
start_nginx() {
    echo -e "${BLUE}🚀 Starting Nginx...${NC}"
    docker-compose -f nginx-docker-compose.yml up -d
    echo -e "${GREEN}✅ Nginx started${NC}"
}

stop_nginx() {
    echo -e "${BLUE}🛑 Stopping Nginx...${NC}"
    docker-compose -f nginx-docker-compose.yml down
    echo -e "${GREEN}✅ Nginx stopped${NC}"
}

restart_nginx() {
    echo -e "${BLUE}🔄 Restarting Nginx...${NC}"
    docker-compose -f nginx-docker-compose.yml restart
    echo -e "${GREEN}✅ Nginx restarted${NC}"
}

reload_config() {
    echo -e "${BLUE}🔄 Reloading Nginx configuration...${NC}"
    docker-compose -f nginx-docker-compose.yml exec nginx nginx -s reload
    echo -e "${GREEN}✅ Configuration reloaded${NC}"
}

test_config() {
    echo -e "${BLUE}🧪 Testing Nginx configuration...${NC}"
    docker-compose -f nginx-docker-compose.yml exec nginx nginx -t
}

view_logs() {
    echo -e "${BLUE}📋 Nginx logs:${NC}"
    docker-compose -f nginx-docker-compose.yml logs -f nginx
}

status() {
    echo -e "${BLUE}📊 Nginx status:${NC}"
    docker-compose -f nginx-docker-compose.yml ps nginx
    
    echo ""
    echo -e "${BLUE}🔍 Health check:${NC}"
    if curl -s http://localhost/nginx-health &>/dev/null; then
        echo -e "${GREEN}✅ Nginx is healthy${NC}"
    else
        echo -e "${RED}❌ Nginx health check failed${NC}"
    fi
}

# Main menu
case $1 in
    start)
        start_nginx
        ;;
    stop)
        stop_nginx
        ;;
    restart)
        restart_nginx
        ;;
    reload)
        reload_config
        ;;
    test)
        test_config
        ;;
    logs)
        view_logs
        ;;
    status)
        status
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|reload|test|logs|status}"
        echo ""
        echo "Commands:"
        echo "  start   - Start Nginx container"
        echo "  stop    - Stop Nginx container"
        echo "  restart - Restart Nginx container"
        echo "  reload  - Reload Nginx configuration"
        echo "  test    - Test Nginx configuration"
        echo "  logs    - View Nginx logs"
        echo "  status  - Show Nginx status"
        exit 1
        ;;
esac
EOF

chmod +x manage-nginx.sh

# Create Let's Encrypt renewal script (if using Let's Encrypt)
if [ "$ssl_type" = "letsencrypt" ]; then
    cat > "renew-certificates.sh" << EOF
#!/bin/bash
# Let's Encrypt Certificate Renewal Script

set -e

echo "🔐 Renewing Let's Encrypt certificates..."

# Stop Nginx temporarily
docker-compose -f nginx-docker-compose.yml stop nginx

# Renew certificates
docker run --rm \\
    -v /etc/letsencrypt:/etc/letsencrypt \\
    -v ./microservices/nginx/ssl:/var/www/certbot \\
    -p 80:80 \\
    certbot/certbot renew \\
    --webroot \\
    --webroot-path=/var/www/certbot \\
    --quiet

# Start Nginx again
docker-compose -f nginx-docker-compose.yml start nginx

echo "✅ Certificate renewal completed"
EOF

    chmod +x renew-certificates.sh
    
    echo -e "${GREEN}✅ Let's Encrypt renewal script created${NC}"
fi

# Create Nginx test script
cat > "test-nginx.sh" << 'EOF'
#!/bin/bash
# Nginx Reverse Proxy Test Script

echo "🌐 Testing Nginx Reverse Proxy"
echo "=============================="

BASE_URL="https://localhost"
HTTP_URL="http://localhost"

# Test 1: HTTP to HTTPS redirect
echo ""
echo "1. Testing HTTP to HTTPS redirect..."

if curl -s -o /dev/null -w "%{http_code}" "$HTTP_URL" | grep -q "301"; then
    echo "✅ HTTP correctly redirects to HTTPS"
else
    echo "⚠️  HTTP redirect not working as expected"
fi

# Test 2: SSL/HTTPS connectivity
echo ""
echo "2. Testing HTTPS connectivity..."

if curl -k -s -o /dev/null -w "%{http_code}" "$BASE_URL" | grep -q "200\|302"; then
    echo "✅ HTTPS connectivity working"
else
    echo "⚠️  HTTPS connectivity issues"
fi

# Test 3: API proxy
echo ""
echo "3. Testing API proxy..."

if curl -k -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/health" | grep -q "200\|401"; then
    echo "✅ API proxy working"
else
    echo "⚠️  API proxy issues"
fi

# Test 4: OAuth2 proxy
echo ""
echo "4. Testing OAuth2 proxy..."

if curl -k -s -o /dev/null -w "%{http_code}" "$BASE_URL/oauth2/authorize" | grep -q "200\|302\|401"; then
    echo "✅ OAuth2 proxy working"
else
    echo "⚠️  OAuth2 proxy issues"
fi

# Test 5: Security headers
echo ""
echo "5. Testing security headers..."

HEADERS=$(curl -k -s -I "$BASE_URL" | grep -i "x-frame-options\|x-content-type-options\|strict-transport-security")

if [ -n "$HEADERS" ]; then
    echo "✅ Security headers present"
    echo "$HEADERS"
else
    echo "⚠️  Security headers missing"
fi

# Test 6: Rate limiting
echo ""
echo "6. Testing rate limiting..."

# Make multiple requests quickly
for i in {1..15}; do
    curl -k -s -o /dev/null -w "%{http_code}\n" "$BASE_URL/api/test" &
done
wait

echo "ℹ️  Rate limiting test completed (check for 429 responses)"

echo ""
echo "🌐 Nginx reverse proxy test completed"
EOF

chmod +x test-nginx.sh

# Update main docker-compose.yml to include Nginx network
if [ -f "docker-compose.yml" ]; then
    if ! grep -q "integration-network" docker-compose.yml; then
        cat >> docker-compose.yml << EOF

networks:
  integration-network:
    driver: bridge
EOF
    fi
fi

echo ""
echo -e "${GREEN}✅ Nginx Reverse Proxy Configuration Complete!${NC}"
echo ""
echo -e "${BLUE}📋 Summary:${NC}"
echo "- Domain: $domain_name"
echo "- SSL Type: $ssl_type"
echo "- HR Service Port: $hr_port"
echo "- OAuth2 Port: $oauth_port"
echo "- Monitoring Enabled: $enable_monitoring"
echo ""
echo -e "${BLUE}📋 Generated Files:${NC}"
echo "- nginx-docker-compose.yml - Docker Compose configuration"
echo "- manage-nginx.sh - Nginx management script"
echo "- test-nginx.sh - Nginx testing script"

if [ "$ssl_type" = "letsencrypt" ]; then
    echo "- renew-certificates.sh - Certificate renewal script"
fi

echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo "1. Start Nginx: ./manage-nginx.sh start"
echo "2. Test configuration: ./test-nginx.sh"
echo "3. Monitor logs: ./manage-nginx.sh logs"

if [ "$ssl_type" = "letsencrypt" ]; then
    echo "4. Set up certificate auto-renewal with cron"
fi

echo ""
echo -e "${YELLOW}⚠️  Important Notes:${NC}"
echo "- Ensure DNS points to this server for $domain_name"
echo "- Configure firewall to allow ports 80 and 443"
echo "- Monitor Nginx logs for any issues"

if [ "$enable_monitoring" = "y" ]; then
    echo "- Monitoring accessible at: https://$domain_name/grafana/"
    echo "- Monitoring username: $monitoring_user"
fi

echo ""
echo -e "${GREEN}🌐 Nginx reverse proxy setup completed successfully!${NC}"