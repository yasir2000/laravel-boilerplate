package com.hrboilerplate.integration.transformer;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.node.ArrayNode;
import com.fasterxml.jackson.databind.node.ObjectNode;
import org.springframework.stereotype.Component;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

/**
 * Data transformer for employee synchronization between Laravel HR system and ERP systems.
 */
@Component("employeeDataTransformer")
public class EmployeeDataTransformer {

    private final ObjectMapper objectMapper = new ObjectMapper();

    /**
     * Transform employee data from Laravel format to ERP format.
     */
    public String transformForErp(String laravelEmployeeData) throws Exception {
        JsonNode laravelData = objectMapper.readTree(laravelEmployeeData);
        ObjectNode erpData = objectMapper.createObjectNode();
        ArrayNode employees = objectMapper.createArrayNode();

        // Check if data is wrapped in a 'data' field
        JsonNode employeeArray = laravelData.has("data") ? laravelData.get("data") : laravelData;

        if (employeeArray.isArray()) {
            for (JsonNode employee : employeeArray) {
                ObjectNode erpEmployee = transformSingleEmployeeForErp(employee);
                employees.add(erpEmployee);
            }
        }

        erpData.set("employees", employees);
        return objectMapper.writeValueAsString(erpData);
    }

    /**
     * Transform a single employee record for ERP system.
     */
    private ObjectNode transformSingleEmployeeForErp(JsonNode laravelEmployee) {
        ObjectNode erpEmployee = objectMapper.createObjectNode();

        // Basic employee information
        erpEmployee.put("employee_number", getStringValue(laravelEmployee, "employee_id"));
        erpEmployee.put("employee_name", getStringValue(laravelEmployee, "full_name"));
        erpEmployee.put("first_name", getStringValue(laravelEmployee, "first_name"));
        erpEmployee.put("last_name", getStringValue(laravelEmployee, "last_name"));
        erpEmployee.put("middle_name", getStringValue(laravelEmployee, "middle_name"));

        // Contact information
        erpEmployee.put("personal_email", getStringValue(laravelEmployee, "email"));
        erpEmployee.put("cell_number", getStringValue(laravelEmployee, "phone"));
        erpEmployee.put("current_address", getStringValue(laravelEmployee, "address"));

        // Employment details
        erpEmployee.put("designation", getStringValue(laravelEmployee, "position"));
        erpEmployee.put("department", getStringValue(laravelEmployee, "department"));
        erpEmployee.put("company", getStringValue(laravelEmployee, "company", "Default Company"));
        erpEmployee.put("employment_type", getStringValue(laravelEmployee, "employment_type", "Full-time"));
        
        // Dates
        String joinDate = getStringValue(laravelEmployee, "hire_date");
        if (!joinDate.isEmpty()) {
            erpEmployee.put("date_of_joining", formatDate(joinDate));
        }

        String birthDate = getStringValue(laravelEmployee, "birth_date");
        if (!birthDate.isEmpty()) {
            erpEmployee.put("date_of_birth", formatDate(birthDate));
        }

        // Status
        String status = getStringValue(laravelEmployee, "status");
        erpEmployee.put("status", mapEmployeeStatus(status));

        // Salary information
        if (laravelEmployee.has("salary")) {
            erpEmployee.put("salary_amount", laravelEmployee.get("salary").asDouble());
        }

        // Manager information
        erpEmployee.put("reports_to", getStringValue(laravelEmployee, "manager_id"));

        // Gender
        erpEmployee.put("gender", getStringValue(laravelEmployee, "gender"));

        // Employee category
        erpEmployee.put("employee_category", getStringValue(laravelEmployee, "category", "Employee"));

        return erpEmployee;
    }

    /**
     * Transform employee data from ERP format to Laravel format.
     */
    public String transformForLaravel(String erpEmployeeData) throws Exception {
        JsonNode erpData = objectMapper.readTree(erpEmployeeData);
        ObjectNode laravelData = objectMapper.createObjectNode();
        ArrayNode employees = objectMapper.createArrayNode();

        // Check if data is wrapped in a 'data' field
        JsonNode employeeArray = erpData.has("data") ? erpData.get("data") : erpData;

        if (employeeArray.isArray()) {
            for (JsonNode employee : employeeArray) {
                ObjectNode laravelEmployee = transformSingleEmployeeForLaravel(employee);
                employees.add(laravelEmployee);
            }
        }

        laravelData.set("employees", employees);
        return objectMapper.writeValueAsString(laravelData);
    }

    /**
     * Transform a single employee record for Laravel system.
     */
    private ObjectNode transformSingleEmployeeForLaravel(JsonNode erpEmployee) {
        ObjectNode laravelEmployee = objectMapper.createObjectNode();

        // Basic information
        laravelEmployee.put("employee_id", getStringValue(erpEmployee, "employee_number"));
        laravelEmployee.put("first_name", getStringValue(erpEmployee, "first_name"));
        laravelEmployee.put("last_name", getStringValue(erpEmployee, "last_name"));
        laravelEmployee.put("middle_name", getStringValue(erpEmployee, "middle_name"));
        laravelEmployee.put("full_name", getStringValue(erpEmployee, "employee_name"));

        // Contact information
        laravelEmployee.put("email", getStringValue(erpEmployee, "personal_email"));
        laravelEmployee.put("phone", getStringValue(erpEmployee, "cell_number"));
        laravelEmployee.put("address", getStringValue(erpEmployee, "current_address"));

        // Employment details
        laravelEmployee.put("position", getStringValue(erpEmployee, "designation"));
        laravelEmployee.put("department", getStringValue(erpEmployee, "department"));
        laravelEmployee.put("employment_type", getStringValue(erpEmployee, "employment_type"));

        // Dates
        String joinDate = getStringValue(erpEmployee, "date_of_joining");
        if (!joinDate.isEmpty()) {
            laravelEmployee.put("hire_date", joinDate);
        }

        String birthDate = getStringValue(erpEmployee, "date_of_birth");
        if (!birthDate.isEmpty()) {
            laravelEmployee.put("birth_date", birthDate);
        }

        // Status
        String status = getStringValue(erpEmployee, "status");
        laravelEmployee.put("status", mapStatusToLaravel(status));

        // Salary
        if (erpEmployee.has("salary_amount")) {
            laravelEmployee.put("salary", erpEmployee.get("salary_amount").asDouble());
        }

        // Manager
        laravelEmployee.put("manager_id", getStringValue(erpEmployee, "reports_to"));

        // Gender
        laravelEmployee.put("gender", getStringValue(erpEmployee, "gender"));

        // Additional fields
        laravelEmployee.put("updated_at", LocalDate.now().toString());

        return laravelEmployee;
    }

    /**
     * Helper method to safely get string values from JSON.
     */
    private String getStringValue(JsonNode node, String fieldName) {
        return getStringValue(node, fieldName, "");
    }

    private String getStringValue(JsonNode node, String fieldName, String defaultValue) {
        if (node.has(fieldName) && !node.get(fieldName).isNull()) {
            return node.get(fieldName).asText();
        }
        return defaultValue;
    }

    /**
     * Format date for ERP system.
     */
    private String formatDate(String dateString) {
        try {
            // Assuming Laravel date format is YYYY-MM-DD
            LocalDate date = LocalDate.parse(dateString, DateTimeFormatter.ISO_LOCAL_DATE);
            return date.format(DateTimeFormatter.ISO_LOCAL_DATE);
        } catch (Exception e) {
            return dateString; // Return as-is if parsing fails
        }
    }

    /**
     * Map Laravel employee status to ERP status.
     */
    private String mapEmployeeStatus(String laravelStatus) {
        if (laravelStatus == null || laravelStatus.isEmpty()) {
            return "Active";
        }
        
        switch (laravelStatus.toLowerCase()) {
            case "active":
            case "1":
                return "Active";
            case "inactive":
            case "0":
                return "Inactive";
            case "terminated":
                return "Left";
            case "suspended":
                return "Suspended";
            default:
                return "Active";
        }
    }

    /**
     * Map ERP status to Laravel status.
     */
    private String mapStatusToLaravel(String erpStatus) {
        if (erpStatus == null || erpStatus.isEmpty()) {
            return "active";
        }
        
        switch (erpStatus.toLowerCase()) {
            case "active":
                return "active";
            case "inactive":
            case "left":
                return "inactive";
            case "suspended":
                return "suspended";
            default:
                return "active";
        }
    }
}