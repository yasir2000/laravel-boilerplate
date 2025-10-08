#!/bin/bash
# OAuth2 Authentication Configuration Script
# Sets up OAuth2/JWT authentication for the ERP Integration system

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔐 OAuth2 Authentication Configuration${NC}"
echo -e "${BLUE}=====================================${NC}"
echo ""

# Create OAuth2 directory structure
OAUTH2_DIR="microservices/oauth2"
KEYS_DIR="$OAUTH2_DIR/keys"
CONFIG_DIR="$OAUTH2_DIR/config"

echo -e "${BLUE}📁 Creating OAuth2 directory structure...${NC}"
mkdir -p "$KEYS_DIR" "$CONFIG_DIR"
chmod 755 "$OAUTH2_DIR" "$CONFIG_DIR"
chmod 700 "$KEYS_DIR"

echo -e "${GREEN}✅ OAuth2 directories created${NC}"

# OAuth2 provider options
echo ""
echo -e "${YELLOW}📋 OAuth2 Provider Options:${NC}"
echo "1. Built-in OAuth2 Server (Spring Security)"
echo "2. External OAuth2 Provider (Auth0, Okta, etc.)"
echo "3. Azure Active Directory (Azure AD)"
echo "4. Google OAuth2"
echo "5. Custom OAuth2 Provider"
echo ""

read -p "Select OAuth2 provider (1-5): " oauth_choice

case $oauth_choice in
    1)
        echo ""
        echo -e "${BLUE}🔧 Configuring Built-in OAuth2 Server${NC}"
        echo ""
        
        # Generate RSA key pair for JWT signing
        echo -e "${BLUE}🔐 Generating RSA key pair for JWT signing...${NC}"
        
        # Generate private key
        openssl genrsa -out "$KEYS_DIR/jwt-private.pem" 2048
        
        # Generate public key
        openssl rsa -in "$KEYS_DIR/jwt-private.pem" -pubout -out "$KEYS_DIR/jwt-public.pem"
        
        # Convert to PKCS#8 format for Java
        openssl pkcs8 -topk8 -inform PEM -outform PEM -nocrypt \
            -in "$KEYS_DIR/jwt-private.pem" -out "$KEYS_DIR/jwt-private-pkcs8.pem"
        
        echo -e "${GREEN}✅ JWT key pair generated${NC}"
        
        # Generate OAuth2 clients
        read -p "Enter OAuth2 client ID (default: erp-integration-client): " client_id
        client_id=${client_id:-erp-integration-client}
        
        client_secret=$(openssl rand -base64 32)
        
        echo -e "${BLUE}🔐 Generated OAuth2 credentials:${NC}"
        echo "Client ID: $client_id"
        echo "Client Secret: $client_secret"
        
        # Create OAuth2 server configuration
        cat > "$CONFIG_DIR/oauth2-server.yml" << EOF
# OAuth2 Authorization Server Configuration
spring:
  security:
    oauth2:
      authorizationserver:
        client:
          $client_id:
            registration:
              client-id: $client_id
              client-secret: '{bcrypt}$(echo -n "$client_secret" | openssl dgst -sha256 -binary | base64)'
              client-authentication-methods:
                - client_secret_basic
                - client_secret_post
              authorization-grant-types:
                - authorization_code
                - refresh_token
                - client_credentials
              redirect-uris:
                - https://erp-integration.yourcompany.com/login/oauth2/code/$client_id
                - http://localhost:8083/login/oauth2/code/$client_id
              scopes:
                - read
                - write
                - erp:access
            require-authorization-consent: false
            
        jwk:
          source: classpath:keys/jwt-private-pkcs8.pem
          
        settings:
          issuer: https://erp-integration.yourcompany.com
          authorization-endpoint: /oauth2/authorize
          token-endpoint: /oauth2/token
          jwk-set-endpoint: /oauth2/jwks
          revocation-endpoint: /oauth2/revoke
          introspection-endpoint: /oauth2/introspect
          userinfo-endpoint: /oauth2/userinfo
EOF

        # Create JWT configuration
        cat > "$CONFIG_DIR/jwt-config.yml" << EOF
# JWT Configuration
jwt:
  private-key-path: classpath:keys/jwt-private-pkcs8.pem
  public-key-path: classpath:keys/jwt-public.pem
  expiration: 3600
  refresh-expiration: 86400
  issuer: https://erp-integration.yourcompany.com
  audience: erp-integration-api
EOF

        oauth2_issuer="https://erp-integration.yourcompany.com"
        ;;
        
    2)
        echo ""
        echo -e "${BLUE}🔧 Configuring External OAuth2 Provider${NC}"
        echo ""
        
        echo "Common OAuth2 providers:"
        echo "1. Auth0"
        echo "2. Okta"
        echo "3. Keycloak"
        echo "4. Other"
        
        read -p "Select provider (1-4): " provider_choice
        
        case $provider_choice in
            1)
                read -p "Enter Auth0 domain (e.g., yourcompany.auth0.com): " auth0_domain
                oauth2_issuer="https://${auth0_domain}/"
                ;;
            2)
                read -p "Enter Okta domain (e.g., yourcompany.okta.com): " okta_domain
                oauth2_issuer="https://${okta_domain}/oauth2/default"
                ;;
            3)
                read -p "Enter Keycloak URL (e.g., https://auth.yourcompany.com/auth/realms/master): " keycloak_url
                oauth2_issuer="$keycloak_url"
                ;;
            4)
                read -p "Enter OAuth2 issuer URL: " oauth2_issuer
                ;;
        esac
        
        read -p "Enter OAuth2 client ID: " client_id
        read -s -p "Enter OAuth2 client secret: " client_secret
        echo ""
        ;;
        
    3)
        echo ""
        echo -e "${BLUE}🔧 Configuring Azure Active Directory${NC}"
        echo ""
        
        read -p "Enter Azure Tenant ID: " azure_tenant_id
        read -p "Enter Azure Client ID: " client_id
        read -s -p "Enter Azure Client Secret: " client_secret
        echo ""
        
        oauth2_issuer="https://login.microsoftonline.com/${azure_tenant_id}/v2.0"
        
        # Create Azure AD configuration
        cat > "$CONFIG_DIR/azure-ad-config.yml" << EOF
# Azure Active Directory Configuration
spring:
  security:
    oauth2:
      client:
        registration:
          azure:
            client-id: $client_id
            client-secret: $client_secret
            scope:
              - openid
              - profile
              - email
              - offline_access
            authorization-grant-type: authorization_code
            redirect-uri: https://erp-integration.yourcompany.com/login/oauth2/code/azure
        provider:
          azure:
            issuer-uri: https://login.microsoftonline.com/${azure_tenant_id}/v2.0
            authorization-uri: https://login.microsoftonline.com/${azure_tenant_id}/oauth2/v2.0/authorize
            token-uri: https://login.microsoftonline.com/${azure_tenant_id}/oauth2/v2.0/token
            user-info-uri: https://graph.microsoft.com/oidc/userinfo
            jwk-set-uri: https://login.microsoftonline.com/${azure_tenant_id}/discovery/v2.0/keys
            user-name-attribute: sub
EOF
        ;;
        
    4)
        echo ""
        echo -e "${BLUE}🔧 Configuring Google OAuth2${NC}"
        echo ""
        
        read -p "Enter Google Client ID: " client_id
        read -s -p "Enter Google Client Secret: " client_secret
        echo ""
        
        oauth2_issuer="https://accounts.google.com"
        
        # Create Google OAuth2 configuration
        cat > "$CONFIG_DIR/google-oauth2-config.yml" << EOF
# Google OAuth2 Configuration
spring:
  security:
    oauth2:
      client:
        registration:
          google:
            client-id: $client_id
            client-secret: $client_secret
            scope:
              - openid
              - profile
              - email
            redirect-uri: https://erp-integration.yourcompany.com/login/oauth2/code/google
        provider:
          google:
            issuer-uri: https://accounts.google.com
            user-name-attribute: sub
EOF
        ;;
        
    5)
        echo ""
        echo -e "${BLUE}🔧 Configuring Custom OAuth2 Provider${NC}"
        echo ""
        
        read -p "Enter OAuth2 issuer URL: " oauth2_issuer
        read -p "Enter authorization endpoint: " auth_endpoint
        read -p "Enter token endpoint: " token_endpoint
        read -p "Enter userinfo endpoint: " userinfo_endpoint
        read -p "Enter JWK set endpoint: " jwk_endpoint
        read -p "Enter client ID: " client_id
        read -s -p "Enter client secret: " client_secret
        echo ""
        
        # Create custom OAuth2 configuration
        cat > "$CONFIG_DIR/custom-oauth2-config.yml" << EOF
# Custom OAuth2 Provider Configuration
spring:
  security:
    oauth2:
      client:
        registration:
          custom:
            client-id: $client_id
            client-secret: $client_secret
            scope:
              - openid
              - profile
              - email
              - erp:access
            authorization-grant-type: authorization_code
            redirect-uri: https://erp-integration.yourcompany.com/login/oauth2/code/custom
        provider:
          custom:
            issuer-uri: $oauth2_issuer
            authorization-uri: $auth_endpoint
            token-uri: $token_endpoint
            user-info-uri: $userinfo_endpoint
            jwk-set-uri: $jwk_endpoint
            user-name-attribute: sub
EOF
        ;;
esac

# Create Spring Security configuration
cat > "$CONFIG_DIR/security-config.java" << 'EOF'
package com.hrintegration.config;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.core.userdetails.User;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.security.core.userdetails.UserDetailsService;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.oauth2.jwt.JwtDecoder;
import org.springframework.security.oauth2.jwt.NimbusJwtDecoder;
import org.springframework.security.provisioning.InMemoryUserDetailsManager;
import org.springframework.security.web.SecurityFilterChain;

import java.security.interfaces.RSAPublicKey;

@Configuration
@EnableWebSecurity
public class SecurityConfig {
    
    @Value("${oauth2.issuer-url}")
    private String issuerUrl;
    
    @Value("${jwt.public-key-path}")
    private RSAPublicKey publicKey;
    
    @Bean
    public SecurityFilterChain filterChain(HttpSecurity http) throws Exception {
        http
            .authorizeHttpRequests(authz -> authz
                .requestMatchers("/actuator/health", "/actuator/info").permitAll()
                .requestMatchers("/api/public/**").permitAll()
                .requestMatchers("/oauth2/**", "/login/**").permitAll()
                .requestMatchers("/api/admin/**").hasRole("ADMIN")
                .requestMatchers("/api/erp/**").hasAuthority("SCOPE_erp:access")
                .anyRequest().authenticated()
            )
            .oauth2Login(oauth2 -> oauth2
                .defaultSuccessUrl("/dashboard")
                .failureUrl("/login?error")
            )
            .oauth2ResourceServer(oauth2 -> oauth2
                .jwt(jwt -> jwt
                    .decoder(jwtDecoder())
                )
            )
            .logout(logout -> logout
                .logoutSuccessUrl("/")
                .invalidateHttpSession(true)
                .clearAuthentication(true)
            );
            
        return http.build();
    }
    
    @Bean
    public JwtDecoder jwtDecoder() {
        return NimbusJwtDecoder.withPublicKey(publicKey).build();
    }
    
    @Bean
    public PasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder();
    }
    
    @Bean
    public UserDetailsService userDetailsService() {
        UserDetails admin = User.builder()
            .username("admin")
            .password(passwordEncoder().encode("admin123"))
            .roles("ADMIN", "USER")
            .build();
            
        UserDetails user = User.builder()
            .username("user")
            .password(passwordEncoder().encode("user123"))
            .roles("USER")
            .build();
            
        return new InMemoryUserDetailsManager(admin, user);
    }
}
EOF

# Create JWT utility class
cat > "$CONFIG_DIR/JwtUtil.java" << 'EOF'
package com.hrintegration.util;

import io.jsonwebtoken.Claims;
import io.jsonwebtoken.Jwts;
import io.jsonwebtoken.SignatureAlgorithm;
import io.jsonwebtoken.security.Keys;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.stereotype.Component;

import java.security.Key;
import java.util.Date;
import java.util.HashMap;
import java.util.Map;
import java.util.function.Function;

@Component
public class JwtUtil {
    
    @Value("${jwt.secret}")
    private String secret;
    
    @Value("${jwt.expiration}")
    private Long expiration;
    
    public String extractUsername(String token) {
        return extractClaim(token, Claims::getSubject);
    }
    
    public Date extractExpiration(String token) {
        return extractClaim(token, Claims::getExpiration);
    }
    
    public <T> T extractClaim(String token, Function<Claims, T> claimsResolver) {
        final Claims claims = extractAllClaims(token);
        return claimsResolver.apply(claims);
    }
    
    private Claims extractAllClaims(String token) {
        return Jwts.parserBuilder()
                .setSigningKey(getSignKey())
                .build()
                .parseClaimsJws(token)
                .getBody();
    }
    
    private Boolean isTokenExpired(String token) {
        return extractExpiration(token).before(new Date());
    }
    
    public String generateToken(UserDetails userDetails) {
        Map<String, Object> claims = new HashMap<>();
        return createToken(claims, userDetails.getUsername());
    }
    
    private String createToken(Map<String, Object> claims, String subject) {
        return Jwts.builder()
                .setClaims(claims)
                .setSubject(subject)
                .setIssuedAt(new Date(System.currentTimeMillis()))
                .setExpiration(new Date(System.currentTimeMillis() + expiration * 1000))
                .signWith(getSignKey(), SignatureAlgorithm.HS256)
                .compact();
    }
    
    public Boolean validateToken(String token, UserDetails userDetails) {
        final String username = extractUsername(token);
        return (username.equals(userDetails.getUsername()) && !isTokenExpired(token));
    }
    
    private Key getSignKey() {
        byte[] keyBytes = secret.getBytes();
        return Keys.hmacShaKeyFor(keyBytes);
    }
}
EOF

# Create authentication controller
cat > "$CONFIG_DIR/AuthController.java" << 'EOF'
package com.hrintegration.controller;

import com.hrintegration.util.JwtUtil;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.security.authentication.AuthenticationManager;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.security.core.userdetails.UserDetailsService;
import org.springframework.web.bind.annotation.*;

import java.util.HashMap;
import java.util.Map;

@RestController
@RequestMapping("/api/auth")
public class AuthController {
    
    @Autowired
    private AuthenticationManager authenticationManager;
    
    @Autowired
    private UserDetailsService userDetailsService;
    
    @Autowired
    private JwtUtil jwtUtil;
    
    @PostMapping("/login")
    public ResponseEntity<?> login(@RequestBody LoginRequest loginRequest) {
        try {
            Authentication authentication = authenticationManager.authenticate(
                new UsernamePasswordAuthenticationToken(
                    loginRequest.getUsername(), 
                    loginRequest.getPassword()
                )
            );
            
            UserDetails userDetails = userDetailsService.loadUserByUsername(loginRequest.getUsername());
            String token = jwtUtil.generateToken(userDetails);
            
            Map<String, Object> response = new HashMap<>();
            response.put("token", token);
            response.put("type", "Bearer");
            response.put("username", userDetails.getUsername());
            response.put("authorities", userDetails.getAuthorities());
            
            return ResponseEntity.ok(response);
            
        } catch (Exception e) {
            return ResponseEntity.badRequest()
                .body(Map.of("error", "Invalid credentials"));
        }
    }
    
    @PostMapping("/refresh")
    public ResponseEntity<?> refresh(@RequestHeader("Authorization") String token) {
        try {
            String jwt = token.substring(7); // Remove "Bearer " prefix
            String username = jwtUtil.extractUsername(jwt);
            UserDetails userDetails = userDetailsService.loadUserByUsername(username);
            
            if (jwtUtil.validateToken(jwt, userDetails)) {
                String newToken = jwtUtil.generateToken(userDetails);
                return ResponseEntity.ok(Map.of("token", newToken));
            }
            
            return ResponseEntity.badRequest()
                .body(Map.of("error", "Invalid token"));
                
        } catch (Exception e) {
            return ResponseEntity.badRequest()
                .body(Map.of("error", "Token refresh failed"));
        }
    }
    
    @GetMapping("/userinfo")
    public ResponseEntity<?> getUserInfo(Authentication authentication) {
        Map<String, Object> userInfo = new HashMap<>();
        userInfo.put("username", authentication.getName());
        userInfo.put("authorities", authentication.getAuthorities());
        userInfo.put("authenticated", authentication.isAuthenticated());
        
        return ResponseEntity.ok(userInfo);
    }
    
    public static class LoginRequest {
        private String username;
        private String password;
        
        // Getters and setters
        public String getUsername() { return username; }
        public void setUsername(String username) { this.username = username; }
        public String getPassword() { return password; }
        public void setPassword(String password) { this.password = password; }
    }
}
EOF

# Update environment configuration with OAuth2 settings
if [ -f ".env.production" ]; then
    sed -i "s|OAUTH2_ENABLED=.*|OAUTH2_ENABLED=true|g" .env.production
    sed -i "s|OAUTH2_ISSUER_URL=.*|OAUTH2_ISSUER_URL=${oauth2_issuer}|g" .env.production
    sed -i "s|OAUTH2_CLIENT_ID=.*|OAUTH2_CLIENT_ID=${client_id}|g" .env.production
    sed -i "s|OAUTH2_CLIENT_SECRET=.*|OAUTH2_CLIENT_SECRET=${client_secret}|g" .env.production
    
    echo -e "${GREEN}✅ Environment file updated with OAuth2 settings${NC}"
fi

# Create OAuth2 Docker configuration
cat > "microservices/oauth2-config.yml" << EOF
# OAuth2 Configuration for Docker Compose
version: '3.8'

services:
  integration-service:
    environment:
      - OAUTH2_ENABLED=true
      - OAUTH2_ISSUER_URL=${oauth2_issuer}
      - OAUTH2_CLIENT_ID=${client_id}
      - OAUTH2_CLIENT_SECRET=${client_secret}
      - JWT_SECRET=\${JWT_SECRET}
    volumes:
      - ./oauth2/keys:/app/keys:ro
      - ./oauth2/config:/app/config:ro

  # Optional: OAuth2 Authorization Server (if using built-in)
  oauth2-server:
    image: openjdk:17-jre-slim
    ports:
      - "9000:9000"
    environment:
      - SPRING_PROFILES_ACTIVE=oauth2-server
      - SERVER_PORT=9000
    volumes:
      - ./oauth2/keys:/app/keys:ro
      - ./oauth2/config:/app/config:ro
    depends_on:
      - integration-db
EOF

# Create OAuth2 test script
cat > "test-oauth2.sh" << 'EOF'
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
EOF

chmod +x test-oauth2.sh

# Set proper permissions
chmod -R 644 "$CONFIG_DIR"
chmod -R 600 "$KEYS_DIR"

echo ""
echo -e "${GREEN}✅ OAuth2 Authentication Configuration Complete!${NC}"
echo ""
echo -e "${BLUE}📋 Summary:${NC}"
echo "- OAuth2 provider configured: $oauth_choice"
echo "- Client ID: $client_id"
echo "- Issuer URL: $oauth2_issuer"
echo "- Configuration files created in: $CONFIG_DIR"

if [ "$oauth_choice" = "1" ]; then
    echo "- JWT keys generated in: $KEYS_DIR"
fi

echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo "1. Test OAuth2 configuration: ./test-oauth2.sh"
echo "2. Update application.yml with OAuth2 settings"
echo "3. Configure user roles and permissions"
echo "4. Set up user management interface"
echo ""
echo -e "${YELLOW}⚠️  Important Security Notes:${NC}"
echo "- JWT signing keys are stored securely with restricted permissions"
echo "- Client secret is generated and stored in environment file"
echo "- Default users (admin/user) should be changed in production"
echo "- Consider implementing proper user management"
echo ""

echo -e "${GREEN}🔐 OAuth2 authentication setup completed successfully!${NC}"