<?php
// Run once from the command line:  php create_admin.php admin@bank.com "Admin Name" "StrongPass123"
if (PHP_SAPI !== 'cli') exit('CLI only.');
require 'includes/bootstrap.php';
[, $email, $name, $pw] = $argv + [null, null, null, null];
if (!$email || !$name || strlen((string)$pw) < 8) exit("Usage: php create_admin.php email \"Full Name\" password(8+)\n");
$db->prepare("INSERT INTO users(full_name,email,password_hash,role) VALUES(?,?,?,'admin')")->execute([$name, strtolower($email), password_hash($pw, PASSWORD_DEFAULT)]);
echo "Admin created.\n";
