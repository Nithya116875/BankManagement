<?php
require 'includes/bootstrap.php'; require 'includes/bank.php'; require 'includes/layout.php';
require_login(); $uid = $_SESSION['uid'];
$type = $_GET['type'] ?? 'deposit'; if (!in_array($type, ['deposit', 'withdraw', 'transfer'], true)) $type = 'deposit';
$q = $db->prepare("SELECT id, account_number, account_type, balance FROM accounts WHERE user_id=? AND status='active' ORDER BY id"); $q->execute([$uid]); $accounts = $q->fetchAll();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acc = (int)($_POST['account'] ?? 0); $amt = parse_amount($_POST['amount'] ?? ''); $desc = clean_text($_POST['desc'] ?? '');
    if (!$amt) $err = 'Enter an amount between ₹0.01 and ' . money(MAX_TXN) . ', with up to 2 decimals.';
    else {
        try {
            if ($type !== 'deposit') {   // money leaving the bank requires the password again
                $p = $db->prepare('SELECT password_hash FROM users WHERE id=?'); $p->execute([$uid]);
                if (!password_verify($_POST['password'] ?? '', (string)$p->fetchColumn())) throw new BankException('Password is incorrect.');
            }
            if ($type === 'deposit') $ref = deposit($db, $uid, $acc, $amt, $desc);
            elseif ($type === 'withdraw') $ref = withdraw($db, $uid, $acc, $amt, $desc);
            else {
                $to = preg_replace('/\D/', '', $_POST['to'] ?? '');
                if (strlen($to) !== 12) throw new BankException('Recipient account numbers have 12 digits.');
                $ref = transfer($db, $uid, $acc, $to, $amt, $desc);
            }
            audit($db, $uid, ucfirst($type) . " $amt ref $ref");
            flash(ucfirst($type) . ' of ' . money($amt) . ' done. Reference ' . $ref . '.'); redirect('dashboard.php');
        } catch (BankException $e) { $err = $e->getMessage(); }
        catch (Throwable $e) { error_log($e->getMessage()); $err = 'Something went wrong and nothing was changed. Please try again.'; }
    }
}
page_header('Move money'); ?>
<h1>Move money</h1>
<nav class="tabs"><?php foreach (['deposit' => 'Deposit', 'withdraw' => 'Withdraw', 'transfer' => 'Transfer'] as $k => $l)
  echo '<a href="?type=' . $k . '"' . ($type === $k ? ' aria-current="page"' : '') . '>' . $l . '</a>'; ?></nav>
<?php if ($err) echo '<p class="note err" role="alert">' . h($err) . '</p>'; ?>
<form method="post" class="stack narrow"><?= csrf_field() ?>
  <label><?= $type === 'deposit' ? 'Into account' : 'From account' ?>
    <select name="account"><?php foreach ($accounts as $a) echo '<option value="' . $a['id'] . '">' . ucfirst($a['account_type']) . ' …' . h(substr($a['account_number'], -4)) . ' (' . money($a['balance']) . ')</option>'; ?></select></label>
  <?php if ($type === 'transfer'): ?><label>Recipient account number<input name="to" inputmode="numeric" maxlength="14" required placeholder="12 digits"></label><?php endif; ?>
  <label>Amount (₹)<input name="amount" inputmode="decimal" required placeholder="0.00"></label>
  <label>Note (optional)<input name="desc" maxlength="120"></label>
  <?php if ($type !== 'deposit'): ?><label>Confirm with your password<input type="password" name="password" required autocomplete="current-password"></label><?php endif; ?>
  <button class="btn"><?= ['deposit' => 'Deposit funds', 'withdraw' => 'Withdraw funds', 'transfer' => 'Send transfer'][$type] ?></button>
</form>
<?php page_footer();
