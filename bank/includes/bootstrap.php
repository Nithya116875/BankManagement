<?php
require __DIR__ . '/../config.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com");

try {
    $db = new PDO('mysql:host=' . DB_HOST . ';port='.DB_PORT.'; dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Service temporarily unavailable.');
}

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v): string { return '₹' . number_format((float)$v, 2); }
function redirect(string $to): never { header("Location: $to"); exit; }
function csrf_field(): string {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Invalid or expired form. Go back and try again.'); }
}
function flash(?string $msg = null, string $kind = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $kind]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function audit(PDO $db, ?int $uid, string $action): void {
    $db->prepare('INSERT INTO audit_log(user_id,action,ip) VALUES(?,?,?)')->execute([$uid, $action, $_SERVER['REMOTE_ADDR'] ?? null]);
}
function require_login(): void {
    if (empty($_SESSION['uid'])) redirect('login.php');
    if (time() - ($_SESSION['last'] ?? 0) > SESSION_TTL) { session_unset(); session_destroy(); session_start(); flash('You were signed out after 15 minutes of inactivity.', 'err'); redirect('login.php'); }
    $_SESSION['last'] = time();
}
function require_admin(): void { require_login(); if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('Not allowed.'); } }
