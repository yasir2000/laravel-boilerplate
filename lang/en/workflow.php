<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Language Lines
    |--------------------------------------------------------------------------
    */

    'created' => 'Workflow created successfully.',
    'updated' => 'Workflow updated successfully.',
    'deleted' => 'Workflow deleted successfully.',
    'started' => 'Workflow started successfully.',
    'completed' => 'Workflow completed successfully.',
    'cancelled' => 'Workflow cancelled successfully.',
    'step_completed' => 'Workflow step completed successfully.',
    'step_assigned' => 'Workflow step assigned successfully.',
    'action_recorded' => 'Action recorded successfully.',
    'approved' => 'Approved successfully.',
    'rejected' => 'Rejected successfully.',
    'delegated' => 'Delegated successfully.',
    'comment_added' => 'Comment added successfully.',
    'changes_requested' => 'Changes requested successfully.',
    'not_authorized' => 'You are not authorized to perform this action.',
    'not_found' => 'Workflow not found.',
    'step_not_found' => 'Workflow step not found.',
    'already_completed' => 'This workflow step is already completed.',
    'invalid_action' => 'Invalid action for this workflow step.',
    'assignee_required' => 'An assignee is required for this step.',
    'delegation_not_allowed' => 'Delegation is not allowed for this step.',

    'types' => [
        'document_approval' => 'Document Approval',
        'expense_approval' => 'Expense Approval',
        'leave_request' => 'Leave Request',
        'project_approval' => 'Project Approval',
        'purchase_order' => 'Purchase Order',
        'custom' => 'Custom Workflow',
    ],

    'statuses' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
    ],

    'step_types' => [
        'approval' => 'Approval',
        'review' => 'Review',
        'notification' => 'Notification',
        'condition' => 'Condition',
        'parallel' => 'Parallel',
        'sequential' => 'Sequential',
        'custom' => 'Custom',
    ],

    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
        'delegate' => 'Delegate',
        'comment' => 'Comment',
        'request_changes' => 'Request Changes',
        'complete' => 'Complete',
    ],
];
