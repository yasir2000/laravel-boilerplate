package com.hrboilerplate.integration.routes;

import org.apache.camel.builder.RouteBuilder;
import org.apache.camel.component.jackson.JacksonDataFormat;
import org.springframework.stereotype.Component;

/**
 * Apache Camel route for payroll data synchronization with ERP systems.
 */
@Component
public class PayrollSyncRoute extends RouteBuilder {

    @Override
    public void configure() throws Exception {
        
        JacksonDataFormat jsonFormat = new JacksonDataFormat();
        
        // Global exception handling
        onException(Exception.class)
            .log("Payroll sync failed: ${exception.message}")
            .to("direct:handle-sync-error");
        
        // Scheduled payroll sync route - ENABLED for automated operation
        // Using timer component with different period for payroll sync (every 3 hours)
        from("timer:payrollSync?delay=120000&period=10800000&repeatCount=0") // Start after 2 min, repeat every 3 hours
            .routeId("payroll-sync-scheduled")
            .log("Starting scheduled payroll synchronization")
            .choice()
                .when(simple("{{integration.sync.payroll.enabled}}"))
                    .log("Payroll sync is enabled, proceeding with synchronization")
                    .to("direct:payroll-sync")
                .otherwise()
                    .log("Payroll sync is disabled, skipping synchronization")
            .end();

        // Main payroll sync route
        from("direct:payroll-sync")
            .routeId("payroll-sync-main")
            .log("Payroll sync started")
            // Fetch payroll data from Laravel HR system
            .to("direct:fetch-payroll-from-laravel")
            .log("Fetched ${header.payrollCount} payroll records from Laravel")
            
            // Transform data for ERP system
            .to("direct:transform-payroll-for-erp")
            .log("Transformed payroll data for ERP")
            
            // Send to ERP system
            .choice()
                .when(simple("{{erp.frappe.enabled}}"))
                    .to("direct:send-payroll-to-frappe")
                .when(simple("{{erp.generic.enabled}}"))
                    .to("direct:send-payroll-to-generic-erp")
                .otherwise()
                    .log("No ERP system enabled for payroll sync")
            .end()
            
            .log("Payroll sync completed successfully");

        // Fetch payroll from Laravel HR system
        from("direct:fetch-payroll-from-laravel")
            .routeId("fetch-payroll-laravel")
            .setHeader("Authorization", constant("Bearer {{integration.laravel.api-token}}"))
            .setHeader("Accept", constant("application/json"))
            .to("{{integration.laravel.base-url}}/api/payroll?batch={{integration.sync.payroll.batch-size}}")
            .unmarshal(jsonFormat)
            .setHeader("payrollCount", jsonpath("$.data.length()"));

        // Transform payroll for ERP system
        from("direct:transform-payroll-for-erp")
            .routeId("transform-payroll-erp")
            .to("bean:payrollDataTransformer?method=transformForErp");

        // Send payroll to Frappe ERP
        from("direct:send-payroll-to-frappe")
            .routeId("send-payroll-frappe")
            .log("Sending payroll to Frappe ERP")
            .setHeader("Authorization", simple("token {{erp.frappe.api-key}}:{{erp.frappe.api-secret}}"))
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .split(jsonpath("$.data"))
                .marshal(jsonFormat)
                .to("http://{{erp.frappe.base-url}}/api/resource/Salary Slip?httpMethod=POST")
                .log("Payroll record sent to Frappe")
            .end()
            .log("All payroll records sent to Frappe successfully");

        // Send payroll to generic ERP
        from("direct:send-payroll-to-generic-erp")
            .routeId("send-payroll-generic")
            .log("Sending payroll to Generic ERP")
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
            .to("http://{{erp.generic.base-url}}/api/payroll?httpMethod=POST")
            .log("Payroll sent to Generic ERP successfully");

        // Fetch payroll status updates from ERP
        from("direct:fetch-payroll-status-from-erp")
            .routeId("fetch-payroll-status-erp")
            .log("Fetching payroll status updates from ERP")
            .choice()
                .when(simple("{{erp.frappe.enabled}}"))
                    .to("direct:fetch-payroll-from-frappe")
                .when(simple("{{erp.generic.enabled}}"))
                    .to("direct:fetch-payroll-from-generic-erp")
            .end()
            .to("direct:send-payroll-updates-to-laravel");

        // Fetch payroll from Frappe
        from("direct:fetch-payroll-from-frappe")
            .routeId("fetch-payroll-frappe")
            .setHeader("Authorization", simple("token {{erp.frappe.api-key}}:{{erp.frappe.api-secret}}"))
            .setHeader("Accept", constant("application/json"))
            .to("http://{{erp.frappe.base-url}}/api/resource/Salary Slip?fields=[\"name\",\"employee\",\"gross_pay\",\"net_pay\",\"posting_date\",\"status\"]")
            .unmarshal(jsonFormat);

        // Send payroll updates back to Laravel
        from("direct:send-payroll-updates-to-laravel")
            .routeId("send-payroll-updates-laravel")
            .to("bean:payrollDataTransformer?method=transformForLaravel")
            .setHeader("Authorization", constant("Bearer {{integration.laravel.api-token}}"))
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .marshal(jsonFormat)
            .to("{{integration.laravel.base-url}}/api/payroll/bulk-update?httpMethod=PUT")
            .log("Payroll updates sent to Laravel successfully");

        // Leave application sync route
        from("direct:sync-leave-applications")
            .routeId("sync-leave-applications")
            .log("Syncing leave applications")
            .to("direct:fetch-leave-applications-from-laravel")
            .to("direct:transform-leave-for-erp")
            .choice()
                .when(simple("{{erp.frappe.enabled}}"))
                    .to("direct:send-leave-to-frappe")
            .end();

        // Fetch leave applications from Laravel
        from("direct:fetch-leave-applications-from-laravel")
            .routeId("fetch-leave-laravel")
            .setHeader("Authorization", constant("Bearer {{integration.laravel.api-token}}"))
            .setHeader("Accept", constant("application/json"))
            .to("{{integration.laravel.base-url}}/api/leave-applications")
            .unmarshal(jsonFormat);

        // Transform leave for ERP
        from("direct:transform-leave-for-erp")
            .routeId("transform-leave-erp")
            .to("bean:leaveDataTransformer?method=transformForErp");

        // Send leave to Frappe
        from("direct:send-leave-to-frappe")
            .routeId("send-leave-frappe")
            .setHeader("Authorization", simple("token {{erp.frappe.api-key}}:{{erp.frappe.api-secret}}"))
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .split(jsonpath("$.data"))
                .marshal(jsonFormat)
                .to("http://{{erp.frappe.base-url}}/api/resource/Leave Application?httpMethod=POST")
            .end()
            .log("Leave applications sent to Frappe successfully");
    }
}