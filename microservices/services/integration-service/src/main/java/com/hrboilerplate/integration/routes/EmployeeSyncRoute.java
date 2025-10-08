package com.hrboilerplate.integration.routes;

import org.apache.camel.builder.RouteBuilder;
import org.apache.camel.component.jackson.JacksonDataFormat;
import org.springframework.stereotype.Component;

/**
 * Apache Camel route for employee data synchronization with ERP systems.
 */
@Component
public class EmployeeSyncRoute extends RouteBuilder {

    @Override
    public void configure() throws Exception {
        
        // JSON data format
        JacksonDataFormat jsonFormat = new JacksonDataFormat();
        
        // Global exception handling
        onException(Exception.class)
            .log("Employee sync failed: ${exception.message}")
            .to("direct:handle-sync-error");
        
        // Scheduled employee sync route - ENABLED for automated operation
        // Using timer component with repeatCount=0 for indefinite scheduling
        from("timer:employeeSync?delay=60000&period=7200000&repeatCount=0") // Start after 1 min, repeat every 2 hours
            .routeId("employee-sync-scheduled")
            .log("Starting scheduled employee synchronization")
            .choice()
                .when(simple("{{integration.sync.employee.enabled}}"))
                    .log("Employee sync is enabled, proceeding with synchronization")
                    .to("direct:employee-sync")
                .otherwise()
                    .log("Employee sync is disabled, skipping synchronization")
            .end();

        // Main employee sync route
        from("direct:employee-sync")
            .routeId("employee-sync-main")
            .log("Employee sync started")
            // Fetch employees from Laravel HR system
            .to("direct:fetch-employees-from-laravel")
            .log("Fetched ${header.employeeCount} employees from Laravel")
            
            // Transform data for ERP system
            .to("direct:transform-employees-for-erp")
            .log("Transformed employee data for ERP")
            
            // Send to ERP system
            .choice()
                .when(simple("{{erp.frappe.enabled}}"))
                    .to("direct:send-employees-to-frappe")
                .when(simple("{{erp.generic.enabled}}"))
                    .to("direct:send-employees-to-generic-erp")
                .otherwise()
                    .log("No ERP system enabled for employee sync")
            .end()
            
            .log("Employee sync completed successfully");

        // Fetch employees from Laravel HR system
        from("direct:fetch-employees-from-laravel")
            .routeId("fetch-employees-laravel")
            .setHeader("Authorization", constant("Bearer {{integration.laravel.api-token}}"))
            .setHeader("Accept", constant("application/json"))
            .to("{{integration.laravel.base-url}}/api/employees?batch={{integration.sync.employee.batch-size}}")
            .unmarshal(jsonFormat)
            .setHeader("employeeCount", jsonpath("$.data.length()"));

        // Transform employees for ERP system
        from("direct:transform-employees-for-erp")
            .routeId("transform-employees-erp")
            .to("bean:employeeDataTransformer?method=transformForErp");

        // Send employees to Frappe ERP
        from("direct:send-employees-to-frappe")
            .routeId("send-employees-frappe")
            .log("Sending employees to Frappe ERP")
            .setHeader("Authorization", simple("token {{erp.frappe.api-key}}:{{erp.frappe.api-secret}}"))
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .marshal(jsonFormat)
            .to("http://{{erp.frappe.base-url}}/api/resource/Employee?httpMethod=POST")
            .log("Employees sent to Frappe successfully");

        // Send employees to generic ERP
        from("direct:send-employees-to-generic-erp")
            .routeId("send-employees-generic")
            .log("Sending employees to Generic ERP")
            .choice()
                .when(simple("'{{erp.generic.auth-type}}' == 'bearer'"))
                    .setHeader("Authorization", simple("Bearer {{erp.generic.token}}"))
                .when(simple("'{{erp.generic.auth-type}}' == 'basic'"))
                    .setHeader("Authorization", simple("Basic {{erp.generic.username}}:{{erp.generic.password}}"))
                .when(simple("'{{erp.generic.auth-type}}' == 'api-key'"))
                    .setHeader("X-API-Key", simple("{{erp.generic.token}}"))
            .end()
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .marshal(jsonFormat)
            .to("http://{{erp.generic.base-url}}/api/employees?httpMethod=POST")
            .log("Employees sent to Generic ERP successfully");

        // Bidirectional sync - Fetch employees from ERP back to Laravel
        from("direct:fetch-employees-from-erp")
            .routeId("fetch-employees-erp")
            .log("Fetching employee updates from ERP")
            .choice()
                .when(simple("{{erp.frappe.enabled}}"))
                    .to("direct:fetch-employees-from-frappe")
                .when(simple("{{erp.generic.enabled}}"))
                    .to("direct:fetch-employees-from-generic-erp")
            .end()
            .to("direct:send-employee-updates-to-laravel");

        // Fetch employees from Frappe
        from("direct:fetch-employees-from-frappe")
            .routeId("fetch-employees-frappe")
            .setHeader("Authorization", simple("token {{erp.frappe.api-key}}:{{erp.frappe.api-secret}}"))
            .setHeader("Accept", constant("application/json"))
            .to("http://{{erp.frappe.base-url}}/api/resource/Employee?fields=[\"name\",\"employee_name\",\"designation\",\"department\",\"status\"]")
            .unmarshal(jsonFormat);

        // Send employee updates back to Laravel
        from("direct:send-employee-updates-to-laravel")
            .routeId("send-employee-updates-laravel")
            .to("bean:employeeDataTransformer?method=transformForLaravel")
            .setHeader("Authorization", constant("Bearer {{integration.laravel.api-token}}"))
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .marshal(jsonFormat)
            .to("{{integration.laravel.base-url}}/api/employees/bulk-update?httpMethod=PUT")
            .log("Employee updates sent to Laravel successfully");
    }
}