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
        Schema::create('hr_employee_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('document_type', ['photo', 'contract', 'certificate', 'id_document', 'resume', 'other']);
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->bigInteger('file_size'); // File size in bytes
            $table->string('mime_type');
            $table->uuid('uploaded_by')->nullable(); // User ID who uploaded
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            // Note: uploaded_by foreign key will be handled in model relationships to avoid UUID/bigint conflicts

            // Indexes
            $table->index(['employee_id', 'document_type']);
            $table->index('uploaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_documents');
    }
};