<?php
require 'includes/bootstrap.php'; require 'includes/bank.php'; require 'includes/layout.php';
if (!empty($_SESSION['uid'])) redirect('dashboard.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $q = $db->prepare('SELECT *, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM users WHERE email=?');
    $q->execute([$email]); $u = $q->fetch();
    if ($u && $u['is_locked']) {
        $err = 'Too many failed attempts. Try again in 15 minutes.';
    } elseif ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        $db->prepare('UPDATE users SET failed_attempts=0, locked_until=NULL WHERE id=?')->execute([$u['id']]);
        session_regenerate_id(true);
        $_SESSION = ['uid' => (int)$u['id'], 'name' => $u['full_name'], 'role' => $u['role'], 'last' => time()];
        audit($db, (int)$u['id'], 'Signed in'); redirect('dashboard.php');
    } else {
        if ($u) {
            $db->prepare('UPDATE users SET failed_attempts=failed_attempts+1 WHERE id=?')->execute([$u['id']]);
            if ($u['failed_attempts'] + 1 >= MAX_LOGIN_FAILS)
                $db->prepare('UPDATE users SET failed_attempts=0, locked_until=DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id=?')->execute([$u['id']]);
            audit($db, (int)$u['id'], 'Failed sign-in');
        }
        $err = 'Email or password is incorrect.';
    }
}
page_header('Sign in'); ?>
<section class="auth"><h1>Sign in</h1>
<?php if ($err) echo '<p class="note err" role="alert">' . h($err) . '</p>'; ?>
<form method="post" class="stack"><?= csrf_field() ?>
  <label>Email<input type="email" name="email" required autocomplete="username"></label>
  <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
  <button class="btn">Sign in</button>
</form><p class="alt">New here? <a href="register.php">Open an account</a></p></section>
<?php page_footer();
