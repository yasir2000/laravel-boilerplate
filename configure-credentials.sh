#!/bin/bash
# ERP Credential Configuration Script
# This script helps configure actual ERP system credentials securely

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔐 ERP Integration Credential Configuration${NC}"
echo -e "${BLUE}===========================================${NC}"
echo ""

# Check if .env.production exists
if [ ! -f ".env.production" ]; then
    echo -e "${RED}❌ .env.production file not found!${NC}"
    echo "Please ensure the production environment file exists."
    exit 1
fi

# Create a backup
cp .env.production .env.production.backup
echo -e "${GREEN}✅ Created backup: .env.production.backup${NC}"

echo ""
echo -e "${YELLOW}📋 ERP System Configuration Options:${NC}"
echo "1. Frappe/ERPNext (Cloud or Self-hosted)"
echo "2. SAP ERP"
echo "3. Oracle ERP Cloud"
echo "4. Microsoft Dynamics 365"
echo "5. Generic REST API ERP"
echo "6. Multiple ERP Systems"
echo ""

read -p "Select your primary ERP system (1-6): " erp_choice

case $erp_choice in
    1)
        echo ""
        echo -e "${BLUE}🔧 Configuring Frappe/ERPNext Integration${NC}"
        echo ""
        
        echo "Frappe/ERPNext deployment options:"
        echo "1. Frappe Cloud (https://yourcompany.frappe.cloud)"
        echo "2. Self-hosted ERPNext"
        echo "3. ERPNext.com SaaS"
        
        read -p "Select deployment type (1-3): " frappe_type
        
        case $frappe_type in
            1)
                read -p "Enter your Frappe Cloud URL (e.g., yourcompany.frappe.cloud): " frappe_url
                frappe_base_url="https://${frappe_url}"
                ;;
            2)
                read -p "Enter your self-hosted ERPNext URL (e.g., erp.yourcompany.com): " frappe_url
                frappe_base_url="https://${frappe_url}"
                ;;
            3)
                read -p "Enter your ERPNext.com URL (e.g., yourcompany.erpnext.com): " frappe_url
                frappe_base_url="https://${frappe_url}"
                ;;
        esac
        
        read -p "Enter Frappe API Key: " frappe_api_key
        read -s -p "Enter Frappe API Secret: " frappe_api_secret
        echo ""
        read -p "Enter Company Name in Frappe: " frappe_company
        
        # Update the configuration file
        sed -i "s|FRAPPE_ENABLED=true|FRAPPE_ENABLED=true|g" .env.production
        sed -i "s|FRAPPE_BASE_URL=.*|FRAPPE_BASE_URL=${frappe_base_url}|g" .env.production
        sed -i "s|FRAPPE_API_KEY=.*|FRAPPE_API_KEY=${frappe_api_key}|g" .env.production
        sed -i "s|FRAPPE_API_SECRET=.*|FRAPPE_API_SECRET=${frappe_api_secret}|g" .env.production
        sed -i "s|FRAPPE_COMPANY=.*|FRAPPE_COMPANY=${frappe_company}|g" .env.production
        
        echo -e "${GREEN}✅ Frappe/ERPNext credentials configured${NC}"
        ;;
        
    2)
        echo ""
        echo -e "${BLUE}🔧 Configuring SAP ERP Integration${NC}"
        echo ""
        
        read -p "Enter SAP Base URL (e.g., https://sap-system.com:8000/sap/bc/rest/api): " sap_url
        read -p "Enter SAP Client (e.g., 100): " sap_client
        read -p "Enter SAP Username: " sap_username
        read -s -p "Enter SAP Password: " sap_password
        echo ""
        read -p "Enter SAP System ID (e.g., PRD): " sap_system_id
        
        # Update the configuration file
        sed -i "s|SAP_ENABLED=false|SAP_ENABLED=true|g" .env.production
        sed -i "s|SAP_BASE_URL=.*|SAP_BASE_URL=${sap_url}|g" .env.production
        sed -i "s|SAP_CLIENT=.*|SAP_CLIENT=${sap_client}|g" .env.production
        sed -i "s|SAP_USERNAME=.*|SAP_USERNAME=${sap_username}|g" .env.production
        sed -i "s|SAP_PASSWORD=.*|SAP_PASSWORD=${sap_password}|g" .env.production
        sed -i "s|SAP_SYSTEM_ID=.*|SAP_SYSTEM_ID=${sap_system_id}|g" .env.production
        
        echo -e "${GREEN}✅ SAP ERP credentials configured${NC}"
        ;;
        
    3)
        echo ""
        echo -e "${BLUE}🔧 Configuring Oracle ERP Cloud Integration${NC}"
        echo ""
        
        read -p "Enter Oracle Instance URL: " oracle_url
        read -p "Enter Oracle Username: " oracle_username
        read -s -p "Enter Oracle Password: " oracle_password
        echo ""
        read -p "Enter Oracle Client ID: " oracle_client_id
        read -s -p "Enter Oracle Client Secret: " oracle_client_secret
        echo ""
        
        # Update the configuration file
        sed -i "s|ORACLE_ENABLED=false|ORACLE_ENABLED=true|g" .env.production
        sed -i "s|ORACLE_BASE_URL=.*|ORACLE_BASE_URL=${oracle_url}|g" .env.production
        sed -i "s|ORACLE_USERNAME=.*|ORACLE_USERNAME=${oracle_username}|g" .env.production
        sed -i "s|ORACLE_PASSWORD=.*|ORACLE_PASSWORD=${oracle_password}|g" .env.production
        sed -i "s|ORACLE_CLIENT_ID=.*|ORACLE_CLIENT_ID=${oracle_client_id}|g" .env.production
        sed -i "s|ORACLE_CLIENT_SECRET=.*|ORACLE_CLIENT_SECRET=${oracle_client_secret}|g" .env.production
        
        echo -e "${GREEN}✅ Oracle ERP Cloud credentials configured${NC}"
        ;;
        
    4)
        echo ""
        echo -e "${BLUE}🔧 Configuring Microsoft Dynamics 365 Integration${NC}"
        echo ""
        
        read -p "Enter Dynamics 365 URL: " dynamics_url
        read -p "Enter Azure Tenant ID: " dynamics_tenant_id
        read -p "Enter Dynamics Client ID: " dynamics_client_id
        read -s -p "Enter Dynamics Client Secret: " dynamics_client_secret
        echo ""
        
        # Update the configuration file
        sed -i "s|DYNAMICS_ENABLED=false|DYNAMICS_ENABLED=true|g" .env.production
        sed -i "s|DYNAMICS_BASE_URL=.*|DYNAMICS_BASE_URL=${dynamics_url}|g" .env.production
        sed -i "s|DYNAMICS_TENANT_ID=.*|DYNAMICS_TENANT_ID=${dynamics_tenant_id}|g" .env.production
        sed -i "s|DYNAMICS_CLIENT_ID=.*|DYNAMICS_CLIENT_ID=${dynamics_client_id}|g" .env.production
        sed -i "s|DYNAMICS_CLIENT_SECRET=.*|DYNAMICS_CLIENT_SECRET=${dynamics_client_secret}|g" .env.production
        
        echo -e "${GREEN}✅ Microsoft Dynamics 365 credentials configured${NC}"
        ;;
        
    5)
        echo ""
        echo -e "${BLUE}🔧 Configuring Generic REST API ERP Integration${NC}"
        echo ""
        
        read -p "Enter ERP API Base URL: " generic_url
        echo "Authentication types:"
        echo "1. Bearer Token"
        echo "2. Basic Authentication"
        echo "3. API Key"
        
        read -p "Select authentication type (1-3): " auth_type
        
        case $auth_type in
            1)
                auth_type_value="bearer"
                read -s -p "Enter Bearer Token: " generic_token
                echo ""
                ;;
            2)
                auth_type_value="basic"
                read -p "Enter Username: " generic_username
                read -s -p "Enter Password: " generic_password
                echo ""
                ;;
            3)
                auth_type_value="api-key"
                read -s -p "Enter API Key: " generic_token
                echo ""
                ;;
        esac
        
        # Update the configuration file
        sed -i "s|GENERIC_ERP_ENABLED=false|GENERIC_ERP_ENABLED=true|g" .env.production
        sed -i "s|GENERIC_ERP_BASE_URL=.*|GENERIC_ERP_BASE_URL=${generic_url}|g" .env.production
        sed -i "s|GENERIC_ERP_AUTH_TYPE=.*|GENERIC_ERP_AUTH_TYPE=${auth_type_value}|g" .env.production
        
        if [ "$auth_type_value" = "basic" ]; then
            sed -i "s|GENERIC_ERP_USERNAME=.*|GENERIC_ERP_USERNAME=${generic_username}|g" .env.production
            sed -i "s|GENERIC_ERP_PASSWORD=.*|GENERIC_ERP_PASSWORD=${generic_password}|g" .env.production
        else
            sed -i "s|GENERIC_ERP_TOKEN=.*|GENERIC_ERP_TOKEN=${generic_token}|g" .env.production
        fi
        
        echo -e "${GREEN}✅ Generic REST API ERP credentials configured${NC}"
        ;;
        
    6)
        echo ""
        echo -e "${BLUE}🔧 Multiple ERP Systems Configuration${NC}"
        echo -e "${YELLOW}⚠️  You can configure multiple ERP systems by editing .env.production manually${NC}"
        echo "Enable multiple systems by setting their respective ENABLED flags to true"
        ;;
esac

# Configure common settings
echo ""
echo -e "${BLUE}🔧 Configuring Common Settings${NC}"
echo ""

read -p "Enter your organization name: " org_name
read -p "Enter your organization code (e.g., ACME): " org_code
read -p "Enter your Laravel HR system URL (e.g., https://hr.yourcompany.com): " laravel_url

# Generate secure passwords
echo ""
echo -e "${BLUE}🔐 Generating Secure Passwords${NC}"
db_password=$(openssl rand -base64 32)
rabbitmq_password=$(openssl rand -base64 32)
redis_password=$(openssl rand -base64 32)
jwt_secret=$(openssl rand -base64 64)
grafana_password=$(openssl rand -base64 16)

# Update common settings
sed -i "s|ORGANIZATION_NAME=.*|ORGANIZATION_NAME=\"${org_name}\"|g" .env.production
sed -i "s|ORGANIZATION_CODE=.*|ORGANIZATION_CODE=\"${org_code}\"|g" .env.production
sed -i "s|LARAVEL_BASE_URL=.*|LARAVEL_BASE_URL=${laravel_url}|g" .env.production
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${db_password}|g" .env.production
sed -i "s|RABBITMQ_PASSWORD=.*|RABBITMQ_PASSWORD=${rabbitmq_password}|g" .env.production
sed -i "s|REDIS_PASSWORD=.*|REDIS_PASSWORD=${redis_password}|g" .env.production
sed -i "s|JWT_SECRET=.*|JWT_SECRET=${jwt_secret}|g" .env.production
sed -i "s|GRAFANA_ADMIN_PASSWORD=.*|GRAFANA_ADMIN_PASSWORD=${grafana_password}|g" .env.production

echo ""
echo -e "${GREEN}✅ Generated secure passwords for:${NC}"
echo "   - Database: ${db_password}"
echo "   - RabbitMQ: ${rabbitmq_password}"
echo "   - Redis: ${redis_password}"
echo "   - Grafana: ${grafana_password}"
echo ""
echo -e "${YELLOW}⚠️  Please save these passwords securely!${NC}"

# Configure alerts
echo ""
read -p "Configure email alerts? (y/n): " configure_email
if [ "$configure_email" = "y" ]; then
    read -p "Enter SMTP host: " smtp_host
    read -p "Enter SMTP port (587): " smtp_port
    smtp_port=${smtp_port:-587}
    read -p "Enter SMTP username: " smtp_username
    read -s -p "Enter SMTP password: " smtp_password
    echo ""
    read -p "Enter alert recipient emails (comma-separated): " alert_emails
    
    sed -i "s|ALERT_EMAIL_SMTP_HOST=.*|ALERT_EMAIL_SMTP_HOST=${smtp_host}|g" .env.production
    sed -i "s|ALERT_EMAIL_SMTP_PORT=.*|ALERT_EMAIL_SMTP_PORT=${smtp_port}|g" .env.production
    sed -i "s|ALERT_EMAIL_SMTP_USERNAME=.*|ALERT_EMAIL_SMTP_USERNAME=${smtp_username}|g" .env.production
    sed -i "s|ALERT_EMAIL_SMTP_PASSWORD=.*|ALERT_EMAIL_SMTP_PASSWORD=${smtp_password}|g" .env.production
    sed -i "s|ALERT_EMAIL_TO=.*|ALERT_EMAIL_TO=${alert_emails}|g" .env.production
    
    echo -e "${GREEN}✅ Email alerts configured${NC}"
fi

read -p "Configure Slack alerts? (y/n): " configure_slack
if [ "$configure_slack" = "y" ]; then
    read -p "Enter Slack webhook URL: " slack_webhook
    read -p "Enter Slack channel (e.g., #erp-alerts): " slack_channel
    
    sed -i "s|ALERT_SLACK_ENABLED=.*|ALERT_SLACK_ENABLED=true|g" .env.production
    sed -i "s|ALERT_SLACK_WEBHOOK_URL=.*|ALERT_SLACK_WEBHOOK_URL=${slack_webhook}|g" .env.production
    sed -i "s|ALERT_SLACK_CHANNEL=.*|ALERT_SLACK_CHANNEL=${slack_channel}|g" .env.production
    
    echo -e "${GREEN}✅ Slack alerts configured${NC}"
fi

# Set secure file permissions
chmod 600 .env.production
echo -e "${GREEN}✅ Set secure file permissions (600) on .env.production${NC}"

# Generate credential summary
cat > credential-summary.txt << EOF
ERP Integration Credentials Summary
Generated: $(date)
=====================================

Organization: ${org_name}
Organization Code: ${org_code}

Generated Passwords:
- Database: ${db_password}
- RabbitMQ: ${rabbitmq_password}
- Redis: ${redis_password}
- Grafana: ${grafana_password}

JWT Secret: ${jwt_secret}

⚠️  SECURITY NOTICE:
- Store this file securely
- Delete after passwords are saved in password manager
- Never commit to version control
EOF

chmod 600 credential-summary.txt

echo ""
echo -e "${GREEN}✅ Configuration Complete!${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo "1. Review the generated .env.production file"
echo "2. Save the credentials from credential-summary.txt securely"
echo "3. Configure SSL/TLS certificates"
echo "4. Set up OAuth2 authentication"
echo "5. Configure Nginx reverse proxy"
echo ""
echo -e "${YELLOW}⚠️  Important Security Notes:${NC}"
echo "- .env.production contains sensitive credentials"
echo "- File permissions set to 600 (owner read/write only)"
echo "- Never commit this file to version control"
echo "- Backup is available at .env.production.backup"
echo ""

echo -e "${GREEN}🎉 ERP credentials configuration completed successfully!${NC}"