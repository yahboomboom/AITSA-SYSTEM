<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=aitsa_system;charset=utf8mb4', 'root', '');
$stmt = $pdo->prepare('SELECT id, login_id, name, email, password, role FROM users WHERE email = ? OR login_id = ? LIMIT 1');
$stmt->execute([$_SERVER['argv'][1] ?? '', $_SERVER['argv'][1] ?? '']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "NOT_FOUND\n";
    exit(0);
}
print_r($user);
