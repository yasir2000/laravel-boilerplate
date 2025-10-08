# 🔒 SECURITY CREDENTIALS VAULT
## ERP Integration System - Production Keys

**⚠️ CRITICAL SECURITY NOTICE:**
This file contains sensitive security credentials. Store this information securely and restrict access to authorized personnel only.

---

## 🔐 BACKUP ENCRYPTION KEY

**Purpose**: AES-256 encryption for backup files
**Algorithm**: AES-256-CBC with PBKDF2
**Generated**: 2025-10-08 17:44:23

```
BACKUP_ENCRYPTION_KEY=rY9u02SKXiP94F0SPbKzCarAxAlcQVcFQ3M4wf2IFtY=
```

**Storage Requirements:**
- ✅ Store in encrypted password manager
- ✅ Keep offline backup copy in secure location
- ✅ Never store with backup files
- ✅ Rotate key every 6 months
- ✅ Document key rotation dates

---

## 🔑 OAuth2 AUTHENTICATION CREDENTIALS

**Client Configuration:**
```
OAUTH2_CLIENT_ID=asoath
OAUTH2_CLIENT_SECRET=9f+ecPBt+JVJXiWv5jzTi0dw8EogrI817l//8wrTLr4=
OAUTH2_ISSUER_URL=https://erp-integration.yourcompany.com
```

**JWT Signing Keys Location:**
```
Private Key: microservices/oauth2/keys/jwt-private-pkcs8.pem
Public Key:  microservices/oauth2/keys/jwt-public.pem
```

---

## 🗄️ DATABASE CREDENTIALS

**PostgreSQL Production Database:**
```
DB_HOST=postgres (container) / localhost:5433 (external)
DB_DATABASE=erp_integration
DB_USERNAME=erp_user
DB_PASSWORD=[Set in .env.production]
```

---

## 🌐 ERP SYSTEM CREDENTIALS

**Frappe/ERPNext Integration:**
```
FRAPPE_URL=https://2 (needs correction)
FRAPPE_API_KEY=akkjjjsiiaas
FRAPPE_API_SECRET=18282jajjs
FRAPPE_COMPANY=frappe
```

---

## 📋 KEY ROTATION SCHEDULE

| Key Type | Current Date | Next Rotation | Status |
|----------|-------------|---------------|---------|
| Backup Encryption | 2025-10-08 | 2026-04-08 | ✅ Active |
| OAuth2 Client Secret | 2025-10-08 | 2026-01-08 | ✅ Active |
| JWT Signing Keys | 2025-10-08 | 2026-01-08 | ✅ Active |
| Database Password | [Not Set] | [Schedule] | ⚠️ Pending |

---

## 🔒 SECURITY BEST PRACTICES

### Immediate Actions Required:
1. **Store this file in encrypted password manager** (LastPass, 1Password, etc.)
2. **Delete this file from the repository** after secure storage
3. **Create offline backup** of encryption keys
4. **Set up key rotation reminders**

### Access Control:
- ✅ Limit access to DevOps/Security team only
- ✅ Use role-based access control
- ✅ Log all key access activities
- ✅ Regular access review

### Key Management:
- ✅ Never commit keys to version control
- ✅ Use environment variables for production
- ✅ Implement key rotation procedures
- ✅ Monitor for key compromise

---

## 🚨 INCIDENT RESPONSE

**If keys are compromised:**
1. **Immediately rotate all affected keys**
2. **Review access logs for unauthorized usage**
3. **Re-encrypt all backups with new keys**
4. **Update all systems with new credentials**
5. **Document incident and lessons learned**

**Emergency Contacts:**
- Security Team: [Add contact information]
- DevOps Lead: [Add contact information]
- System Administrator: [Add contact information]

---

## 📊 VERIFICATION COMMANDS

**Test backup encryption:**
```bash
# Test encryption
echo "test data" | openssl enc -aes-256-cbc -salt -pbkdf2 -k "rY9u02SKXiP94F0SPbKzCarAxAlcQVcFQ3M4wf2IFtY="

# Test decryption
echo "encrypted_data" | openssl enc -aes-256-cbc -d -pbkdf2 -k "rY9u02SKXiP94F0SPbKzCarAxAlcQVcFQ3M4wf2IFtY="
```

**Verify OAuth2 keys:**
```bash
# Verify JWT private key
openssl rsa -in microservices/oauth2/keys/jwt-private-pkcs8.pem -check

# Verify JWT public key
openssl rsa -in microservices/oauth2/keys/jwt-private-pkcs8.pem -pubout
```

---

**Document Version**: 1.0
**Last Updated**: 2025-10-08 17:51:00
**Next Review**: 2025-11-08

**⚠️ REMEMBER: Delete this file after storing credentials securely!