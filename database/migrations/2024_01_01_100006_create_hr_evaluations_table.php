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
        Schema::create('hr_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('evaluator_id');
            
            // Evaluation period
            $table->date('evaluation_period_start');
            $table->date('evaluation_period_end');
            $table->enum('evaluation_type', ['annual', 'quarterly', 'probation', 'special']);
            
            // Scores and ratings
            $table->decimal('overall_score', 3, 2)->nullable(); // 0.00 to 5.00
            $table->enum('overall_rating', ['excellent', 'good', 'satisfactory', 'needs_improvement', 'poor'])->nullable();
            
            // Detailed evaluations (JSON for flexibility)
            $table->json('performance_scores')->nullable(); // Different criteria scores
            $table->json('goals_achievement')->nullable(); // Goal completion status
            $table->json('competencies')->nullable(); // Skill assessments
            
            // Comments and feedback
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('goals_next_period')->nullable();
            $table->text('development_plan')->nullable();
            $table->text('employee_comments')->nullable();
            $table->text('evaluator_comments')->nullable();
            
            // Status and approval
            $table->enum('status', ['draft', 'pending_review', 'completed', 'acknowledged'])->default('draft');
            $table->datetime('completed_at')->nullable();
            $table->datetime('acknowledged_at')->nullable();
            $table->boolean('employee_acknowledged')->default(false);
            
            // Recommendations
            $table->enum('salary_recommendation', ['increase', 'no_change', 'decrease'])->nullable();
            $table->decimal('salary_increase_percentage', 5, 2)->nullable();
            $table->enum('promotion_recommendation', ['promote', 'no_change', 'review'])->nullable();
            $table->text('recommendations')->nullable();
            
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->foreign('evaluator_id')->references('id')->on('users')->onDelete('restrict');
            
            // Indexes
            $table->index(['employee_id', 'evaluation_period_start']);
            $table->index(['evaluator_id', 'status']);
            $table->index('evaluation_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_evaluations');
    }
};