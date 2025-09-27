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
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('employee_id', 20)->unique();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('supervisor_id')->nullable();
            
            // Personal Information
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('middle_name', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('national_id', 50)->nullable();
            $table->string('passport_number', 50)->nullable();
            
            // Contact Information
            $table->string('personal_email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 50)->nullable();
            
            // Employment Information
            $table->date('hire_date');
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');
            $table->enum('employment_status', ['active', 'inactive', 'terminated', 'on_leave'])->default('active');
            $table->decimal('salary', 10, 2);
            $table->string('salary_currency', 3)->default('USD');
            $table->enum('salary_type', ['monthly', 'yearly', 'hourly'])->default('monthly');
            $table->decimal('hourly_rate', 8, 2)->nullable();
            
            // Work Schedule
            $table->json('work_schedule')->nullable(); // Store working hours, days
            $table->integer('work_hours_per_week')->default(40);
            $table->string('work_location', 100)->nullable();
            $table->boolean('remote_work_allowed')->default(false);
            
            // Benefits and Leave
            $table->integer('vacation_days_per_year')->default(21);
            $table->integer('sick_days_per_year')->default(10);
            $table->integer('vacation_days_used')->default(0);
            $table->integer('sick_days_used')->default(0);
            
            // Documents and Files
            $table->string('profile_photo')->nullable();
            $table->json('documents')->nullable(); // Store file paths
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();
            $table->json('education')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('hr_departments')->onDelete('restrict');
            $table->foreign('position_id')->references('id')->on('hr_positions')->onDelete('restrict');
            $table->foreign('supervisor_id')->references('id')->on('hr_employees')->onDelete('set null');
            
            // Indexes
            $table->index(['department_id', 'employment_status']);
            $table->index(['position_id', 'employment_status']);
            $table->index('supervisor_id');
            $table->index('hire_date');
            $table->index(['employment_status', 'employment_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
    }
};