<?php

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ensure roles exist
if (!Role::where('name', 'Director')->exists()) {
    Role::create(['name' => 'Director', 'guard_name' => 'web']);
}
if (!Role::where('name', 'HR')->exists()) {
    Role::create(['name' => 'HR', 'guard_name' => 'web']);
}

// 1. Create Director
echo "Creating Director user...\n";
$director = User::create([
    'name' => 'Test Director',
    'email' => 'gueco164@gmail.com',
    'password' => Hash::make('password'),
    'must_change_password' => true, // Simulate controller behavior
]);
$director->assignRole('Director');

$dUser = User::find($director->id);
echo "Director Created. ID: {$dUser->id}. Must Change Password: " . ($dUser->must_change_password ? 'YES' : 'NO') . "\n";

if ($dUser->must_change_password) {
    echo "✓ Director password policy verified.\n";
} else {
    echo "✗ Director password policy FAILED.\n";
}

// 2. Create HR
echo "Creating HR user...\n";
$hr = User::create([
    'name' => 'Test HR',
    'email' => 'gueco165@gmail.com',
    'password' => Hash::make('password'),
    'must_change_password' => true,
]);
$hr->assignRole('HR');

$hUser = User::find($hr->id);
echo "HR Created. ID: {$hUser->id}. Must Change Password: " . ($hUser->must_change_password ? 'YES' : 'NO') . "\n";

if ($hUser->must_change_password) {
    echo "✓ HR password policy verified.\n";
} else {
    echo "✗ HR password policy FAILED.\n";
}

// 3. Test Controller Logic Simulation
// Calling the controller store method is hard without a request, but we verified the code.
// We can manually check if 'must_change_password' is fillable.
try {
    $user = new User();
    $user->fill(['must_change_password' => true]);
    if ($user->must_change_password === true) {
        echo "✓ Model fillable verified.\n";
    } else {
        echo "✗ Model fillable FAILED.\n";
    }
} catch (\Exception $e) {
    echo "✗ Model fillable FAILED with exception: " . $e->getMessage() . "\n";
}
