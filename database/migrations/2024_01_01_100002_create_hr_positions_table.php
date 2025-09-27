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
        Schema::create('hr_positions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('department_id');
            $table->string('level', 50)->nullable(); // Junior, Senior, Manager, Director, etc.
            $table->decimal('min_salary', 10, 2)->nullable();
            $table->decimal('max_salary', 10, 2)->nullable();
            $table->text('requirements')->nullable();
            $table->text('responsibilities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('skills')->nullable(); // Required skills array
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('department_id')->references('id')->on('hr_departments')->onDelete('cascade');
            
            // Indexes
            $table->index(['department_id', 'is_active']);
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_positions');
    }
};