<?php
use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use App\Models\LeaveApplication;
use App\Services\LeaveApprovalService;
use App\Services\LeavePdfService;
use Illuminate\Support\Facades\Auth;

echo "Starting Simulation...\n";

// 1. Setup Data
$employee = User::where('email', 'employee@test.com')->firstOrFail();
$manager1 = User::where('email', 'manager1@test.com')->firstOrFail();
$manager2 = User::where('email', 'manager2@test.com')->firstOrFail();
$hr = User::where('email', 'amsathichau@yahoo.fr')->firstOrFail();
$director = User::where('email', 'gueco167@gmail.com')->firstOrFail();

$leaveType = LeaveType::first(); // Assumes seeded
if (!$leaveType) {
    $leaveType = LeaveType::create([
        'name' => 'Annual Leave',
        'slug' => 'annual-leave',
        'status' => 'active',
        'created_by' => 1
    ]);
}

$policy = LeavePolicy::firstOrNew(['leave_type_id' => $leaveType->id]);
$policy->max_days_per_year = 20;
$policy->requires_approval = true;
$policy->status = 'active';
$policy->created_by = 1;
$policy->save();

// 2. Create Application
echo "Creating application for Employee...\n";
$application = LeaveApplication::create([
    'employee_id' => $employee->id,
    'leave_type_id' => $leaveType->id,
    'leave_policy_id' => $policy->id,
    'start_date' => now()->addDays(5),
    'end_date' => now()->addDays(7),
    'total_days' => 3,
    'reason' => 'Test Leave',
    'status' => 'pending',
    'current_stage' => 1,
    'created_by' => $employee->id,
    'is_completed' => false
]);

echo "Application created with ID: {$application->id}\n";

$service = app(LeaveApprovalService::class);

// 3. Stage 1 Approval (Manager 1)
echo "Approving Stage 1 (Manager 1)...\n";
try {
    Auth::login($manager1);
    $service->approve($application, $manager1, 'Approved by Manager 1');
    $application->refresh();
    echo "Stage after Manager 1: {$application->current_stage}\n";
    if ($application->current_stage != 2)
        throw new Exception("Expected Stage 2, got {$application->current_stage}");
} catch (\Exception $e) {
    echo "Error Stage 1: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

// 4. Stage 2 Approval (Manager 2)
echo "Approving Stage 2 (Manager 2)...\n";
try {
    Auth::login($manager2);
    $service->approve($application, $manager2, 'Approved by Manager 2');
    $application->refresh();
    echo "Stage after Manager 2: {$application->current_stage}\n";
    if ($application->current_stage != 3)
        throw new Exception("Expected Stage 3, got {$application->current_stage}");
} catch (\Exception $e) {
    echo "Error Stage 2: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

// 5. Stage 3 Approval (HR)
echo "Approving Stage 3 (HR)...\n";
try {
    Auth::login($hr);
    $service->approve($application, $hr, 'Approved by HR');
    $application->refresh();
    echo "Stage after HR: {$application->current_stage}\n";
    if ($application->current_stage != 4)
        throw new Exception("Expected Stage 4, got {$application->current_stage}");
} catch (\Exception $e) {
    echo "Error Stage 3: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

// 6. Stage 4 Approval (Director)
echo "Approving Stage 4 (Director)...\n";
try {
    Auth::login($director);
    $service->approve($application, $director, 'Approved by Director');
    $application->refresh();
    echo "Final Status: {$application->status}\n";
    echo "Is Completed: " . ($application->is_completed ? 'Yes' : 'No') . "\n";
    if ($application->status != 'approved')
        throw new Exception("Expected Status approved, got {$application->status}");
} catch (\Exception $e) {
    echo "Error Stage 4: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

echo "Simulation Completed Successfully!\n";
