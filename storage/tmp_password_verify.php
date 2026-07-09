<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('email', 'student@aitsa.edu.ph')->first();
if (!$user) {
    echo "NO_USER_EMAIL\n";
    exit(0);
}
echo 'len=' . strlen($user->password) . "\n";
echo 'php_verify=' . (password_verify('password123', $user->password) ? 'true' : 'false') . "\n";
