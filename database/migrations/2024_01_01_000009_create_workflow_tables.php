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
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->string('type');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['type', 'is_active']);
            $table->index('key');
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_definition_id');
            $table->morphs('subject');
            $table->string('status');
            $table->json('context')->nullable();
            $table->json('variables')->nullable();
            $table->uuid('created_by');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('workflow_definition_id')->references('id')->on('workflow_definitions');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['status', 'created_at']);
            $table->index('workflow_definition_id');
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_instance_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['approval', 'review', 'notification', 'condition', 'parallel', 'sequential', 'custom']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped', 'cancelled']);
            $table->json('config')->nullable();
            $table->integer('order')->default(0);
            $table->uuid('assignee_id')->nullable();
            $table->uuid('assigned_by')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('workflow_instance_id')->references('id')->on('workflow_instances')->onDelete('cascade');
            $table->foreign('assignee_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['workflow_instance_id', 'order']);
            $table->index(['assignee_id', 'status']);
            $table->index(['status', 'due_date']);
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_step_id');
            $table->uuid('user_id');
            $table->enum('action', ['approve', 'reject', 'delegate', 'comment', 'request_changes', 'complete']);
            $table->text('comment')->nullable();
            $table->json('data')->nullable();
            $table->uuid('delegated_to')->nullable();
            $table->timestamps();

            $table->foreign('workflow_step_id')->references('id')->on('workflow_steps')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('delegated_to')->references('id')->on('users')->onDelete('set null');
            $table->index(['workflow_step_id', 'created_at']);
            $table->index(['user_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_definitions');
    }
};
