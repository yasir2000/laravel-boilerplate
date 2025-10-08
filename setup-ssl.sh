#!/bin/bash
# SSL/TLS Certificate Setup Script
# Supports both Let's Encrypt and custom certificates

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔒 SSL/TLS Certificate Setup${NC}"
echo -e "${BLUE}=============================${NC}"
echo ""

# Create SSL directory structure
SSL_DIR="/opt/ssl"
CERTS_DIR="$SSL_DIR/certs"
PRIVATE_DIR="$SSL_DIR/private"
CSR_DIR="$SSL_DIR/csr"

echo -e "${BLUE}📁 Creating SSL directory structure...${NC}"
sudo mkdir -p "$CERTS_DIR" "$PRIVATE_DIR" "$CSR_DIR"
sudo chmod 755 "$SSL_DIR" "$CERTS_DIR" "$CSR_DIR"
sudo chmod 700 "$PRIVATE_DIR"

echo -e "${GREEN}✅ SSL directories created${NC}"

# Certificate options
echo ""
echo -e "${YELLOW}📋 SSL Certificate Options:${NC}"
echo "1. Let's Encrypt (Free, automated)"
echo "2. Custom Certificate (Self-signed or CA-signed)"
echo "3. Both (Let's Encrypt for external, self-signed for internal)"
echo ""

read -p "Select certificate option (1-3): " cert_choice

case $cert_choice in
    1|3)
        echo ""
        echo -e "${BLUE}🔧 Configuring Let's Encrypt Certificate${NC}"
        echo ""
        
        # Check if certbot is installed
        if ! command -v certbot &> /dev/null; then
            echo -e "${YELLOW}📦 Installing Certbot...${NC}"
            
            # Detect package manager and install certbot
            if command -v apt-get &> /dev/null; then
                sudo apt-get update
                sudo apt-get install -y certbot python3-certbot-nginx snapd
                sudo snap install core; sudo snap refresh core
                sudo snap install --classic certbot
                sudo ln -sf /snap/bin/certbot /usr/bin/certbot
            elif command -v yum &> /dev/null; then
                sudo yum install -y certbot python3-certbot-nginx
            elif command -v dnf &> /dev/null; then
                sudo dnf install -y certbot python3-certbot-nginx
            else
                echo -e "${RED}❌ Unable to detect package manager. Please install certbot manually.${NC}"
                exit 1
            fi
            
            echo -e "${GREEN}✅ Certbot installed${NC}"
        fi
        
        read -p "Enter your domain name (e.g., erp-integration.yourcompany.com): " domain_name
        read -p "Enter your email address for certificate notifications: " email_address
        
        # Choose between staging and production
        echo ""
        echo "Certificate environment:"
        echo "1. Production (real certificate)"
        echo "2. Staging (test certificate)"
        
        read -p "Select environment (1-2): " env_choice
        
        staging_flag=""
        if [ "$env_choice" = "2" ]; then
            staging_flag="--staging"
            echo -e "${YELLOW}⚠️  Using staging environment (test certificate)${NC}"
        fi
        
        # Generate Let's Encrypt certificate
        echo -e "${BLUE}🔐 Generating Let's Encrypt certificate...${NC}"
        
        # Check if running with nginx or standalone
        echo "Certificate generation method:"
        echo "1. Standalone (if no web server is running)"
        echo "2. Nginx (if nginx is already running)"
        echo "3. Manual DNS challenge"
        
        read -p "Select method (1-3): " method_choice
        
        case $method_choice in
            1)
                sudo certbot certonly --standalone \
                    --non-interactive \
                    --agree-tos \
                    --email "$email_address" \
                    -d "$domain_name" \
                    $staging_flag
                ;;
            2)
                sudo certbot --nginx \
                    --non-interactive \
                    --agree-tos \
                    --email "$email_address" \
                    -d "$domain_name" \
                    $staging_flag
                ;;
            3)
                sudo certbot certonly --manual \
                    --preferred-challenges dns \
                    --email "$email_address" \
                    -d "$domain_name" \
                    $staging_flag
                ;;
        esac
        
        # Copy certificates to our SSL directory
        if [ -d "/etc/letsencrypt/live/$domain_name" ]; then
            sudo cp "/etc/letsencrypt/live/$domain_name/fullchain.pem" "$CERTS_DIR/${domain_name}.crt"
            sudo cp "/etc/letsencrypt/live/$domain_name/privkey.pem" "$PRIVATE_DIR/${domain_name}.key"
            sudo cp "/etc/letsencrypt/live/$domain_name/chain.pem" "$CERTS_DIR/${domain_name}-chain.crt"
            
            echo -e "${GREEN}✅ Let's Encrypt certificate configured${NC}"
            
            # Create renewal script
            cat > /tmp/renew-certificates.sh << 'EOF'
#!/bin/bash
# Automatic certificate renewal script

echo "Renewing certificates..."
sudo certbot renew --quiet

# Copy renewed certificates
for domain in /etc/letsencrypt/live/*/; do
    domain_name=$(basename "$domain")
    if [ -f "$domain/fullchain.pem" ]; then
        sudo cp "$domain/fullchain.pem" "/opt/ssl/certs/${domain_name}.crt"
        sudo cp "$domain/privkey.pem" "/opt/ssl/private/${domain_name}.key"
        sudo cp "$domain/chain.pem" "/opt/ssl/certs/${domain_name}-chain.crt"
    fi
done

# Restart services
sudo systemctl reload nginx 2>/dev/null || true
docker-compose restart integration-service 2>/dev/null || true

echo "Certificate renewal completed"
EOF
            
            sudo mv /tmp/renew-certificates.sh /opt/ssl/renew-certificates.sh
            sudo chmod +x /opt/ssl/renew-certificates.sh
            
            # Add to crontab for automatic renewal
            (sudo crontab -l 2>/dev/null; echo "0 3 * * * /opt/ssl/renew-certificates.sh") | sudo crontab -
            
            echo -e "${GREEN}✅ Automatic renewal configured${NC}"
        else
            echo -e "${RED}❌ Certificate generation failed${NC}"
            exit 1
        fi
        ;;
esac

case $cert_choice in
    2|3)
        echo ""
        echo -e "${BLUE}🔧 Configuring Custom Certificate${NC}"
        echo ""
        
        echo "Custom certificate options:"
        echo "1. Generate self-signed certificate"
        echo "2. Use existing certificate files"
        echo "3. Generate Certificate Signing Request (CSR) for CA"
        
        read -p "Select option (1-3): " custom_choice
        
        read -p "Enter certificate common name (domain): " cert_domain
        
        case $custom_choice in
            1)
                echo -e "${BLUE}🔐 Generating self-signed certificate...${NC}"
                
                # Generate private key
                sudo openssl genrsa -out "$PRIVATE_DIR/${cert_domain}.key" 2048
                
                # Generate certificate
                sudo openssl req -new -x509 -key "$PRIVATE_DIR/${cert_domain}.key" \
                    -out "$CERTS_DIR/${cert_domain}.crt" -days 365 \
                    -subj "/C=US/ST=State/L=City/O=Organization/CN=${cert_domain}"
                
                echo -e "${GREEN}✅ Self-signed certificate generated${NC}"
                ;;
                
            2)
                echo "Please provide the paths to your certificate files:"
                read -p "Certificate file path (.crt or .pem): " cert_file
                read -p "Private key file path (.key): " key_file
                read -p "Certificate chain file path (optional): " chain_file
                
                if [ -f "$cert_file" ] && [ -f "$key_file" ]; then
                    sudo cp "$cert_file" "$CERTS_DIR/${cert_domain}.crt"
                    sudo cp "$key_file" "$PRIVATE_DIR/${cert_domain}.key"
                    
                    if [ -f "$chain_file" ]; then
                        sudo cp "$chain_file" "$CERTS_DIR/${cert_domain}-chain.crt"
                    fi
                    
                    echo -e "${GREEN}✅ Custom certificate files copied${NC}"
                else
                    echo -e "${RED}❌ Certificate files not found${NC}"
                    exit 1
                fi
                ;;
                
            3)
                echo -e "${BLUE}🔐 Generating Certificate Signing Request...${NC}"
                
                # Generate private key
                sudo openssl genrsa -out "$PRIVATE_DIR/${cert_domain}.key" 2048
                
                # Collect certificate information
                read -p "Country (2 letter code): " country
                read -p "State/Province: " state
                read -p "City/Locality: " city
                read -p "Organization: " organization
                read -p "Organizational Unit: " org_unit
                read -p "Email: " email
                
                # Generate CSR
                sudo openssl req -new -key "$PRIVATE_DIR/${cert_domain}.key" \
                    -out "$CSR_DIR/${cert_domain}.csr" \
                    -subj "/C=${country}/ST=${state}/L=${city}/O=${organization}/OU=${org_unit}/CN=${cert_domain}/emailAddress=${email}"
                
                echo -e "${GREEN}✅ Certificate Signing Request generated${NC}"
                echo -e "${YELLOW}📋 CSR file location: $CSR_DIR/${cert_domain}.csr${NC}"
                echo -e "${YELLOW}📋 Submit this CSR to your Certificate Authority${NC}"
                echo -e "${YELLOW}📋 Once you receive the certificate, place it in: $CERTS_DIR/${cert_domain}.crt${NC}"
                ;;
        esac
        ;;
esac

# Create Java keystore for Spring Boot
if [ -f "$CERTS_DIR/${domain_name:-$cert_domain}.crt" ] && [ -f "$PRIVATE_DIR/${domain_name:-$cert_domain}.key" ]; then
    echo ""
    echo -e "${BLUE}🔐 Creating Java Keystore for Spring Boot...${NC}"
    
    cert_name="${domain_name:-$cert_domain}"
    keystore_password=$(openssl rand -base64 32)
    
    # Convert to PKCS12 format
    sudo openssl pkcs12 -export -in "$CERTS_DIR/${cert_name}.crt" \
        -inkey "$PRIVATE_DIR/${cert_name}.key" \
        -out "$SSL_DIR/keystore.p12" \
        -name "erp-integration" \
        -password "pass:${keystore_password}"
    
    echo -e "${GREEN}✅ Java keystore created${NC}"
    echo -e "${YELLOW}🔑 Keystore password: ${keystore_password}${NC}"
    
    # Update environment file with keystore settings
    if [ -f ".env.production" ]; then
        sed -i "s|SSL_KEYSTORE_PATH=.*|SSL_KEYSTORE_PATH=/opt/ssl/keystore.p12|g" .env.production
        sed -i "s|SSL_KEYSTORE_PASSWORD=.*|SSL_KEYSTORE_PASSWORD=${keystore_password}|g" .env.production
        echo -e "${GREEN}✅ Environment file updated with SSL settings${NC}"
    fi
fi

# Set proper permissions
sudo chown -R root:ssl-cert "$SSL_DIR" 2>/dev/null || sudo chown -R root:root "$SSL_DIR"
sudo chmod -R 644 "$CERTS_DIR"
sudo chmod -R 600 "$PRIVATE_DIR"
sudo chmod -R 644 "$CSR_DIR"

# Create SSL configuration for Docker Compose
cat > microservices/ssl-config.yml << EOF
# SSL Configuration for Docker Compose
# Include this file in your docker-compose override

version: '3.8'

services:
  integration-service:
    environment:
      - SSL_ENABLED=true
      - SSL_KEYSTORE_PATH=/opt/ssl/keystore.p12
      - SSL_KEYSTORE_PASSWORD=${keystore_password:-your-keystore-password}
      - SSL_KEYSTORE_TYPE=PKCS12
      - SSL_KEY_ALIAS=erp-integration
      - SERVER_PORT=8443
    volumes:
      - /opt/ssl:/opt/ssl:ro
    ports:
      - "8443:8443"

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - /opt/ssl/certs:/etc/ssl/certs:ro
      - /opt/ssl/private:/etc/ssl/private:ro
    depends_on:
      - integration-service
EOF

# Create SSL test script
cat > test-ssl.sh << 'EOF'
#!/bin/bash
# SSL Certificate Test Script

echo "🔒 Testing SSL Certificate Configuration"
echo "======================================"

# Test certificate validity
for cert in /opt/ssl/certs/*.crt; do
    if [ -f "$cert" ]; then
        echo ""
        echo "Testing certificate: $(basename "$cert")"
        
        # Check certificate details
        openssl x509 -in "$cert" -text -noout | grep -E "(Subject:|Issuer:|Not Before:|Not After:)"
        
        # Check certificate expiry
        expiry_date=$(openssl x509 -in "$cert" -noout -enddate | cut -d= -f2)
        echo "Expires: $expiry_date"
        
        # Check if certificate is about to expire (within 30 days)
        if openssl x509 -checkend 2592000 -noout -in "$cert"; then
            echo "✅ Certificate is valid for at least 30 days"
        else
            echo "⚠️  Certificate expires within 30 days"
        fi
    fi
done

# Test SSL connection (if service is running)
if curl -k -s https://localhost:8443/ &>/dev/null; then
    echo ""
    echo "✅ SSL service is accessible on port 8443"
else
    echo ""
    echo "ℹ️  SSL service not running or not accessible on port 8443"
fi

echo ""
echo "🔒 SSL test completed"
EOF

chmod +x test-ssl.sh

echo ""
echo -e "${GREEN}✅ SSL/TLS Configuration Complete!${NC}"
echo ""
echo -e "${BLUE}📋 Summary:${NC}"
echo "- SSL certificates configured in: $SSL_DIR"
echo "- Certificate files: $CERTS_DIR"
echo "- Private keys: $PRIVATE_DIR"
echo "- CSR files: $CSR_DIR"

if [ "$cert_choice" = "1" ] || [ "$cert_choice" = "3" ]; then
    echo "- Let's Encrypt automatic renewal configured"
fi

echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo "1. Test SSL configuration: ./test-ssl.sh"
echo "2. Configure Nginx reverse proxy"
echo "3. Update application configuration for HTTPS"
echo "4. Test certificate renewal (if using Let's Encrypt)"
echo ""
echo -e "${YELLOW}⚠️  Important Security Notes:${NC}"
echo "- Private keys are stored in $PRIVATE_DIR with restricted permissions"
echo "- Keystore password saved to environment file"
echo "- Regular certificate renewal is configured"
echo ""

echo -e "${GREEN}🔒 SSL/TLS setup completed successfully!${NC}"