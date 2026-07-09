<?php
$hash = '$2y$12$rM4AnW8QkbdFfT9tIjAu5ugpippytmPVesZ4OmD3HCpyC12EHHi.G';
$passwords = ['password', 'Password', '123456', 'student'];
foreach ($passwords as $pwd) {
    echo $pwd . ':' . (password_verify($pwd, $hash) ? 'true' : 'false') . PHP_EOL;
}
