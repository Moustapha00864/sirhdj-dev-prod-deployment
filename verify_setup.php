<?php

use App\Models\User;
use App\Models\Department;
use App\Models\LeaveBalance;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Verifying Setup...\n";

// Check Users
$users = [
    'amsathichau@yahoo.fr',
    'director@medistaff.com',
    'manager1@medistaff.com',
    'manager2@medistaff.com',
    'employee@medistaff.com'
];

foreach ($users as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        echo "User found: {$user->name} ({$user->email}) - Roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    } else {
        echo "ERROR: User not found: $email\n";
    }
}

// Check Department
$dept = Department::where('name', 'Medical Staff')->with(['manager', 'validator2', 'director'])->first();
if ($dept) {
    echo "\nDepartment found: {$dept->name}\n";
    echo "Manager 1: " . ($dept->manager ? $dept->manager->name : 'NONE') . "\n";
    echo "Validator 2: " . ($dept->validator2 ? $dept->validator2->name : 'NONE') . "\n";
    echo "Director: " . ($dept->director ? $dept->director->name : 'NONE') . "\n";
} else {
    echo "ERROR: Department not found\n";
}

// Check Balance
$emp = User::where('email', 'employee@medistaff.com')->first();
if ($emp) {
    $balance = LeaveBalance::where('employee_id', $emp->id)->first();
    if ($balance) {
        echo "\nBalance found for Employee: {$balance->allocated_days} allocated, {$balance->remaining_days} remaining.\n";
        echo "Initial Balance: " . ($balance->initial_leave_balance ?? 'N/A') . "\n";
    } else {
        echo "ERROR: No balance found for employee\n";
    }
}
