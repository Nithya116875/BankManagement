<?php
require 'includes/bootstrap.php'; require 'includes/bank.php'; require 'includes/layout.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = clean_text($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
    $pw = $_POST['password'] ?? ''; $type = ($_POST['type'] ?? '') === 'current' ? 'current' : 'savings';
    if (strlen($name) < 2) $err = 'Enter your full name.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email address.';
    elseif (strlen($pw) < 8 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) $err = 'Password needs 8+ characters with at least one letter and one number.';
    elseif ($pw !== ($_POST['confirm'] ?? '')) $err = 'The two passwords do not match.';
    else {
        $q = $db->prepare('SELECT 1 FROM users WHERE email=?'); $q->execute([$email]);
        if ($q->fetch()) $err = 'An account with this email already exists. Sign in instead.';
        else {
            atomic($db, function () use ($db, $name, $email, $pw, $type) {
                $db->prepare('INSERT INTO users(full_name,email,password_hash) VALUES(?,?,?)')->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
                $uid = (int)$db->lastInsertId();
                $db->prepare('INSERT INTO accounts(user_id,account_number,account_type) VALUES(?,?,?)')->execute([$uid, new_account_number($db), $type]);
                audit($db, $uid, 'Registered');
            });
            flash('Account created. Sign in to start.'); redirect('login.php');
        }
    }
}
page_header('Open an account'); ?>
<section class="auth"><h1>Open an account</h1>
<?php if ($err) echo '<p class="note err" role="alert">' . h($err) . '</p>'; ?>
<form method="post" class="stack"><?= csrf_field() ?>
  <label>Full name<input name="name" required maxlength="100" autocomplete="name" value="<?= h($_POST['name'] ?? '') ?>"></label>
  <label>Email<input type="email" name="email" required autocomplete="email" value="<?= h($_POST['email'] ?? '') ?>"></label>
  <label>Account type<select name="type"><option value="savings">Savings</option><option value="current">Current</option></select></label>
  <label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
  <label>Confirm password<input type="password" name="confirm" required autocomplete="new-password"></label>
  <button class="btn">Create account</button>
</form><p class="alt">Already a customer? <a href="login.php">Sign in</a></p></section>
<?php page_footer();
