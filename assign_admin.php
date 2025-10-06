<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get user and role
$user = App\Models\User::where('email', 'admin@hr-system.com')->first();
$role = Spatie\Permission\Models\Role::where('name', 'super-admin')->first();

if ($user && $role) {
    try {
        // Try direct database insertion
        DB::table('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => 'App\\Models\\User',
            'model_id' => (string) $user->id  // Convert UUID to string
        ]);
        echo "Admin role assigned successfully to: " . $user->email . "\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "User ID type: " . gettype($user->id) . "\n";
        echo "User ID: " . $user->id . "\n";
    }
} else {
    echo "User or role not found!\n";
}