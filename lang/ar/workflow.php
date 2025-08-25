<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Arabic Workflow Language Lines
    |--------------------------------------------------------------------------
    */

    'created' => 'تم إنشاء سير العمل بنجاح.',
    'updated' => 'تم تحديث سير العمل بنجاح.',
    'deleted' => 'تم حذف سير العمل بنجاح.',
    'started' => 'تم بدء سير العمل بنجاح.',
    'completed' => 'تم إكمال سير العمل بنجاح.',
    'cancelled' => 'تم إلغاء سير العمل بنجاح.',
    'step_completed' => 'تم إكمال خطوة سير العمل بنجاح.',
    'step_assigned' => 'تم تعيين خطوة سير العمل بنجاح.',
    'action_recorded' => 'تم تسجيل الإجراء بنجاح.',
    'approved' => 'تم الموافقة بنجاح.',
    'rejected' => 'تم الرفض بنجاح.',
    'delegated' => 'تم التفويض بنجاح.',
    'comment_added' => 'تم إضافة التعليق بنجاح.',
    'changes_requested' => 'تم طلب التغييرات بنجاح.',
    'not_authorized' => 'غير مخول لك لتنفيذ هذا الإجراء.',
    'not_found' => 'سير العمل غير موجود.',
    'step_not_found' => 'خطوة سير العمل غير موجودة.',
    'already_completed' => 'خطوة سير العمل هذه مكتملة بالفعل.',
    'invalid_action' => 'إجراء غير صالح لخطوة سير العمل هذه.',
    'assignee_required' => 'مطلوب تعيين شخص مسؤول لهذه الخطوة.',
    'delegation_not_allowed' => 'التفويض غير مسموح لهذه الخطوة.',

    'types' => [
        'document_approval' => 'موافقة الوثيقة',
        'expense_approval' => 'موافقة المصروفات',
        'leave_request' => 'طلب إجازة',
        'project_approval' => 'موافقة المشروع',
        'purchase_order' => 'أمر الشراء',
        'custom' => 'سير عمل مخصص',
    ],

    'statuses' => [
        'draft' => 'مسودة',
        'pending' => 'في الانتظار',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
        'rejected' => 'مرفوض',
    ],

    'step_types' => [
        'approval' => 'موافقة',
        'review' => 'مراجعة',
        'notification' => 'إشعار',
        'condition' => 'شرط',
        'parallel' => 'متوازي',
        'sequential' => 'متسلسل',
        'custom' => 'مخصص',
    ],

    'actions' => [
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'delegate' => 'تفويض',
        'comment' => 'تعليق',
        'request_changes' => 'طلب تغييرات',
        'complete' => 'إكمال',
    ],
];
