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
        Schema::create('llm_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('model')->nullable()->index();
            $table->string('request_type')->index(); // completion, chat, function_calling
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('cost', 10, 6)->nullable();
            $table->decimal('duration', 8, 3)->default(0); // Response time in seconds
            $table->integer('quality_score')->default(0); // 0-100
            $table->boolean('success')->default(true);
            $table->string('error_type')->nullable();
            $table->text('error_message')->nullable();
            $table->string('event_type')->nullable(); // For non-completion events
            $table->integer('estimated_tokens')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('timestamp')->index();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['provider', 'success', 'created_at']);
            $table->index(['created_at', 'cost']);
            $table->index(['provider', 'model', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_metrics');
    }
};