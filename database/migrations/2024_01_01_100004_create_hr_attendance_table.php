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
        Schema::create('hr_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->time('expected_check_in')->nullable();
            $table->time('expected_check_out')->nullable();
            
            // Break times
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->integer('break_duration_minutes')->default(0);
            
            // Calculated fields
            $table->integer('total_work_minutes')->nullable();
            $table->integer('overtime_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_departure_minutes')->default(0);
            
            // Status
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'on_leave', 'holiday'])->default('absent');
            $table->enum('attendance_type', ['regular', 'overtime', 'weekend', 'holiday'])->default('regular');
            
            // Location and device info
            $table->string('check_in_location')->nullable();
            $table->string('check_out_location')->nullable();
            $table->string('check_in_device')->nullable();
            $table->string('check_out_device')->nullable();
            $table->decimal('check_in_latitude', 10, 8)->nullable();
            $table->decimal('check_in_longitude', 11, 8)->nullable();
            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            
            // Notes and approvals
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->unique(['employee_id', 'date']);
            $table->index(['date', 'status']);
            $table->index(['employee_id', 'date', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_attendance');
    }
};