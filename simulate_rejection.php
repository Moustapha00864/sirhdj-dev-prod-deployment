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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

// Force mail to log to avoid timeouts
Config::set('mail.default', 'log');
Config::set('mail.mailers.log.transport', 'log');

echo "Starting Rejection Simulation...\n";

// 1. Setup Data
try {
    $employee = User::where('email', 'employee@medistaff.com')->firstOrFail();
    $manager1 = User::where('email', 'manager1@medistaff.com')->firstOrFail();
} catch (\Exception $e) {
    die("Error finding users: " . $e->getMessage());
}

$leaveType = LeaveType::where('name', 'Congé Annuel')->firstOrFail();
$policy = LeavePolicy::where('leave_type_id', $leaveType->id)->firstOrFail();

// 2. Create Application
echo "Creating application to be rejected...\n";
$application = LeaveApplication::create([
    'employee_id' => $employee->id,
    'leave_type_id' => $leaveType->id,
    'leave_policy_id' => $policy->id,
    'start_date' => now()->addDays(10),
    'end_date' => now()->addDays(12),
    'total_days' => 2,
    'reason' => 'Test Leave Rejection',
    'status' => 'pending',
    'current_stage' => 1,
    'created_by' => $employee->id,
    'is_completed' => false
]);

echo "Application created with ID: {$application->id}\n";

$service = app(LeaveApprovalService::class);

// 3. Reject at Stage 1
echo "Manager 1 Rejecting...\n";
try {
    $service->reject($application, $manager1, 'Not a good time.');
    $application->refresh();

    echo "Status: {$application->status}\n";
    echo "Is Completed: " . ($application->is_completed ? 'Yes' : 'No') . "\n";

    if ($application->status === 'rejected') {
        echo "SUCCESS: Application rejected correctly.\n";
    } else {
        echo "FAILURE: Application status is {$application->status}\n";
    }

} catch (\Exception $e) {
    echo "Error Rejecting: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
