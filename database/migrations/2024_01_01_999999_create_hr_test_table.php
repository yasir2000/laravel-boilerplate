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
        // Create a simple test HR table to verify the system works
        Schema::create('hr_test_data', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('demo');
            $table->timestamps();
        });
        
        // Insert some test data
        DB::table('hr_test_data')->insert([
            ['name' => 'HR Department', 'type' => 'department', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'IT Department', 'type' => 'department', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'John Doe', 'type' => 'employee', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jane Smith', 'type' => 'employee', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_test_data');
    }
};