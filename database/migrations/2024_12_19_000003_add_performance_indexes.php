<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Optimize users table
        Schema::table('users', function (Blueprint $table) {
            $table->index(['email', 'company_id'], 'users_email_company_index');
            $table->index(['company_id', 'is_active'], 'users_company_active_index');
            $table->index(['created_at'], 'users_created_at_index');
            $table->index(['last_login_at'], 'users_last_login_index');
        });

        // Optimize hr_departments table
        if (Schema::hasTable('hr_departments')) {
            Schema::table('hr_departments', function (Blueprint $table) {
                $table->index(['company_id', 'is_active'], 'hr_departments_company_active_index');
                $table->index(['parent_id'], 'hr_departments_parent_index');
            });
        }

        // Optimize hr_employees table
        if (Schema::hasTable('hr_employees')) {
            Schema::table('hr_employees', function (Blueprint $table) {
                $table->index(['company_id', 'is_active'], 'hr_employees_company_active_index');
                $table->index(['department_id'], 'hr_employees_department_index');
                $table->index(['hire_date'], 'hr_employees_hire_date_index');
                $table->index(['status'], 'hr_employees_status_index');
            });
        }

        // Optimize hr_attendances table
        if (Schema::hasTable('hr_attendances')) {
            Schema::table('hr_attendances', function (Blueprint $table) {
                $table->index(['employee_id', 'date'], 'hr_attendances_employee_date_index');
                $table->index(['company_id', 'date'], 'hr_attendances_company_date_index');
                $table->index(['date', 'status'], 'hr_attendances_date_status_index');
            });
        }

        // Optimize hr_evaluations table
        if (Schema::hasTable('hr_evaluations')) {
            Schema::table('hr_evaluations', function (Blueprint $table) {
                $table->index(['employee_id', 'evaluation_period'], 'hr_evaluations_employee_period_index');
                $table->index(['company_id', 'status'], 'hr_evaluations_company_status_index');
                $table->index(['created_at'], 'hr_evaluations_created_at_index');
            });
        }

        // Optimize workflow_instances table
        if (Schema::hasTable('workflow_instances')) {
            Schema::table('workflow_instances', function (Blueprint $table) {
                $table->index(['definition_id', 'status'], 'workflow_instances_def_status_index');
                $table->index(['assignee_id', 'status'], 'workflow_instances_assignee_status_index');
                $table->index(['created_by', 'created_at'], 'workflow_instances_creator_date_index');
                $table->index(['due_date'], 'workflow_instances_due_date_index');
            });
        }

        // Optimize workflow_steps table
        if (Schema::hasTable('workflow_steps')) {
            Schema::table('workflow_steps', function (Blueprint $table) {
                $table->index(['instance_id', 'status'], 'workflow_steps_instance_status_index');
                $table->index(['assignee_id', 'status'], 'workflow_steps_assignee_status_index');
                $table->index(['due_date'], 'workflow_steps_due_date_index');
            });
        }

        // Optimize notifications table
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
            $table->index(['created_at'], 'notifications_created_at_index');
            $table->index(['type'], 'notifications_type_index');
        });

        // Optimize media table (Spatie Media Library)
        if (Schema::hasTable('media')) {
            Schema::table('media', function (Blueprint $table) {
                $table->index(['model_type', 'model_id'], 'media_model_index');
                $table->index(['collection_name'], 'media_collection_index');
                $table->index(['created_at'], 'media_created_at_index');
            });
        }

        // Optimize model_has_permissions table (Spatie Permission)
        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->index(['model_type', 'model_id'], 'model_has_permissions_model_index');
            });
        }

        // Optimize model_has_roles table (Spatie Permission)
        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->index(['model_type', 'model_id'], 'model_has_roles_model_index');
            });
        }

        // Optimize sessions table
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->index(['user_id'], 'sessions_user_id_index');
                $table->index(['last_activity'], 'sessions_last_activity_index');
            });
        }

        // Optimize failed_jobs table
        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->index(['failed_at'], 'failed_jobs_failed_at_index');
                $table->index(['queue'], 'failed_jobs_queue_index');
            });
        }

        // Optimize jobs table
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->index(['queue', 'reserved_at'], 'jobs_queue_reserved_index');
                $table->index(['available_at'], 'jobs_available_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_company_index');
            $table->dropIndex('users_company_active_index');
            $table->dropIndex('users_created_at_index');
            $table->dropIndex('users_last_login_index');
        });

        // Drop indexes from hr_departments table
        if (Schema::hasTable('hr_departments')) {
            Schema::table('hr_departments', function (Blueprint $table) {
                $table->dropIndex('hr_departments_company_active_index');
                $table->dropIndex('hr_departments_parent_index');
            });
        }

        // Drop indexes from hr_employees table
        if (Schema::hasTable('hr_employees')) {
            Schema::table('hr_employees', function (Blueprint $table) {
                $table->dropIndex('hr_employees_company_active_index');
                $table->dropIndex('hr_employees_department_index');
                $table->dropIndex('hr_employees_hire_date_index');
                $table->dropIndex('hr_employees_status_index');
            });
        }

        // Drop indexes from hr_attendances table
        if (Schema::hasTable('hr_attendances')) {
            Schema::table('hr_attendances', function (Blueprint $table) {
                $table->dropIndex('hr_attendances_employee_date_index');
                $table->dropIndex('hr_attendances_company_date_index');
                $table->dropIndex('hr_attendances_date_status_index');
            });
        }

        // Drop indexes from hr_evaluations table
        if (Schema::hasTable('hr_evaluations')) {
            Schema::table('hr_evaluations', function (Blueprint $table) {
                $table->dropIndex('hr_evaluations_employee_period_index');
                $table->dropIndex('hr_evaluations_company_status_index');
                $table->dropIndex('hr_evaluations_created_at_index');
            });
        }

        // Drop indexes from workflow_instances table
        if (Schema::hasTable('workflow_instances')) {
            Schema::table('workflow_instances', function (Blueprint $table) {
                $table->dropIndex('workflow_instances_def_status_index');
                $table->dropIndex('workflow_instances_assignee_status_index');
                $table->dropIndex('workflow_instances_creator_date_index');
                $table->dropIndex('workflow_instances_due_date_index');
            });
        }

        // Drop indexes from workflow_steps table
        if (Schema::hasTable('workflow_steps')) {
            Schema::table('workflow_steps', function (Blueprint $table) {
                $table->dropIndex('workflow_steps_instance_status_index');
                $table->dropIndex('workflow_steps_assignee_status_index');
                $table->dropIndex('workflow_steps_due_date_index');
            });
        }

        // Drop indexes from notifications table
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_index');
            $table->dropIndex('notifications_created_at_index');
            $table->dropIndex('notifications_type_index');
        });

        // Drop indexes from media table
        if (Schema::hasTable('media')) {
            Schema::table('media', function (Blueprint $table) {
                $table->dropIndex('media_model_index');
                $table->dropIndex('media_collection_index');
                $table->dropIndex('media_created_at_index');
            });
        }

        // Drop indexes from model_has_permissions table
        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->dropIndex('model_has_permissions_model_index');
            });
        }

        // Drop indexes from model_has_roles table
        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->dropIndex('model_has_roles_model_index');
            });
        }

        // Drop indexes from sessions table
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropIndex('sessions_user_id_index');
                $table->dropIndex('sessions_last_activity_index');
            });
        }

        // Drop indexes from failed_jobs table
        if (Schema::hasTable('failed_jobs')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->dropIndex('failed_jobs_failed_at_index');
                $table->dropIndex('failed_jobs_queue_index');
            });
        }

        // Drop indexes from jobs table
        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->dropIndex('jobs_queue_reserved_index');
                $table->dropIndex('jobs_available_at_index');
            });
        }
    }
};