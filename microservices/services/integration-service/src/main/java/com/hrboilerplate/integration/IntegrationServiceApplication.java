package com.hrboilerplate.integration;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.scheduling.annotation.EnableScheduling;

/**
 * Main Spring Boot application class for the ERP Integration Service.
 * This service provides Apache Camel-based integration with legacy ERP systems
 * like Frappe for accounting and payroll data synchronization.
 */
@SpringBootApplication
@EnableScheduling
public class IntegrationServiceApplication {

    public static void main(String[] args) {
        SpringApplication.run(IntegrationServiceApplication.class, args);
    }
}