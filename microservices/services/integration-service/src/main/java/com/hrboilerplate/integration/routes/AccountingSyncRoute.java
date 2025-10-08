package com.hrboilerplate.integration.routes;

import org.apache.camel.builder.RouteBuilder;
import org.apache.camel.component.jackson.JacksonDataFormat;
import org.springframework.stereotype.Component;

/**
 * Apache Camel route for accounting data synchronization with ERP systems.
 */
@Component
public class AccountingSyncRoute extends RouteBuilder {

    @Override
    public void configure() throws Exception {
        
        JacksonDataFormat jsonFormat = new JacksonDataFormat();
        
        // Scheduled accounting sync route - commented out for initial deployment
        // from("cron:accountingSync?cron={{integration.sync.accounting.cron}}")
        //     .routeId("accounting-sync-scheduled")
        //     .log("Starting scheduled accounting synchronization")
        //     .to("direct:accounting-sync");

        // Main accounting sync route
        from("direct:accounting-sync")
            .routeId("accounting-sync-main")
            .log("Accounting sync started")
            .doTry()
                // Sync various accounting components
                .to("direct:sync-chart-of-accounts")
                .to("direct:sync-journal-entries")
                .to("direct:sync-expense-claims")
                .to("direct:sync-purchase-orders")
                
                .log("Accounting sync completed successfully")
                
            .doCatch(Exception.class)
                .log("Accounting sync failed: ${exception.message}")
                .to("direct:handle-sync-error")
            .end();

        // Chart of Accounts synchronization
        from("direct:sync-chart-of-accounts")
            .routeId("sync-chart-accounts")
            .log("Syncing chart of accounts")
            .to("direct:fetch-accounts-from-laravel")
            .to("direct:transform-accounts-for-erp")
            .choice()
                .when(simple("{{erp.frappe.enabled}}"))
                    .to("direct:send-accounts-to-frappe")
                .when(simple("{{erp.generic.enabled}}"))
                    .to("direct:send-accounts-to-generic-erp")
            .end();

        // Fetch accounts from Laravel
        from("direct:fetch-accounts-from-laravel")
            .routeId("fetch-accounts-laravel")
            .setHeader("Authorization", constant("Bearer {{integration.laravel.api-token}}"))
            .setHeader("Accept", constant("application/json"))
            .to("{{integration.laravel.base-url}}/api/accounts")
            .unmarshal(jsonFormat);

        // Transform accounts for ERP
        from("direct:transform-accounts-for-erp")
            .routeId("transform-accounts-erp")
            .to("bean:accountingDataTransformer?method=transformAccountsForErp");

        // Send accounts to Frappe
        from("direct:send-accounts-to-frappe")
            .routeId("send-accounts-frappe")
            .setHeader("Authorization", simple("token {{erp.frappe.api-key}}:{{erp.frappe.api-secret}}"))
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .split(jsonpath("$.data"))
                .marshal(jsonFormat)
                .to("http://{{erp.frappe.base-url}}/api/resource/Account?httpMethod=POST")
            .end()
            .log("Chart of accounts sent to Frappe successfully");

        // Journal Entries synchronization
        from("direct:sync-journal-entries")
            .routeId("sync-journal-entries")
            .log("Syncing journal entries")
            .to("direct:fetch-journal-entries-from-laravel")
            .to("direct:transform-journal-entries-for-erp")
            .choice()
                .when(simple("{{erp.frappe.enabled}}"))
                    .to("direct:send-journal-entries-to-frappe")
            .end();

        // Fetch journal entries from Laravel
        from("direct:fetch-journal-entries-from-laravel")
            .routeId("fetch-journal-entries-laravel")
            .setHeader("Authorization", constant("Bearer {{integration.laravel.api-token}}"))
            .setHeader("Accept", constant("application/json"))
            .to("{{integration.laravel.base-url}}/api/journal-entries?batch={{integration.sync.accounting.batch-size}}")
            .unmarshal(jsonFormat);

        // Transform journal entries for ERP
        from("direct:transform-journal-entries-for-erp")
            .routeId("transform-journal-entries-erp")
            .to("bean:accountingDataTransformer?method=transformJournalEntriesForErp");

        // Send journal entries to Frappe
        from("direct:send-journal-entries-to-frappe")
            .routeId("send-journal-entries-frappe")
            .setHeader("Authorization", simple("token {{erp.frappe.api-key}}:{{erp.frappe.api-secret}}"))
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .split(jsonpath("$.data"))
                .marshal(jsonFormat)
                .to("http://{{erp.frappe.base-url}}/api/resource/Journal Entry?httpMethod=POST")
            .end()
            .log("Journal entries sent to Frappe successfully");

        // Expense Claims synchronization
        from("direct:sync-expense-claims")
            .routeId("sync-expense-claims")
            .log("Syncing expense claims")
            .to("direct:fetch-expense-claims-from-laravel")
            .to("direct:transform-expense-claims-for-erp")
            .choice()
                .when(simple("{{erp.frappe.enabled}}"))
                    .to("direct:send-expense-claims-to-frappe")
            .end();

        // Fetch expense claims from Laravel
        from("direct:fetch-expense-claims-from-laravel")
            .routeId("fetch-expense-claims-laravel")
            .setHeader("Authorization", constant("Bearer {{integration.laravel.api-token}}"))
            .setHeader("Accept", constant("application/json"))
            .to("{{integration.laravel.base-url}}/api/expense-claims")
            .unmarshal(jsonFormat);

        // Transform expense claims for ERP
        from("direct:transform-expense-claims-for-erp")
            .routeId("transform-expense-claims-erp")
            .to("bean:accountingDataTransformer?method=transformExpenseClaimsForErp");

        // Send expense claims to Frappe
        from("direct:send-expense-claims-to-frappe")
            .routeId("send-expense-claims-frappe")
            .setHeader("Authorization", simple("token {{erp.frappe.api-key}}:{{erp.frappe.api-secret}}"))
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .split(jsonpath("$.data"))
                .marshal(jsonFormat)
                .to("http://{{erp.frappe.base-url}}/api/resource/Expense Claim?httpMethod=POST")
            .end()
            .log("Expense claims sent to Frappe successfully");

        // Purchase Orders synchronization
        from("direct:sync-purchase-orders")
            .routeId("sync-purchase-orders")
            .log("Syncing purchase orders")
            .to("direct:fetch-purchase-orders-from-laravel")
            .to("direct:transform-purchase-orders-for-erp")
            .choice()
                .when(simple("{{erp.frappe.enabled}}"))
                    .to("direct:send-purchase-orders-to-frappe")
            .end();

        // Fetch purchase orders from Laravel
        from("direct:fetch-purchase-orders-from-laravel")
            .routeId("fetch-purchase-orders-laravel")
            .setHeader("Authorization", constant("Bearer {{integration.laravel.api-token}}"))
            .setHeader("Accept", constant("application/json"))
            .to("{{integration.laravel.base-url}}/api/purchase-orders")
            .unmarshal(jsonFormat);

        // Transform purchase orders for ERP
        from("direct:transform-purchase-orders-for-erp")
            .routeId("transform-purchase-orders-erp")
            .to("bean:accountingDataTransformer?method=transformPurchaseOrdersForErp");

        // Send purchase orders to Frappe
        from("direct:send-purchase-orders-to-frappe")
            .routeId("send-purchase-orders-frappe")
            .setHeader("Authorization", simple("token {{erp.frappe.api-key}}:{{erp.frappe.api-secret}}"))
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .split(jsonpath("$.data"))
                .marshal(jsonFormat)
                .to("http://{{erp.frappe.base-url}}/api/resource/Purchase Order?httpMethod=POST")
            .end()
            .log("Purchase orders sent to Frappe successfully");

        // Bidirectional sync for accounting data updates
        from("direct:fetch-accounting-updates-from-erp")
            .routeId("fetch-accounting-updates-erp")
            .log("Fetching accounting updates from ERP")
            .choice()
                .when(simple("{{erp.frappe.enabled}}"))
                    .to("direct:fetch-accounting-from-frappe")
            .end()
            .to("direct:send-accounting-updates-to-laravel");

        // Fetch accounting data from Frappe
        from("direct:fetch-accounting-from-frappe")
            .routeId("fetch-accounting-frappe")
            .setHeader("Authorization", simple("token {{erp.frappe.api-key}}:{{erp.frappe.api-secret}}"))
            .setHeader("Accept", constant("application/json"))
            .to("http://{{erp.frappe.base-url}}/api/resource/GL Entry?fields=[\"name\",\"account\",\"debit\",\"credit\",\"posting_date\",\"voucher_type\",\"voucher_no\"]")
            .unmarshal(jsonFormat);

        // Send accounting updates back to Laravel
        from("direct:send-accounting-updates-to-laravel")
            .routeId("send-accounting-updates-laravel")
            .to("bean:accountingDataTransformer?method=transformForLaravel")
            .setHeader("Authorization", constant("Bearer {{integration.laravel.api-token}}"))
            .setHeader("Accept", constant("application/json"))
            .setHeader("Content-Type", constant("application/json"))
            .marshal(jsonFormat)
            .to("{{integration.laravel.base-url}}/api/accounting/bulk-update?httpMethod=PUT")
            .log("Accounting updates sent to Laravel successfully");
    }
}