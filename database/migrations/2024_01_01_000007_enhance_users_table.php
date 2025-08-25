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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('locale', 10)->default('en')->after('is_active');
            $table->string('timezone', 50)->default('UTC')->after('locale');
            $table->string('avatar')->nullable()->after('timezone');
            $table->text('bio')->nullable()->after('avatar');
            $table->date('date_of_birth')->nullable()->after('bio');
            $table->text('address')->nullable()->after('date_of_birth');
            $table->string('city')->nullable()->after('address');
            $table->string('country')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('country');
            $table->string('emergency_contact_name')->nullable()->after('postal_code');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('job_title')->nullable()->after('emergency_contact_phone');
            $table->string('department')->nullable()->after('job_title');
            $table->decimal('salary', 10, 2)->nullable()->after('department');
            $table->date('hire_date')->nullable()->after('salary');
            $table->uuid('manager_id')->nullable()->after('hire_date');
            $table->text('two_factor_secret')->nullable()->after('remember_token');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');

            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->index('locale');
            $table->index('timezone');
            $table->index('department');
            $table->index('manager_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropIndex(['locale']);
            $table->dropIndex(['timezone']);
            $table->dropIndex(['department']);
            $table->dropIndex(['manager_id']);
            
            $table->dropColumn([
                'phone_verified_at',
                'locale',
                'timezone',
                'avatar',
                'bio',
                'date_of_birth',
                'address',
                'city',
                'country',
                'postal_code',
                'emergency_contact_name',
                'emergency_contact_phone',
                'job_title',
                'department',
                'salary',
                'hire_date',
                'manager_id',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
