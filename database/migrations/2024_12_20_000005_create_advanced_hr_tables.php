<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('document_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_name');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired', 'archived'])->default('pending');
            $table->enum('access_level', ['public', 'internal', 'confidential', 'restricted'])->default('internal');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'document_type']);
            $table->index(['status', 'is_verified']);
            $table->index('expiry_date');
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('pay_date');
            $table->enum('status', ['draft', 'pending', 'processing', 'processed', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->enum('type', ['weekly', 'bi_weekly', 'monthly', 'quarterly', 'annual'])->default('monthly');
            $table->integer('year');
            $table->integer('month')->nullable();
            $table->integer('week_number')->nullable();
            $table->integer('total_employees')->default(0);
            $table->decimal('total_gross_pay', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net_pay', 15, 2)->default(0);
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['year', 'month']);
            $table->index(['status', 'type']);
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('employee_number');
            $table->string('employee_name');
            $table->string('department');
            $table->string('position');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_rate', 8, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->json('allowances')->nullable();
            $table->decimal('bonuses', 12, 2)->default(0);
            $table->decimal('commissions', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2);
            $table->decimal('tax_deductions', 12, 2)->default(0);
            $table->decimal('insurance_deductions', 12, 2)->default(0);
            $table->decimal('retirement_deductions', 12, 2)->default(0);
            $table->json('other_deductions')->nullable();
            $table->decimal('total_deductions', 12, 2);
            $table->decimal('net_pay', 12, 2);
            $table->string('bank_account')->nullable();
            $table->enum('payment_method', ['bank_transfer', 'cash', 'cheque', 'digital_wallet'])->default('bank_transfer');
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['draft', 'generated', 'approved', 'pending', 'paid', 'cancelled', 'on_hold'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payroll_period_id', 'employee_id']);
            $table->index(['status', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('employee_documents');
    }
};