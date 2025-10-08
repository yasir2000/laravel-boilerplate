package com.hrboilerplate.integration.routes;

import org.apache.camel.builder.RouteBuilder;
import org.apache.camel.model.rest.RestBindingMode;
import org.springframework.stereotype.Component;

/**
 * REST API routes for the ERP Integration Service.
 * Provides endpoints for triggering integrations and monitoring status.
 */
@Component
public class RestApiRoute extends RouteBuilder {

    @Override
    public void configure() throws Exception {
        
        // Configure REST DSL
        restConfiguration()
            .component("servlet")
            .bindingMode(RestBindingMode.json)
            .dataFormatProperty("prettyPrint", "true")
            .enableCORS(true)
            .port("{{server.port}}")
            .contextPath("/integration/camel")
            .apiContextPath("/api-doc")
            .apiProperty("api.title", "ERP Integration API")
            .apiProperty("api.version", "1.0.0")
            .apiProperty("cors", "true");

        // Health check endpoint
        rest("/health")
            .description("Health check operations")
            .get()
                .description("Get service health status")
                .outType(String.class)
                .to("direct:health-check");

        // Employee integration endpoints
        rest("/employee")
            .description("Employee integration operations")
            .post("/sync")
                .description("Trigger employee synchronization")
                .outType(String.class)
                .to("direct:employee-sync-trigger")
            .get("/status")
                .description("Get employee sync status")
                .outType(String.class)
                .to("direct:employee-sync-status");

        // Payroll integration endpoints
        rest("/payroll")
            .description("Payroll integration operations")
            .post("/sync")
                .description("Trigger payroll synchronization")
                .outType(String.class)
                .to("direct:payroll-sync-trigger")
            .get("/status")
                .description("Get payroll sync status")
                .outType(String.class)
                .to("direct:payroll-sync-status");

        // Accounting integration endpoints
        rest("/accounting")
            .description("Accounting integration operations")
            .post("/sync")
                .description("Trigger accounting synchronization")
                .outType(String.class)
                .to("direct:accounting-sync-trigger")
            .get("/status")
                .description("Get accounting sync status")
                .outType(String.class)
                .to("direct:accounting-sync-status");

        // Integration status endpoint
        rest("/integration")
            .description("General integration operations")
            .get("/status")
                .description("Get overall integration status")
                .outType(String.class)
                .to("direct:integration-status");

        // Direct routes implementation
        from("direct:health-check")
            .setBody(constant("{\"status\":\"UP\",\"service\":\"integration-service\"}"))
            .setHeader("Content-Type", constant("application/json"));

        from("direct:employee-sync-trigger")
            .log("Employee sync triggered manually")
            .to("direct:employee-sync")
            .setBody(constant("{\"status\":\"triggered\",\"message\":\"Employee sync started\"}"));

        from("direct:payroll-sync-trigger")
            .log("Payroll sync triggered manually")
            .to("direct:payroll-sync")
            .setBody(constant("{\"status\":\"triggered\",\"message\":\"Payroll sync started\"}"));

        from("direct:accounting-sync-trigger")
            .log("Accounting sync triggered manually")
            .to("direct:accounting-sync")
            .setBody(constant("{\"status\":\"triggered\",\"message\":\"Accounting sync started\"}"));

        from("direct:employee-sync-status")
            .setBody(constant("{\"status\":\"active\",\"lastSync\":\"2024-01-01T00:00:00Z\"}"));

        from("direct:payroll-sync-status")
            .setBody(constant("{\"status\":\"active\",\"lastSync\":\"2024-01-01T00:00:00Z\"}"));

        from("direct:accounting-sync-status")
            .setBody(constant("{\"status\":\"active\",\"lastSync\":\"2024-01-01T00:00:00Z\"}"));

        from("direct:integration-status")
            .setBody(constant("{\"status\":\"running\",\"uptime\":\"24h\",\"integrations\":{\"employee\":\"active\",\"payroll\":\"active\",\"accounting\":\"active\"}}"));
    }
}