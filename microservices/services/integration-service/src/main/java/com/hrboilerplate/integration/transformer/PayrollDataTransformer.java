package com.hrboilerplate.integration.transformer;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.node.ArrayNode;
import com.fasterxml.jackson.databind.node.ObjectNode;
import org.springframework.stereotype.Component;
import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

/**
 * Data transformer for payroll synchronization between Laravel HR system and ERP systems.
 */
@Component("payrollDataTransformer")
public class PayrollDataTransformer {

    private final ObjectMapper objectMapper = new ObjectMapper();

    /**
     * Transform payroll data from Laravel format to ERP format.
     */
    public String transformForErp(String laravelPayrollData) throws Exception {
        JsonNode laravelData = objectMapper.readTree(laravelPayrollData);
        ObjectNode erpData = objectMapper.createObjectNode();
        ArrayNode payrollRecords = objectMapper.createArrayNode();

        JsonNode payrollArray = laravelData.has("data") ? laravelData.get("data") : laravelData;

        if (payrollArray.isArray()) {
            for (JsonNode payroll : payrollArray) {
                ObjectNode erpPayroll = transformSinglePayrollForErp(payroll);
                payrollRecords.add(erpPayroll);
            }
        }

        erpData.set("salary_slips", payrollRecords);
        return objectMapper.writeValueAsString(erpData);
    }

    /**
     * Transform a single payroll record for ERP system.
     */
    private ObjectNode transformSinglePayrollForErp(JsonNode laravelPayroll) {
        ObjectNode erpPayroll = objectMapper.createObjectNode();

        // Basic payroll information
        erpPayroll.put("employee", getStringValue(laravelPayroll, "employee_id"));
        erpPayroll.put("employee_name", getStringValue(laravelPayroll, "employee_name"));
        erpPayroll.put("designation", getStringValue(laravelPayroll, "designation"));
        erpPayroll.put("department", getStringValue(laravelPayroll, "department"));
        erpPayroll.put("company", getStringValue(laravelPayroll, "company", "Default Company"));

        // Pay period
        String payPeriod = getStringValue(laravelPayroll, "pay_period");
        if (!payPeriod.isEmpty()) {
            erpPayroll.put("posting_date", formatDate(payPeriod));
            erpPayroll.put("start_date", formatDate(getStringValue(laravelPayroll, "period_start")));
            erpPayroll.put("end_date", formatDate(getStringValue(laravelPayroll, "period_end")));
        }

        // Salary structure
        erpPayroll.put("salary_structure", getStringValue(laravelPayroll, "salary_structure", "Default Salary Structure"));

        // Earnings
        ObjectNode earnings = objectMapper.createObjectNode();
        earnings.put("basic_salary", getDecimalValue(laravelPayroll, "basic_salary"));
        earnings.put("house_rent_allowance", getDecimalValue(laravelPayroll, "house_allowance"));
        earnings.put("transport_allowance", getDecimalValue(laravelPayroll, "transport_allowance"));
        earnings.put("medical_allowance", getDecimalValue(laravelPayroll, "medical_allowance"));
        earnings.put("overtime_pay", getDecimalValue(laravelPayroll, "overtime_pay"));
        earnings.put("bonus", getDecimalValue(laravelPayroll, "bonus"));
        earnings.put("other_allowances", getDecimalValue(laravelPayroll, "other_allowances"));
        
        erpPayroll.set("earnings", earnings);

        // Deductions
        ObjectNode deductions = objectMapper.createObjectNode();
        deductions.put("income_tax", getDecimalValue(laravelPayroll, "income_tax"));
        deductions.put("provident_fund", getDecimalValue(laravelPayroll, "provident_fund"));
        deductions.put("professional_tax", getDecimalValue(laravelPayroll, "professional_tax"));
        deductions.put("loan_deduction", getDecimalValue(laravelPayroll, "loan_deduction"));
        deductions.put("other_deductions", getDecimalValue(laravelPayroll, "other_deductions"));
        
        erpPayroll.set("deductions", deductions);

        // Totals
        erpPayroll.put("gross_pay", getDecimalValue(laravelPayroll, "gross_pay"));
        erpPayroll.put("total_deduction", getDecimalValue(laravelPayroll, "total_deductions"));
        erpPayroll.put("net_pay", getDecimalValue(laravelPayroll, "net_pay"));

        // Working days
        erpPayroll.put("total_working_days", getIntValue(laravelPayroll, "working_days"));
        erpPayroll.put("payment_days", getIntValue(laravelPayroll, "payment_days"));
        erpPayroll.put("leave_without_pay", getIntValue(laravelPayroll, "unpaid_days"));

        // Status
        erpPayroll.put("docstatus", mapPayrollStatus(getStringValue(laravelPayroll, "status")));

        // Bank details
        if (laravelPayroll.has("bank_account")) {
            erpPayroll.put("bank_name", getStringValue(laravelPayroll, "bank_name"));
            erpPayroll.put("bank_account_no", getStringValue(laravelPayroll, "bank_account"));
        }

        return erpPayroll;
    }

    /**
     * Transform payroll data from ERP format to Laravel format.
     */
    public String transformForLaravel(String erpPayrollData) throws Exception {
        JsonNode erpData = objectMapper.readTree(erpPayrollData);
        ObjectNode laravelData = objectMapper.createObjectNode();
        ArrayNode payrollRecords = objectMapper.createArrayNode();

        JsonNode payrollArray = erpData.has("data") ? erpData.get("data") : erpData;

        if (payrollArray.isArray()) {
            for (JsonNode payroll : payrollArray) {
                ObjectNode laravelPayroll = transformSinglePayrollForLaravel(payroll);
                payrollRecords.add(laravelPayroll);
            }
        }

        laravelData.set("payroll", payrollRecords);
        return objectMapper.writeValueAsString(laravelData);
    }

    /**
     * Transform a single payroll record for Laravel system.
     */
    private ObjectNode transformSinglePayrollForLaravel(JsonNode erpPayroll) {
        ObjectNode laravelPayroll = objectMapper.createObjectNode();

        // Basic information
        laravelPayroll.put("employee_id", getStringValue(erpPayroll, "employee"));
        laravelPayroll.put("employee_name", getStringValue(erpPayroll, "employee_name"));
        laravelPayroll.put("designation", getStringValue(erpPayroll, "designation"));
        laravelPayroll.put("department", getStringValue(erpPayroll, "department"));

        // Pay period
        laravelPayroll.put("pay_period", getStringValue(erpPayroll, "posting_date"));
        laravelPayroll.put("period_start", getStringValue(erpPayroll, "start_date"));
        laravelPayroll.put("period_end", getStringValue(erpPayroll, "end_date"));

        // Extract earnings
        if (erpPayroll.has("earnings")) {
            JsonNode earnings = erpPayroll.get("earnings");
            laravelPayroll.put("basic_salary", getDecimalValue(earnings, "basic_salary"));
            laravelPayroll.put("house_allowance", getDecimalValue(earnings, "house_rent_allowance"));
            laravelPayroll.put("transport_allowance", getDecimalValue(earnings, "transport_allowance"));
            laravelPayroll.put("medical_allowance", getDecimalValue(earnings, "medical_allowance"));
            laravelPayroll.put("overtime_pay", getDecimalValue(earnings, "overtime_pay"));
            laravelPayroll.put("bonus", getDecimalValue(earnings, "bonus"));
            laravelPayroll.put("other_allowances", getDecimalValue(earnings, "other_allowances"));
        }

        // Extract deductions
        if (erpPayroll.has("deductions")) {
            JsonNode deductions = erpPayroll.get("deductions");
            laravelPayroll.put("income_tax", getDecimalValue(deductions, "income_tax"));
            laravelPayroll.put("provident_fund", getDecimalValue(deductions, "provident_fund"));
            laravelPayroll.put("professional_tax", getDecimalValue(deductions, "professional_tax"));
            laravelPayroll.put("loan_deduction", getDecimalValue(deductions, "loan_deduction"));
            laravelPayroll.put("other_deductions", getDecimalValue(deductions, "other_deductions"));
        }

        // Totals
        laravelPayroll.put("gross_pay", getDecimalValue(erpPayroll, "gross_pay"));
        laravelPayroll.put("total_deductions", getDecimalValue(erpPayroll, "total_deduction"));
        laravelPayroll.put("net_pay", getDecimalValue(erpPayroll, "net_pay"));

        // Working days
        laravelPayroll.put("working_days", getIntValue(erpPayroll, "total_working_days"));
        laravelPayroll.put("payment_days", getIntValue(erpPayroll, "payment_days"));
        laravelPayroll.put("unpaid_days", getIntValue(erpPayroll, "leave_without_pay"));

        // Status
        laravelPayroll.put("status", mapStatusToLaravel(getIntValue(erpPayroll, "docstatus")));

        // Bank details
        laravelPayroll.put("bank_name", getStringValue(erpPayroll, "bank_name"));
        laravelPayroll.put("bank_account", getStringValue(erpPayroll, "bank_account_no"));

        // Timestamps
        laravelPayroll.put("updated_at", LocalDate.now().toString());

        return laravelPayroll;
    }

    /**
     * Helper methods for safe value extraction.
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

    private BigDecimal getDecimalValue(JsonNode node, String fieldName) {
        if (node.has(fieldName) && !node.get(fieldName).isNull()) {
            return new BigDecimal(node.get(fieldName).asText());
        }
        return BigDecimal.ZERO;
    }

    private int getIntValue(JsonNode node, String fieldName) {
        if (node.has(fieldName) && !node.get(fieldName).isNull()) {
            return node.get(fieldName).asInt();
        }
        return 0;
    }

    private String formatDate(String dateString) {
        try {
            LocalDate date = LocalDate.parse(dateString, DateTimeFormatter.ISO_LOCAL_DATE);
            return date.format(DateTimeFormatter.ISO_LOCAL_DATE);
        } catch (Exception e) {
            return dateString;
        }
    }

    /**
     * Map Laravel payroll status to ERP status.
     */
    private int mapPayrollStatus(String laravelStatus) {
        if (laravelStatus == null || laravelStatus.isEmpty()) {
            return 0; // Draft
        }
        
        switch (laravelStatus.toLowerCase()) {
            case "draft":
                return 0;
            case "submitted":
            case "approved":
                return 1;
            case "cancelled":
                return 2;
            default:
                return 0;
        }
    }

    /**
     * Map ERP status to Laravel status.
     */
    private String mapStatusToLaravel(int erpStatus) {
        switch (erpStatus) {
            case 0:
                return "draft";
            case 1:
                return "approved";
            case 2:
                return "cancelled";
            default:
                return "draft";
        }
    }
}