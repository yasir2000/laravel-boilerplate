# User Management Service

## 🎯 Service Overview

The User Management Service is responsible for user authentication, authorization, and identity management across the HR microservices ecosystem.

### 🏗️ Service Architecture

```mermaid
graph TB
    subgraph "User Service Architecture"
        subgraph "API Layer"
            AuthAPI[Authentication API]
            UserAPI[User Management API]
            PermissionAPI[Permission API]
            SessionAPI[Session Management API]
        end
        
        subgraph "Business Logic"
            AuthService[Authentication Service]
            UserService[User Service]
            PermissionService[Permission Service]
            SessionService[Session Service]
            TokenService[JWT Token Service]
        end
        
        subgraph "Data Layer"
            UserRepo[User Repository]
            RoleRepo[Role Repository]
            PermissionRepo[Permission Repository]
            SessionRepo[Session Repository]
        end
        
        subgraph "External Dependencies"
            Database[(PostgreSQL<br/>User Database)]
            Cache[(Redis<br/>Session Cache)]
            Vault[HashiCorp Vault<br/>Secrets Management]
            MFA[MFA Provider<br/>TOTP/SMS]
        end
    end
    
    AuthAPI --> AuthService
    UserAPI --> UserService
    PermissionAPI --> PermissionService
    SessionAPI --> SessionService
    
    AuthService --> TokenService
    AuthService --> UserRepo
    AuthService --> SessionRepo
    
    UserService --> UserRepo
    UserService --> PermissionRepo
    
    PermissionService --> RoleRepo
    PermissionService --> PermissionRepo
    
    SessionService --> SessionRepo
    SessionService --> Cache
    
    UserRepo --> Database
    RoleRepo --> Database
    PermissionRepo --> Database
    SessionRepo --> Cache
    
    TokenService --> Vault
    AuthService --> MFA
    
    classDef api fill:#e3f2fd
    classDef service fill:#f3e5f5
    classDef repo fill:#e8f5e8
    classDef external fill:#fff3e0
    
    class AuthAPI,UserAPI,PermissionAPI,SessionAPI api
    class AuthService,UserService,PermissionService,SessionService,TokenService service
    class UserRepo,RoleRepo,PermissionRepo,SessionRepo repo
    class Database,Cache,Vault,MFA external
```

## 📊 Database Schema

```sql
-- Users table
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    is_active BOOLEAN DEFAULT true,
    email_verified_at TIMESTAMP,
    phone_verified_at TIMESTAMP,
    last_login_at TIMESTAMP,
    failed_login_attempts INTEGER DEFAULT 0,
    locked_until TIMESTAMP,
    password_changed_at TIMESTAMP DEFAULT NOW(),
    mfa_enabled BOOLEAN DEFAULT false,
    mfa_secret VARCHAR(32),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Roles table
CREATE TABLE roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    is_system_role BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Permissions table
CREATE TABLE permissions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    description TEXT,
    resource VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- User roles junction table
CREATE TABLE user_roles (
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    role_id UUID REFERENCES roles(id) ON DELETE CASCADE,
    assigned_by UUID REFERENCES users(id),
    assigned_at TIMESTAMP DEFAULT NOW(),
    expires_at TIMESTAMP,
    PRIMARY KEY (user_id, role_id)
);

-- Role permissions junction table
CREATE TABLE role_permissions (
    role_id UUID REFERENCES roles(id) ON DELETE CASCADE,
    permission_id UUID REFERENCES permissions(id) ON DELETE CASCADE,
    PRIMARY KEY (role_id, permission_id)
);

-- User sessions table
CREATE TABLE user_sessions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    refresh_token VARCHAR(255) UNIQUE,
    ip_address INET,
    user_agent TEXT,
    device_fingerprint VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    expires_at TIMESTAMP NOT NULL,
    last_activity_at TIMESTAMP DEFAULT NOW(),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Audit log for user actions
CREATE TABLE user_audit_log (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    resource VARCHAR(100),
    resource_id VARCHAR(255),
    ip_address INET,
    user_agent TEXT,
    details JSONB,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_active ON users(is_active);
CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_user_sessions_active ON user_sessions(is_active, expires_at);
CREATE INDEX idx_user_audit_log_user_id ON user_audit_log(user_id);
CREATE INDEX idx_user_audit_log_created_at ON user_audit_log(created_at);
```

## 🔐 API Endpoints

### Authentication Endpoints

```yaml
# Authentication API
/api/v1/auth:
  post:
    /login:
      summary: Authenticate user with email/password
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                email:
                  type: string
                  format: email
                password:
                  type: string
                remember_me:
                  type: boolean
                  default: false
                device_name:
                  type: string
      responses:
        200:
          description: Authentication successful
          content:
            application/json:
              schema:
                type: object
                properties:
                  access_token:
                    type: string
                  refresh_token:
                    type: string
                  token_type:
                    type: string
                    default: Bearer
                  expires_in:
                    type: integer
                  user:
                    $ref: '#/components/schemas/User'
        401:
          description: Invalid credentials
        423:
          description: Account locked
    
    /logout:
      summary: Logout user and invalidate tokens
      security:
        - bearerAuth: []
      responses:
        200:
          description: Logout successful
    
    /refresh:
      summary: Refresh access token
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                refresh_token:
                  type: string
      responses:
        200:
          description: Token refreshed
          content:
            application/json:
              schema:
                type: object
                properties:
                  access_token:
                    type: string
                  expires_in:
                    type: integer
    
    /mfa/setup:
      summary: Setup multi-factor authentication
      security:
        - bearerAuth: []
      responses:
        200:
          description: MFA setup information
          content:
            application/json:
              schema:
                type: object
                properties:
                  qr_code:
                    type: string
                  secret:
                    type: string
                  backup_codes:
                    type: array
                    items:
                      type: string
    
    /mfa/verify:
      summary: Verify MFA token
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                token:
                  type: string
                  pattern: '^[0-9]{6}$'
      responses:
        200:
          description: MFA verification successful
        400:
          description: Invalid MFA token
```

### User Management Endpoints

```yaml
# User Management API
/api/v1/users:
  get:
    summary: List users with pagination and filtering
    security:
      - bearerAuth: []
    parameters:
      - name: page
        in: query
        schema:
          type: integer
          default: 1
      - name: limit
        in: query
        schema:
          type: integer
          default: 20
          maximum: 100
      - name: search
        in: query
        schema:
          type: string
      - name: role
        in: query
        schema:
          type: string
      - name: status
        in: query
        schema:
          type: string
          enum: [active, inactive, locked]
    responses:
      200:
        description: List of users
        content:
          application/json:
            schema:
              type: object
              properties:
                data:
                  type: array
                  items:
                    $ref: '#/components/schemas/User'
                pagination:
                  $ref: '#/components/schemas/Pagination'
  
  post:
    summary: Create new user
    security:
      - bearerAuth: []
    requestBody:
      required: true
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/CreateUserRequest'
    responses:
      201:
        description: User created successfully
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/User'
      400:
        description: Validation error
      409:
        description: Email already exists

/api/v1/users/{id}:
  get:
    summary: Get user by ID
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: string
          format: uuid
    responses:
      200:
        description: User details
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/User'
      404:
        description: User not found
  
  put:
    summary: Update user
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: string
          format: uuid
    requestBody:
      required: true
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/UpdateUserRequest'
    responses:
      200:
        description: User updated successfully
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/User'
      404:
        description: User not found
  
  delete:
    summary: Deactivate user
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: string
          format: uuid
    responses:
      204:
        description: User deactivated successfully
      404:
        description: User not found

/api/v1/users/{id}/roles:
  get:
    summary: Get user roles
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: string
          format: uuid
    responses:
      200:
        description: User roles
        content:
          application/json:
            schema:
              type: array
              items:
                $ref: '#/components/schemas/Role'
  
  post:
    summary: Assign role to user
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: string
          format: uuid
    requestBody:
      required: true
      content:
        application/json:
          schema:
            type: object
            properties:
              role_id:
                type: string
                format: uuid
              expires_at:
                type: string
                format: date-time
    responses:
      200:
        description: Role assigned successfully
      404:
        description: User or role not found
  
  delete:
    summary: Remove role from user
    security:
      - bearerAuth: []
    parameters:
      - name: id
        in: path
        required: true
        schema:
          type: string
          format: uuid
      - name: role_id
        in: query
        required: true
        schema:
          type: string
          format: uuid
    responses:
      204:
        description: Role removed successfully
      404:
        description: User or role not found
```

## 🐳 Docker Configuration

### Dockerfile
```dockerfile
# Node.js User Service
FROM node:18-alpine AS builder

WORKDIR /app

# Copy package files
COPY package*.json ./
RUN npm ci --only=production

# Copy source code
COPY . .

# Build the application
RUN npm run build

# Production stage
FROM node:18-alpine AS production

WORKDIR /app

# Install dumb-init for signal handling
RUN apk add --no-cache dumb-init

# Create non-root user
RUN addgroup -g 1001 -S nodejs
RUN adduser -S userservice -u 1001

# Copy built application
COPY --from=builder --chown=userservice:nodejs /app/dist ./dist
COPY --from=builder --chown=userservice:nodejs /app/node_modules ./node_modules
COPY --from=builder --chown=userservice:nodejs /app/package.json ./package.json

# Switch to non-root user
USER userservice

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD node dist/health-check.js

# Expose port
EXPOSE 3001

# Start the application
ENTRYPOINT ["dumb-init", "--"]
CMD ["node", "dist/index.js"]
```

### Docker Compose Service
```yaml
# docker-compose.microservices.yml
services:
  user-service:
    build:
      context: ./microservices/services/user-service
      dockerfile: Dockerfile
    container_name: hr_user_service
    ports:
      - "3001:3001"
    environment:
      - NODE_ENV=development
      - PORT=3001
      - DATABASE_URL=postgresql://postgres:password@user-db:5432/user_service
      - REDIS_URL=redis://redis-cluster:6379
      - JWT_SECRET_KEY=your-secret-key
      - VAULT_URL=http://vault:8200
      - VAULT_TOKEN=your-vault-token
      - MFA_ISSUER=HR_System
    depends_on:
      - user-db
      - redis-cluster
      - vault
    volumes:
      - ./logs/user-service:/app/logs
    restart: unless-stopped
    networks:
      - microservices-network
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:3001/health"]
      interval: 30s
      timeout: 10s
      retries: 3

  user-db:
    image: postgres:15-alpine
    container_name: hr_user_db
    environment:
      - POSTGRES_DB=user_service
      - POSTGRES_USER=postgres
      - POSTGRES_PASSWORD=password
    volumes:
      - user_db_data:/var/lib/postgresql/data
      - ./microservices/services/user-service/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql
    ports:
      - "5432:5432"
    restart: unless-stopped
    networks:
      - microservices-network

volumes:
  user_db_data:

networks:
  microservices-network:
    driver: bridge
```

## 🚀 Implementation Example (Node.js/TypeScript)

### Service Structure
```
user-service/
├── src/
│   ├── controllers/
│   │   ├── auth.controller.ts
│   │   ├── user.controller.ts
│   │   └── permission.controller.ts
│   ├── services/
│   │   ├── auth.service.ts
│   │   ├── user.service.ts
│   │   ├── token.service.ts
│   │   └── mfa.service.ts
│   ├── repositories/
│   │   ├── user.repository.ts
│   │   ├── role.repository.ts
│   │   └── session.repository.ts
│   ├── models/
│   │   ├── user.model.ts
│   │   ├── role.model.ts
│   │   └── session.model.ts
│   ├── middleware/
│   │   ├── auth.middleware.ts
│   │   ├── rate-limit.middleware.ts
│   │   └── validation.middleware.ts
│   ├── utils/
│   │   ├── password.util.ts
│   │   ├── jwt.util.ts
│   │   └── crypto.util.ts
│   ├── config/
│   │   ├── database.ts
│   │   ├── redis.ts
│   │   └── vault.ts
│   └── app.ts
├── tests/
├── package.json
├── tsconfig.json
└── Dockerfile
```

### Core Service Implementation
```typescript
// src/services/auth.service.ts
import { User } from '../models/user.model';
import { UserRepository } from '../repositories/user.repository';
import { SessionRepository } from '../repositories/session.repository';
import { TokenService } from './token.service';
import { PasswordUtil } from '../utils/password.util';
import { EventPublisher } from '../events/event-publisher';

export class AuthService {
  constructor(
    private userRepository: UserRepository,
    private sessionRepository: SessionRepository,
    private tokenService: TokenService,
    private eventPublisher: EventPublisher
  ) {}

  async authenticate(
    email: string, 
    password: string, 
    deviceInfo: DeviceInfo
  ): Promise<AuthResult> {
    // Find user by email
    const user = await this.userRepository.findByEmail(email);
    if (!user) {
      await this.recordFailedLogin(email, deviceInfo.ipAddress);
      throw new AuthenticationError('Invalid credentials');
    }

    // Check if account is locked
    if (user.isLocked()) {
      throw new AccountLockedError('Account is temporarily locked');
    }

    // Verify password
    const isValid = await PasswordUtil.verify(password, user.passwordHash);
    if (!isValid) {
      await this.recordFailedLogin(email, deviceInfo.ipAddress);
      await this.incrementFailedAttempts(user);
      throw new AuthenticationError('Invalid credentials');
    }

    // Check if MFA is required
    if (user.mfaEnabled) {
      // Return partial auth result for MFA verification
      return {
        requiresMfa: true,
        mfaToken: await this.tokenService.generateMfaToken(user.id),
        user: user.toSafeObject()
      };
    }

    // Generate tokens
    const tokens = await this.tokenService.generateTokens(user);

    // Create session
    await this.sessionRepository.create({
      userId: user.id,
      sessionToken: tokens.accessToken,
      refreshToken: tokens.refreshToken,
      ipAddress: deviceInfo.ipAddress,
      userAgent: deviceInfo.userAgent,
      expiresAt: tokens.expiresAt
    });

    // Reset failed attempts
    await this.userRepository.resetFailedAttempts(user.id);

    // Update last login
    await this.userRepository.updateLastLogin(user.id);

    // Publish login event
    await this.eventPublisher.publish('user.login', {
      userId: user.id,
      email: user.email,
      ipAddress: deviceInfo.ipAddress,
      timestamp: new Date()
    });

    return {
      accessToken: tokens.accessToken,
      refreshToken: tokens.refreshToken,
      expiresIn: tokens.expiresIn,
      user: user.toSafeObject()
    };
  }

  async verifyMfa(
    mfaToken: string, 
    otpCode: string, 
    deviceInfo: DeviceInfo
  ): Promise<AuthResult> {
    // Verify MFA token
    const payload = await this.tokenService.verifyMfaToken(mfaToken);
    const user = await this.userRepository.findById(payload.userId);
    
    if (!user) {
      throw new AuthenticationError('Invalid MFA token');
    }

    // Verify OTP code
    const isValidOtp = await MfaUtil.verifyTotp(otpCode, user.mfaSecret);
    if (!isValidOtp) {
      throw new AuthenticationError('Invalid MFA code');
    }

    // Generate final tokens
    const tokens = await this.tokenService.generateTokens(user);

    // Create session
    await this.sessionRepository.create({
      userId: user.id,
      sessionToken: tokens.accessToken,
      refreshToken: tokens.refreshToken,
      ipAddress: deviceInfo.ipAddress,
      userAgent: deviceInfo.userAgent,
      expiresAt: tokens.expiresAt
    });

    // Publish MFA verification event
    await this.eventPublisher.publish('user.mfa_verified', {
      userId: user.id,
      email: user.email,
      ipAddress: deviceInfo.ipAddress,
      timestamp: new Date()
    });

    return {
      accessToken: tokens.accessToken,
      refreshToken: tokens.refreshToken,
      expiresIn: tokens.expiresIn,
      user: user.toSafeObject()
    };
  }

  async logout(sessionToken: string): Promise<void> {
    const session = await this.sessionRepository.findByToken(sessionToken);
    if (session) {
      await this.sessionRepository.invalidate(session.id);
      
      // Publish logout event
      await this.eventPublisher.publish('user.logout', {
        userId: session.userId,
        sessionId: session.id,
        timestamp: new Date()
      });
    }
  }

  private async recordFailedLogin(email: string, ipAddress: string): Promise<void> {
    // Record failed login attempt for security monitoring
    await this.eventPublisher.publish('user.failed_login', {
      email,
      ipAddress,
      timestamp: new Date()
    });
  }

  private async incrementFailedAttempts(user: User): Promise<void> {
    const attempts = await this.userRepository.incrementFailedAttempts(user.id);
    
    // Lock account after 5 failed attempts
    if (attempts >= 5) {
      const lockUntil = new Date(Date.now() + 30 * 60 * 1000); // 30 minutes
      await this.userRepository.lockAccount(user.id, lockUntil);
      
      await this.eventPublisher.publish('user.account_locked', {
        userId: user.id,
        email: user.email,
        lockUntil,
        reason: 'too_many_failed_attempts',
        timestamp: new Date()
      });
    }
  }
}
```

## 🔄 Event Publishing

```typescript
// src/events/user-events.ts
export interface UserEvent {
  eventId: string;
  eventType: string;
  timestamp: Date;
  version: string;
  source: string;
  data: any;
}

export class UserEventPublisher {
  constructor(private kafkaProducer: KafkaProducer) {}

  async publishUserCreated(user: User): Promise<void> {
    const event: UserEvent = {
      eventId: generateUuid(),
      eventType: 'user.created',
      timestamp: new Date(),
      version: '1.0',
      source: 'user-service',
      data: {
        userId: user.id,
        email: user.email,
        firstName: user.firstName,
        lastName: user.lastName,
        isActive: user.isActive,
        roles: user.roles?.map(r => ({ id: r.id, name: r.name })) || []
      }
    };

    await this.kafkaProducer.send({
      topic: 'user-events',
      messages: [{
        key: user.id,
        value: JSON.stringify(event),
        headers: {
          'event-type': event.eventType,
          'content-type': 'application/json'
        }
      }]
    });
  }

  async publishUserUpdated(user: User, changes: Partial<User>): Promise<void> {
    const event: UserEvent = {
      eventId: generateUuid(),
      eventType: 'user.updated',
      timestamp: new Date(),
      version: '1.0',
      source: 'user-service',
      data: {
        userId: user.id,
        email: user.email,
        changes: changes,
        updatedAt: new Date()
      }
    };

    await this.kafkaProducer.send({
      topic: 'user-events',
      messages: [{
        key: user.id,
        value: JSON.stringify(event)
      }]
    });
  }
}
```

---

**Next**: [Employee Service](../employee-service/README.md) | [API Gateway](../../infrastructure/api-gateway.md)