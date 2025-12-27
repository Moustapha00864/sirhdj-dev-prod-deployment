<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $results = DB::select('DESCRIBE users');
    file_put_contents('schema_output.json', json_encode($results, JSON_PRETTY_PRINT));
    echo "Schema saved to schema_output.json\n";

    $users = DB::table('users')->limit(1)->get();
    echo "Successfully read " . count($users) . " users\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
