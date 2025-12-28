<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;

$emp = User::where('email', 'employee@medistaff.com')->first();
$apps = LeaveApplication::where('employee_id', $emp->id)->where('status', 'approved')->get();

echo "Approved Content:\n";
$totalUsed = 0;
foreach ($apps as $a) {
    echo "ID: {$a->id}, Days: {$a->total_days}\n";
    $totalUsed += $a->total_days;
}

$balance = LeaveBalance::where('employee_id', $emp->id)->first();
echo "Total Calculated Used: $totalUsed\n";
echo "Balance Record Used: {$balance->used_days}\n";
echo "Balance Record Remaining: {$balance->remaining_days}\n";
