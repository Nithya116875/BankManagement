<?php
require 'includes/bootstrap.php'; require 'includes/bank.php'; require 'includes/layout.php';
require_login(); $uid = $_SESSION['uid'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $type = ($_POST['type'] ?? '') === 'current' ? 'current' : 'savings';
    $db->prepare('INSERT INTO accounts(user_id,account_number,account_type) VALUES(?,?,?)')->execute([$uid, new_account_number($db), $type]);
    audit($db, $uid, "Opened $type account"); flash('New ' . $type . ' account opened.'); redirect('dashboard.php');
}
$q = $db->prepare('SELECT * FROM accounts WHERE user_id=? ORDER BY id'); $q->execute([$uid]); $accounts = $q->fetchAll();
$t = $db->prepare('SELECT t.*, a.account_number FROM transactions t JOIN accounts a ON a.id=t.account_id WHERE a.user_id=? ORDER BY t.id DESC LIMIT 6');
$t->execute([$uid]); $recent = $t->fetchAll();
page_header('Accounts'); ?>
<h1>Your accounts</h1>
<div class="accounts">
<?php foreach ($accounts as $a): ?>
  <article class="acct <?= $a['status'] ?>">
    <p class="kind"><?= ucfirst($a['account_type']) ?><?= $a['status'] === 'frozen' ? ' (frozen)' : '' ?></p>
    <p class="bal"><?= money($a['balance']) ?></p>
    <p class="num">A/C <?= h(chunk_split($a['account_number'], 4, ' ')) ?></p>
  </article>
<?php endforeach; ?>
</div>
<form method="post" class="inline"><?= csrf_field() ?>
  <select name="type" aria-label="Account type"><option value="savings">Savings</option><option value="current">Current</option></select>
  <button class="btn ghost">Open another account</button>
</form>
<h2>Latest activity</h2>
<?php if (!$recent): ?><p class="empty">Nothing yet. <a href="transact.php">Make your first deposit.</a></p>
<?php else: ?><div class="scroll"><table class="ledger"><thead><tr><th>Date</th><th>Account</th><th>Details</th><th class="r">Amount</th></tr></thead><tbody>
<?php foreach ($recent as $r): $in = in_array($r['type'], ['deposit', 'transfer_in']); ?>
<tr><td><?= h(date('d M, H:i', strtotime($r['created_at']))) ?></td><td>…<?= h(substr($r['account_number'], -4)) ?></td>
<td><?= h(str_replace('_', ' ', ucfirst($r['type']))) ?><?= $r['counterparty'] ? ' · ' . h($r['counterparty']) : '' ?></td>
<td class="r <?= $in ? 'cr' : 'dr' ?>"><?= $in ? '+' : '−' ?><?= money($r['amount']) ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif;
page_footer();
