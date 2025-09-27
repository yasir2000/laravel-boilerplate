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
        Schema::create('hr_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('request_number', 20)->unique();
            
            // Leave details
            $table->enum('leave_type', [
                'vacation', 'sick', 'personal', 'maternity', 'paternity', 
                'emergency', 'bereavement', 'unpaid', 'compensatory'
            ]);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_requested');
            $table->boolean('is_half_day')->default(false);
            $table->enum('half_day_type', ['morning', 'afternoon'])->nullable();
            
            // Request information
            $table->text('reason');
            $table->text('comments')->nullable();
            $table->json('attachments')->nullable(); // Supporting documents
            $table->datetime('requested_at');
            
            // Approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('review_comments')->nullable();
            $table->datetime('reviewed_at')->nullable();
            
            // Coverage information
            $table->unsignedBigInteger('coverage_employee_id')->nullable();
            $table->text('coverage_notes')->nullable();
            
            // System fields
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('coverage_employee_id')->references('id')->on('hr_employees')->onDelete('set null');
            
            // Indexes
            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
            $table->index('leave_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_leave_requests');
    }
};