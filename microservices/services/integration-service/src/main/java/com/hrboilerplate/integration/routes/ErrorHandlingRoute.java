package com.hrboilerplate.integration.routes;

import org.apache.camel.builder.RouteBuilder;
import org.springframework.stereotype.Component;

/**
 * Apache Camel route for comprehensive error handling and recovery mechanisms.
 */
@Component
public class ErrorHandlingRoute extends RouteBuilder {

    @Override
    public void configure() throws Exception {
        
        // Global error handler configuration
        errorHandler(deadLetterChannel("direct:dead-letter-queue")
            .maximumRedeliveries(3)
            .redeliveryDelay(5000)
            .retryAttemptedLogLevel(org.apache.camel.LoggingLevel.WARN)
            .retriesExhaustedLogLevel(org.apache.camel.LoggingLevel.ERROR)
            .logRetryAttempted(true)
            .logExhausted(true)
            .backOffMultiplier(2)
            .useExponentialBackOff());

        // Dead Letter Queue route
        from("direct:dead-letter-queue")
            .routeId("dead-letter-queue")
            .log("Message sent to dead letter queue: ${body}")
            .to("bean:errorNotificationService?method=notifyError")
            .to("file:{{integration.error.directory:./errors}}?fileName=error-${date:now:yyyyMMdd-HHmmss}.json");

        // Main error handling route
        from("direct:handle-sync-error")
            .routeId("handle-sync-error")
            .log("Handling sync error: ${exception.message}")
            .choice()
                .when(header("CamelHttpResponseCode").isEqualTo(401))
                    .to("direct:handle-authentication-error")
                .when(header("CamelHttpResponseCode").isEqualTo(403))
                    .to("direct:handle-authorization-error")
                .when(header("CamelHttpResponseCode").isEqualTo(404))
                    .to("direct:handle-not-found-error")
                .when(header("CamelHttpResponseCode").isEqualTo(429))
                    .to("direct:handle-rate-limit-error")
                .when(header("CamelHttpResponseCode").isEqualTo(500))
                    .to("direct:handle-server-error")
                .otherwise()
                    .to("direct:handle-generic-error")
            .end();

        // Authentication error handling
        from("direct:handle-authentication-error")
            .routeId("handle-authentication-error")
            .log("Authentication error occurred")
            .setHeader("errorType", constant("AUTHENTICATION_ERROR"))
            .setHeader("errorMessage", constant("Invalid credentials or authentication token"))
            .to("direct:retry-with-fresh-token")
            .to("direct:log-error");

        // Authorization error handling
        from("direct:handle-authorization-error")
            .routeId("handle-authorization-error")
            .log("Authorization error occurred")
            .setHeader("errorType", constant("AUTHORIZATION_ERROR"))
            .setHeader("errorMessage", constant("Insufficient permissions"))
            .to("direct:log-error")
            .to("direct:notify-admin");

        // Not found error handling
        from("direct:handle-not-found-error")
            .routeId("handle-not-found-error")
            .log("Resource not found error")
            .setHeader("errorType", constant("NOT_FOUND_ERROR"))
            .setHeader("errorMessage", constant("Resource not found"))
            .to("direct:log-error");

        // Rate limit error handling
        from("direct:handle-rate-limit-error")
            .routeId("handle-rate-limit-error")
            .log("Rate limit exceeded")
            .setHeader("errorType", constant("RATE_LIMIT_ERROR"))
            .setHeader("errorMessage", constant("Rate limit exceeded"))
            .delay(30000) // Wait 30 seconds before retry
            .to("direct:log-error");

        // Server error handling
        from("direct:handle-server-error")
            .routeId("handle-server-error")
            .log("Server error occurred")
            .setHeader("errorType", constant("SERVER_ERROR"))
            .setHeader("errorMessage", constant("Internal server error"))
            .to("direct:log-error")
            .to("direct:notify-admin");

        // Generic error handling
        from("direct:handle-generic-error")
            .routeId("handle-generic-error")
            .log("Generic error occurred: ${exception.message}")
            .setHeader("errorType", constant("GENERIC_ERROR"))
            .setHeader("errorMessage", simple("${exception.message}"))
            .to("direct:log-error");

        // Retry with fresh authentication token
        from("direct:retry-with-fresh-token")
            .routeId("retry-with-fresh-token")
            .log("Attempting to refresh authentication token")
            .to("bean:authTokenService?method=refreshToken")
            .log("Authentication token refreshed successfully");

        // Error logging route
        from("direct:log-error")
            .routeId("log-error")
            .log("Error logged: Type=${header.errorType}, Message=${header.errorMessage}")
            .to("bean:errorLoggingService?method=logError");

        // Admin notification route
        from("direct:notify-admin")
            .routeId("notify-admin")
            .log("Notifying admin about critical error")
            .to("bean:notificationService?method=notifyAdmin");

        // Health check error route
        from("direct:health-check-error")
            .routeId("health-check-error")
            .log("Health check failed")
            .setHeader("healthStatus", constant("DOWN"))
            .setHeader("errorDetails", simple("${exception.message}"))
            .to("bean:healthService?method=recordHealthFailure");

        // Data validation error route
        from("direct:validation-error")
            .routeId("validation-error")
            .log("Data validation failed: ${body}")
            .setHeader("errorType", constant("VALIDATION_ERROR"))
            .setHeader("validationErrors", body())
            .to("direct:log-error")
            .setBody(constant("{\"status\":\"error\",\"message\":\"Data validation failed\"}"));

        // Timeout error handling
        from("direct:timeout-error")
            .routeId("timeout-error")
            .log("Request timeout occurred")
            .setHeader("errorType", constant("TIMEOUT_ERROR"))
            .setHeader("errorMessage", constant("Request timeout"))
            .to("direct:log-error")
            .delay(5000) // Wait 5 seconds before retry
            .setBody(constant("{\"status\":\"error\",\"message\":\"Request timeout, retrying\"}"));

        // Connection error handling
        from("direct:connection-error")
            .routeId("connection-error")
            .log("Connection error occurred")
            .setHeader("errorType", constant("CONNECTION_ERROR"))
            .setHeader("errorMessage", constant("Connection failed"))
            .to("direct:log-error")
            .delay(10000) // Wait 10 seconds before retry
            .setBody(constant("{\"status\":\"error\",\"message\":\"Connection failed, retrying\"}"));

        // Error metrics collection
        from("direct:collect-error-metrics")
            .routeId("collect-error-metrics")
            .log("Collecting error metrics")
            .to("bean:metricsService?method=incrementErrorCount")
            .to("bean:metricsService?method=recordErrorType");
    }
}