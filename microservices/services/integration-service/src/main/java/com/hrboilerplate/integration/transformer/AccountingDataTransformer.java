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
 * Data transformer for accounting synchronization between Laravel HR system and ERP systems.
 */
@Component("accountingDataTransformer")
public class AccountingDataTransformer {

    private final ObjectMapper objectMapper = new ObjectMapper();

    /**
     * Transform chart of accounts from Laravel format to ERP format.
     */
    public String transformAccountsForErp(String laravelAccountsData) throws Exception {
        JsonNode laravelData = objectMapper.readTree(laravelAccountsData);
        ObjectNode erpData = objectMapper.createObjectNode();
        ArrayNode accounts = objectMapper.createArrayNode();

        JsonNode accountArray = laravelData.has("data") ? laravelData.get("data") : laravelData;

        if (accountArray.isArray()) {
            for (JsonNode account : accountArray) {
                ObjectNode erpAccount = transformSingleAccountForErp(account);
                accounts.add(erpAccount);
            }
        }

        erpData.set("accounts", accounts);
        return objectMapper.writeValueAsString(erpData);
    }

    /**
     * Transform a single account record for ERP system.
     */
    private ObjectNode transformSingleAccountForErp(JsonNode laravelAccount) {
        ObjectNode erpAccount = objectMapper.createObjectNode();

        // Basic account information
        erpAccount.put("account_name", getStringValue(laravelAccount, "name"));
        erpAccount.put("account_number", getStringValue(laravelAccount, "code"));
        erpAccount.put("account_type", mapAccountType(getStringValue(laravelAccount, "type")));
        erpAccount.put("parent_account", getStringValue(laravelAccount, "parent_account"));
        erpAccount.put("company", getStringValue(laravelAccount, "company", "Default Company"));

        // Account properties
        erpAccount.put("is_group", laravelAccount.has("is_group") ? laravelAccount.get("is_group").asBoolean() : false);
        erpAccount.put("disabled", laravelAccount.has("is_active") ? !laravelAccount.get("is_active").asBoolean() : false);

        // Root type mapping
        erpAccount.put("root_type", mapRootType(getStringValue(laravelAccount, "type")));

        // Additional properties
        erpAccount.put("account_currency", getStringValue(laravelAccount, "currency", "USD"));
        erpAccount.put("freeze_account", getStringValue(laravelAccount, "freeze_account", "No"));

        return erpAccount;
    }

    /**
     * Transform journal entries from Laravel format to ERP format.
     */
    public String transformJournalEntriesForErp(String laravelJournalData) throws Exception {
        JsonNode laravelData = objectMapper.readTree(laravelJournalData);
        ObjectNode erpData = objectMapper.createObjectNode();
        ArrayNode journalEntries = objectMapper.createArrayNode();

        JsonNode journalArray = laravelData.has("data") ? laravelData.get("data") : laravelData;

        if (journalArray.isArray()) {
            for (JsonNode journal : journalArray) {
                ObjectNode erpJournal = transformSingleJournalEntryForErp(journal);
                journalEntries.add(erpJournal);
            }
        }

        erpData.set("journal_entries", journalEntries);
        return objectMapper.writeValueAsString(erpData);
    }

    /**
     * Transform a single journal entry for ERP system.
     */
    private ObjectNode transformSingleJournalEntryForErp(JsonNode laravelJournal) {
        ObjectNode erpJournal = objectMapper.createObjectNode();

        // Basic journal entry information
        erpJournal.put("title", getStringValue(laravelJournal, "description"));
        erpJournal.put("voucher_type", "Journal Entry");
        erpJournal.put("naming_series", "JV-");
        erpJournal.put("company", getStringValue(laravelJournal, "company", "Default Company"));
        erpJournal.put("posting_date", formatDate(getStringValue(laravelJournal, "transaction_date")));

        // Journal entry accounts
        ArrayNode accounts = objectMapper.createArrayNode();
        
        if (laravelJournal.has("entries") && laravelJournal.get("entries").isArray()) {
            for (JsonNode entry : laravelJournal.get("entries")) {
                ObjectNode accountEntry = objectMapper.createObjectNode();
                accountEntry.put("account", getStringValue(entry, "account_code"));
                accountEntry.put("debit_in_account_currency", getDecimalValue(entry, "debit_amount"));
                accountEntry.put("credit_in_account_currency", getDecimalValue(entry, "credit_amount"));
                accountEntry.put("user_remark", getStringValue(entry, "description"));
                accounts.add(accountEntry);
            }
        }

        erpJournal.set("accounts", accounts);

        // Additional fields
        erpJournal.put("user_remark", getStringValue(laravelJournal, "notes"));
        erpJournal.put("docstatus", mapJournalStatus(getStringValue(laravelJournal, "status")));

        return erpJournal;
    }

    /**
     * Transform expense claims from Laravel format to ERP format.
     */
    public String transformExpenseClaimsForErp(String laravelExpenseData) throws Exception {
        JsonNode laravelData = objectMapper.readTree(laravelExpenseData);
        ObjectNode erpData = objectMapper.createObjectNode();
        ArrayNode expenseClaims = objectMapper.createArrayNode();

        JsonNode expenseArray = laravelData.has("data") ? laravelData.get("data") : laravelData;

        if (expenseArray.isArray()) {
            for (JsonNode expense : expenseArray) {
                ObjectNode erpExpense = transformSingleExpenseClaimForErp(expense);
                expenseClaims.add(erpExpense);
            }
        }

        erpData.set("expense_claims", expenseClaims);
        return objectMapper.writeValueAsString(erpData);
    }

    /**
     * Transform a single expense claim for ERP system.
     */
    private ObjectNode transformSingleExpenseClaimForErp(JsonNode laravelExpense) {
        ObjectNode erpExpense = objectMapper.createObjectNode();

        // Basic expense claim information
        erpExpense.put("employee", getStringValue(laravelExpense, "employee_id"));
        erpExpense.put("employee_name", getStringValue(laravelExpense, "employee_name"));
        erpExpense.put("posting_date", formatDate(getStringValue(laravelExpense, "claim_date")));
        erpExpense.put("expense_approver", getStringValue(laravelExpense, "approver_id"));
        erpExpense.put("company", getStringValue(laravelExpense, "company", "Default Company"));

        // Expense details
        ArrayNode expenses = objectMapper.createArrayNode();
        
        if (laravelExpense.has("items") && laravelExpense.get("items").isArray()) {
            for (JsonNode item : laravelExpense.get("items")) {
                ObjectNode expenseDetail = objectMapper.createObjectNode();
                expenseDetail.put("expense_date", formatDate(getStringValue(item, "expense_date")));
                expenseDetail.put("expense_type", getStringValue(item, "category"));
                expenseDetail.put("description", getStringValue(item, "description"));
                expenseDetail.put("amount", getDecimalValue(item, "amount"));
                expenseDetail.put("sanctioned_amount", getDecimalValue(item, "approved_amount"));
                expenses.add(expenseDetail);
            }
        }

        erpExpense.set("expenses", expenses);

        // Totals
        erpExpense.put("total_claimed_amount", getDecimalValue(laravelExpense, "total_amount"));
        erpExpense.put("total_sanctioned_amount", getDecimalValue(laravelExpense, "approved_amount"));

        // Status
        erpExpense.put("approval_status", mapExpenseStatus(getStringValue(laravelExpense, "status")));
        erpExpense.put("docstatus", mapExpenseDocStatus(getStringValue(laravelExpense, "status")));

        return erpExpense;
    }

    /**
     * Transform purchase orders from Laravel format to ERP format.
     */
    public String transformPurchaseOrdersForErp(String laravelPurchaseData) throws Exception {
        JsonNode laravelData = objectMapper.readTree(laravelPurchaseData);
        ObjectNode erpData = objectMapper.createObjectNode();
        ArrayNode purchaseOrders = objectMapper.createArrayNode();

        JsonNode purchaseArray = laravelData.has("data") ? laravelData.get("data") : laravelData;

        if (purchaseArray.isArray()) {
            for (JsonNode purchase : purchaseArray) {
                ObjectNode erpPurchase = transformSinglePurchaseOrderForErp(purchase);
                purchaseOrders.add(erpPurchase);
            }
        }

        erpData.set("purchase_orders", purchaseOrders);
        return objectMapper.writeValueAsString(erpData);
    }

    /**
     * Transform a single purchase order for ERP system.
     */
    private ObjectNode transformSinglePurchaseOrderForErp(JsonNode laravelPurchase) {
        ObjectNode erpPurchase = objectMapper.createObjectNode();

        // Basic purchase order information
        erpPurchase.put("supplier", getStringValue(laravelPurchase, "vendor_name"));
        erpPurchase.put("transaction_date", formatDate(getStringValue(laravelPurchase, "order_date")));
        erpPurchase.put("company", getStringValue(laravelPurchase, "company", "Default Company"));
        erpPurchase.put("currency", getStringValue(laravelPurchase, "currency", "USD"));

        // Purchase order items
        ArrayNode items = objectMapper.createArrayNode();
        
        if (laravelPurchase.has("items") && laravelPurchase.get("items").isArray()) {
            for (JsonNode item : laravelPurchase.get("items")) {
                ObjectNode purchaseItem = objectMapper.createObjectNode();
                purchaseItem.put("item_code", getStringValue(item, "product_code"));
                purchaseItem.put("item_name", getStringValue(item, "product_name"));
                purchaseItem.put("description", getStringValue(item, "description"));
                purchaseItem.put("qty", getDecimalValue(item, "quantity"));
                purchaseItem.put("rate", getDecimalValue(item, "unit_price"));
                purchaseItem.put("amount", getDecimalValue(item, "total_price"));
                purchaseItem.put("schedule_date", formatDate(getStringValue(item, "delivery_date")));
                items.add(purchaseItem);
            }
        }

        erpPurchase.set("items", items);

        // Totals
        erpPurchase.put("total", getDecimalValue(laravelPurchase, "subtotal"));
        erpPurchase.put("grand_total", getDecimalValue(laravelPurchase, "total_amount"));

        // Status
        erpPurchase.put("docstatus", mapPurchaseStatus(getStringValue(laravelPurchase, "status")));

        return erpPurchase;
    }

    /**
     * Transform accounting data from ERP format to Laravel format.
     */
    public String transformForLaravel(String erpAccountingData) throws Exception {
        JsonNode erpData = objectMapper.readTree(erpAccountingData);
        ObjectNode laravelData = objectMapper.createObjectNode();
        ArrayNode glEntries = objectMapper.createArrayNode();

        JsonNode entryArray = erpData.has("data") ? erpData.get("data") : erpData;

        if (entryArray.isArray()) {
            for (JsonNode entry : entryArray) {
                ObjectNode laravelEntry = transformSingleGLEntryForLaravel(entry);
                glEntries.add(laravelEntry);
            }
        }

        laravelData.set("gl_entries", glEntries);
        return objectMapper.writeValueAsString(laravelData);
    }

    /**
     * Transform a single GL entry for Laravel system.
     */
    private ObjectNode transformSingleGLEntryForLaravel(JsonNode erpEntry) {
        ObjectNode laravelEntry = objectMapper.createObjectNode();

        laravelEntry.put("account_code", getStringValue(erpEntry, "account"));
        laravelEntry.put("debit_amount", getDecimalValue(erpEntry, "debit"));
        laravelEntry.put("credit_amount", getDecimalValue(erpEntry, "credit"));
        laravelEntry.put("transaction_date", getStringValue(erpEntry, "posting_date"));
        laravelEntry.put("voucher_type", getStringValue(erpEntry, "voucher_type"));
        laravelEntry.put("voucher_no", getStringValue(erpEntry, "voucher_no"));
        laravelEntry.put("updated_at", LocalDate.now().toString());

        return laravelEntry;
    }

    /**
     * Helper methods for safe value extraction and mapping.
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

    private String formatDate(String dateString) {
        try {
            LocalDate date = LocalDate.parse(dateString, DateTimeFormatter.ISO_LOCAL_DATE);
            return date.format(DateTimeFormatter.ISO_LOCAL_DATE);
        } catch (Exception e) {
            return dateString;
        }
    }

    private String mapAccountType(String laravelType) {
        switch (laravelType.toLowerCase()) {
            case "asset": return "Asset";
            case "liability": return "Liability";
            case "equity": return "Equity";
            case "income": return "Income";
            case "expense": return "Expense";
            default: return "Asset";
        }
    }

    private String mapRootType(String laravelType) {
        switch (laravelType.toLowerCase()) {
            case "asset": return "Asset";
            case "liability": return "Liability";
            case "equity": return "Equity";
            case "income": return "Income";
            case "expense": return "Expense";
            default: return "Asset";
        }
    }

    private int mapJournalStatus(String status) {
        switch (status.toLowerCase()) {
            case "draft": return 0;
            case "submitted": return 1;
            case "cancelled": return 2;
            default: return 0;
        }
    }

    private String mapExpenseStatus(String status) {
        switch (status.toLowerCase()) {
            case "pending": return "Draft";
            case "approved": return "Approved";
            case "rejected": return "Rejected";
            case "paid": return "Paid";
            default: return "Draft";
        }
    }

    private int mapExpenseDocStatus(String status) {
        switch (status.toLowerCase()) {
            case "pending": return 0;
            case "approved": return 1;
            case "rejected": return 2;
            default: return 0;
        }
    }

    private int mapPurchaseStatus(String status) {
        switch (status.toLowerCase()) {
            case "draft": return 0;
            case "submitted": return 1;
            case "cancelled": return 2;
            default: return 0;
        }
    }
}