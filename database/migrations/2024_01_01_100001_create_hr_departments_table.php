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
        Schema::create('hr_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('location', 100)->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->integer('max_employees')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // For additional flexible data
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('parent_id')->references('id')->on('hr_departments')->onDelete('set null');
            
            // Indexes
            $table->index(['is_active', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_departments');
    }
};