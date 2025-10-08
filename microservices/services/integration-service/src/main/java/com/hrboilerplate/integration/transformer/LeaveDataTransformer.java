package com.hrboilerplate.integration.transformer;

import org.springframework.stereotype.Service;
import java.util.Map;
import java.util.List;
import java.util.ArrayList;
import java.util.HashMap;

/**
 * Data transformer for leave application data between Laravel HR and ERP systems.
 */
@Service("leaveDataTransformer")
public class LeaveDataTransformer {

    /**
     * Transform leave data from Laravel HR format to ERP format
     */
    public Object transformForErp(Object laravelData) {
        if (laravelData instanceof List) {
            List<Object> transformedList = new ArrayList<>();
            for (Object item : (List<?>) laravelData) {
                transformedList.add(transformSingleLeaveForErp(item));
            }
            return transformedList;
        } else {
            return transformSingleLeaveForErp(laravelData);
        }
    }

    /**
     * Transform leave data from ERP format to Laravel HR format
     */
    public Object transformForLaravel(Object erpData) {
        if (erpData instanceof List) {
            List<Object> transformedList = new ArrayList<>();
            for (Object item : (List<?>) erpData) {
                transformedList.add(transformSingleLeaveForLaravel(item));
            }
            return transformedList;
        } else {
            return transformSingleLeaveForLaravel(erpData);
        }
    }

    private Object transformSingleLeaveForErp(Object laravelLeave) {
        if (!(laravelLeave instanceof Map)) {
            return laravelLeave;
        }

        Map<String, Object> leave = (Map<String, Object>) laravelLeave;
        Map<String, Object> erpLeave = new HashMap<>();

        // Map Laravel HR fields to ERP fields
        erpLeave.put("employee", leave.get("user_id"));
        erpLeave.put("leave_type", leave.get("type"));
        erpLeave.put("from_date", leave.get("start_date"));
        erpLeave.put("to_date", leave.get("end_date"));
        erpLeave.put("total_leave_days", leave.get("days"));
        erpLeave.put("description", leave.get("reason"));
        erpLeave.put("status", mapLeaveStatus(leave.get("status")));
        erpLeave.put("leave_application_name", leave.get("id"));
        erpLeave.put("posting_date", leave.get("created_at"));

        return erpLeave;
    }

    private Object transformSingleLeaveForLaravel(Object erpLeave) {
        if (!(erpLeave instanceof Map)) {
            return erpLeave;
        }

        Map<String, Object> leave = (Map<String, Object>) erpLeave;
        Map<String, Object> laravelLeave = new HashMap<>();

        // Map ERP fields to Laravel HR fields
        laravelLeave.put("user_id", leave.get("employee"));
        laravelLeave.put("type", leave.get("leave_type"));
        laravelLeave.put("start_date", leave.get("from_date"));
        laravelLeave.put("end_date", leave.get("to_date"));
        laravelLeave.put("days", leave.get("total_leave_days"));
        laravelLeave.put("reason", leave.get("description"));
        laravelLeave.put("status", mapLeaveStatusFromErp(leave.get("status")));
        laravelLeave.put("id", leave.get("leave_application_name"));
        laravelLeave.put("created_at", leave.get("posting_date"));

        return laravelLeave;
    }

    private String mapLeaveStatus(Object laravelStatus) {
        if (laravelStatus == null) return "Draft";
        
        String status = laravelStatus.toString().toLowerCase();
        switch (status) {
            case "pending":
                return "Open";
            case "approved":
                return "Approved";
            case "rejected":
                return "Rejected";
            case "cancelled":
                return "Cancelled";
            default:
                return "Draft";
        }
    }

    private String mapLeaveStatusFromErp(Object erpStatus) {
        if (erpStatus == null) return "pending";
        
        String status = erpStatus.toString().toLowerCase();
        switch (status) {
            case "open":
                return "pending";
            case "approved":
                return "approved";
            case "rejected":
                return "rejected";
            case "cancelled":
                return "cancelled";
            default:
                return "pending";
        }
    }
}