package com.hrboilerplate.integration.service;

import com.hrboilerplate.integration.config.ErpProperties;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import java.nio.charset.StandardCharsets;
import java.util.Base64;
import java.util.Map;
import java.util.HashMap;

/**
 * Service for handling authentication with different ERP systems.
 */
@Service("authenticationService")
public class AuthenticationService {

    @Autowired
    private ErpProperties erpProperties;

    /**
     * Get authentication headers for Frappe ERP.
     */
    public HttpHeaders getFrappeAuthHeaders() {
        HttpHeaders headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        
        String apiKey = erpProperties.getFrappe().getApiKey();
        String apiSecret = erpProperties.getFrappe().getApiSecret();
        
        if (apiKey != null && apiSecret != null) {
            String token = "token " + apiKey + ":" + apiSecret;
            headers.set("Authorization", token);
        }
        
        return headers;
    }

    /**
     * Get authentication headers for generic ERP.
     */
    public HttpHeaders getGenericErpAuthHeaders() {
        HttpHeaders headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        
        String authType = erpProperties.getGeneric().getAuthType();
        
        switch (authType.toLowerCase()) {
            case "bearer":
                if (erpProperties.getGeneric().getToken() != null) {
                    headers.set("Authorization", "Bearer " + erpProperties.getGeneric().getToken());
                }
                break;
                
            case "basic":
                String username = erpProperties.getGeneric().getUsername();
                String password = erpProperties.getGeneric().getPassword();
                if (username != null && password != null) {
                    String credentials = username + ":" + password;
                    String encoded = Base64.getEncoder().encodeToString(credentials.getBytes(StandardCharsets.UTF_8));
                    headers.set("Authorization", "Basic " + encoded);
                }
                break;
                
            case "api-key":
                if (erpProperties.getGeneric().getToken() != null) {
                    headers.set("X-API-Key", erpProperties.getGeneric().getToken());
                }
                break;
        }
        
        return headers;
    }

    /**
     * Get authentication headers for Laravel API.
     */
    public HttpHeaders getLaravelAuthHeaders() {
        HttpHeaders headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        
        // Assuming Bearer token authentication for Laravel
        String token = System.getProperty("integration.laravel.api-token");
        if (token != null && !token.isEmpty()) {
            headers.set("Authorization", "Bearer " + token);
        }
        
        return headers;
    }

    /**
     * Refresh authentication token (placeholder implementation).
     */
    public Map<String, String> refreshToken() {
        Map<String, String> result = new HashMap<>();
        
        try {
            // Implementation depends on the ERP system's token refresh mechanism
            // For Frappe, you might need to call their auth endpoint
            // For now, returning a success status
            
            result.put("status", "success");
            result.put("message", "Token refreshed successfully");
            
        } catch (Exception e) {
            result.put("status", "error");
            result.put("message", "Failed to refresh token: " + e.getMessage());
        }
        
        return result;
    }

    /**
     * Validate authentication credentials.
     */
    public boolean validateCredentials(String system) {
        switch (system.toLowerCase()) {
            case "frappe":
                return erpProperties.getFrappe().getApiKey() != null && 
                       erpProperties.getFrappe().getApiSecret() != null;
                       
            case "generic":
                String authType = erpProperties.getGeneric().getAuthType();
                if ("bearer".equals(authType) || "api-key".equals(authType)) {
                    return erpProperties.getGeneric().getToken() != null;
                } else if ("basic".equals(authType)) {
                    return erpProperties.getGeneric().getUsername() != null && 
                           erpProperties.getGeneric().getPassword() != null;
                }
                return false;
                
            default:
                return false;
        }
    }

    /**
     * Generate secure headers for API calls.
     */
    public Map<String, String> getSecureHeaders(String system) {
        Map<String, String> headers = new HashMap<>();
        
        headers.put("Content-Type", "application/json");
        headers.put("Accept", "application/json");
        headers.put("User-Agent", "HR-Integration-Service/1.0");
        
        // Add timestamp for request tracking
        headers.put("X-Request-Time", String.valueOf(System.currentTimeMillis()));
        
        // Add system-specific authentication
        switch (system.toLowerCase()) {
            case "frappe":
                String frappeToken = "token " + erpProperties.getFrappe().getApiKey() + 
                                   ":" + erpProperties.getFrappe().getApiSecret();
                headers.put("Authorization", frappeToken);
                break;
                
            case "generic":
                // Implementation based on auth type
                String authType = erpProperties.getGeneric().getAuthType();
                if ("bearer".equals(authType)) {
                    headers.put("Authorization", "Bearer " + erpProperties.getGeneric().getToken());
                }
                break;
        }
        
        return headers;
    }

    /**
     * Check if authentication is required for the given endpoint.
     */
    public boolean isAuthRequired(String endpoint) {
        // Most ERP endpoints require authentication
        // Add logic for public endpoints if needed
        return !endpoint.contains("/health") && !endpoint.contains("/ping");
    }

    /**
     * Get timeout configuration for the system.
     */
    public int getTimeout(String system) {
        switch (system.toLowerCase()) {
            case "frappe":
                return erpProperties.getFrappe().getTimeout();
            default:
                return 30000; // 30 seconds default
        }
    }

    /**
     * Get retry attempts configuration for the system.
     */
    public int getRetryAttempts(String system) {
        switch (system.toLowerCase()) {
            case "frappe":
                return erpProperties.getFrappe().getRetryAttempts();
            default:
                return 3; // 3 attempts default
        }
    }
}