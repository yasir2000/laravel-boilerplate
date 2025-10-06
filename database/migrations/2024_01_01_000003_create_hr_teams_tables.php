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
        // Create teams table
        Schema::create('hr_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('department')->nullable();
            $table->uuid('team_lead_id')->nullable();
            $table->enum('team_type', [
                'project', 
                'permanent', 
                'cross-functional', 
                'task-force', 
                'committee', 
                'working-group'
            ])->default('project');
            $table->enum('status', [
                'forming', 
                'active', 
                'performing', 
                'on-hold', 
                'completed', 
                'disbanded'
            ])->default('forming');
            $table->string('icon')->default('🏆');
            $table->integer('performance_score')->default(0);
            $table->json('goals')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['department', 'status']);
            $table->index(['team_type', 'is_active']);
            $table->index('team_lead_id');

            // Foreign keys
            $table->foreign('team_lead_id')->references('id')->on('users')->onDelete('set null');
        });

        // Create team members pivot table
        Schema::create('hr_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->uuid('user_id');
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->unique(['team_id', 'user_id']);
            $table->index(['team_id', 'is_active']);
            $table->index(['user_id', 'is_active']);

            // Foreign keys
            $table->foreign('team_id')->references('id')->on('hr_teams')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Create team evaluations table (optional - for performance tracking)
        Schema::create('hr_team_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->uuid('evaluator_id');
            $table->integer('performance_score');
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('goals_achieved')->nullable();
            $table->text('comments')->nullable();
            $table->date('evaluation_date');
            $table->enum('evaluation_type', ['quarterly', 'annual', 'project-end', 'custom'])->default('quarterly');
            $table->json('metrics')->nullable(); // Store specific KPIs
            $table->timestamps();

            // Indexes
            $table->index(['team_id', 'evaluation_date']);
            $table->index(['evaluator_id']);

            // Foreign keys
            $table->foreign('team_id')->references('id')->on('hr_teams')->onDelete('cascade');
            $table->foreign('evaluator_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_team_evaluations');
        Schema::dropIfExists('hr_team_members');
        Schema::dropIfExists('hr_teams');
    }
};