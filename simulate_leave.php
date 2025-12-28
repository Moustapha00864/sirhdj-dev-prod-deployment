<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use App\Models\LeaveApplication;
use App\Services\LeaveApprovalService;
use App\Services\LeavePdfService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

// Force mail to log to avoid timeouts
Config::set('mail.default', 'log');
Config::set('mail.mailers.log.transport', 'log');

echo "Starting Simulation...\n";

// 1. Setup Data
try {
    $employee = User::where('email', 'employee@medistaff.com')->firstOrFail();
    $manager1 = User::where('email', 'manager1@medistaff.com')->firstOrFail();
    $manager2 = User::where('email', 'manager2@medistaff.com')->firstOrFail();
    $hr = User::where('email', 'amsathichau@yahoo.fr')->firstOrFail();
    $director = User::where('email', 'director@medistaff.com')->firstOrFail();
} catch (\Exception $e) {
    die("Error finding users: " . $e->getMessage() . "\nDid you run the seeder?");
}

$leaveType = LeaveType::where('name', 'Congé Annuel')->firstOrFail();
$policy = LeavePolicy::where('leave_type_id', $leaveType->id)->firstOrFail();

// RESET BALANCE FOR TESTING
$b = \App\Models\LeaveBalance::where('employee_id', $employee->id)->where('leave_type_id', $leaveType->id)->first();
if ($b) {
    $b->used_days = 0;
    $b->remaining_days = $b->allocated_days;
    $b->save();
}
// Delete previous apps to avoid confusion
LeaveApplication::where('employee_id', $employee->id)->delete();

// 2. Create Application
echo "Creating application for Employee...\n";
$application = LeaveApplication::create([
    'employee_id' => $employee->id,
    'leave_type_id' => $leaveType->id,
    'leave_policy_id' => $policy->id,
    'start_date' => now()->addDays(5),
    'end_date' => now()->addDays(7),
    'total_days' => 3,
    'reason' => 'Test Leave Multi-Level',
    'status' => 'pending',
    'current_stage' => 1,
    'created_by' => $employee->id,
    'is_completed' => false
]);

echo "Application created with ID: {$application->id}\n";

$service = app(LeaveApprovalService::class);

// Function to simulate approval
function approveStage($service, $app, $user, $stageName, $expectedNextStage)
{
    echo "Approving Stage: $stageName ({$user->name})...\n";
    try {
        // Mock permission check if needed, or rely on service logic (which checks roles/depts)
        // Note: The service uses Auth::user() or the passed user? 
        // Checking service code: checks $approver passed in argument, but also uses Roles which require DB checks.

        $service->approve($app, $user, "Approved by $stageName");
        $app->refresh();

        echo "Current Stage: {$app->current_stage}, Status: {$app->status}\n";

        if ($expectedNextStage === 'completed') {
            if (!$app->is_completed || $app->status !== 'approved') {
                throw new Exception("Expected completed/approved, got Stage {$app->current_stage} / {$app->status}");
            }
        } else {
            if ($app->current_stage != $expectedNextStage) {
                throw new Exception("Expected Stage $expectedNextStage, got {$app->current_stage}");
            }
        }
        echo "SUCCESS: $stageName passed.\n";
    } catch (\Exception $e) {
        echo "ERROR $stageName: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString();
        exit(1);
    }
}

// 3. Run Workflow
approveStage($service, $application, $manager1, 'Manager 1', 2);
approveStage($service, $application, $manager2, 'Manager 2', 3);
approveStage($service, $application, $hr, 'HR', 4);
approveStage($service, $application, $director, 'Director', 'completed');

echo "\nSimulation Completed Successfully! PDF should be generated and Balance deducted.\n";

// Check Balance
$balance = \App\Models\LeaveBalance::where('employee_id', $employee->id)->where('leave_type_id', $leaveType->id)->first();
echo "Remaining Balance: {$balance->remaining_days} (Expected 17)\n";
