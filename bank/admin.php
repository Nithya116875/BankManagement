<?php
require 'includes/bootstrap.php'; require 'includes/bank.php'; require 'includes/layout.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $id = (int)($_POST['id'] ?? 0); $to = ($_POST['to'] ?? '') === 'frozen' ? 'frozen' : 'active';
    $db->prepare('UPDATE accounts SET status=? WHERE id=?')->execute([$to, $id]);
    audit($db, $_SESSION['uid'], "Set account #$id to $to"); flash("Account set to $to."); redirect('admin.php');
}
$accs = $db->query('SELECT a.*, u.full_name, u.email FROM accounts a JOIN users u ON u.id=a.user_id ORDER BY a.id')->fetchAll();
$log = $db->query('SELECT l.*, u.email FROM audit_log l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 25')->fetchAll();
$tot = $db->query('SELECT COALESCE(SUM(balance),0) FROM accounts')->fetchColumn();
page_header('Admin'); ?>
<h1>Bank overview</h1><p>Total deposits held: <strong><?= money($tot) ?></strong> across <?= count($accs) ?> accounts.</p>
<div class="scroll"><table class="ledger"><thead><tr><th>Customer</th><th>Account</th><th class="r">Balance</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($accs as $a): ?><tr><td><?= h($a['full_name']) ?><br><small><?= h($a['email']) ?></small></td><td class="mono"><?= h($a['account_number']) ?></td>
<td class="r"><?= money($a['balance']) ?></td><td><?= h($a['status']) ?></td>
<td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $a['id'] ?>"><input type="hidden" name="to" value="<?= $a['status'] === 'active' ? 'frozen' : 'active' ?>">
<button class="btn ghost"><?= $a['status'] === 'active' ? 'Freeze' : 'Unfreeze' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div>
<h2>Security log</h2>
<div class="scroll"><table class="ledger"><tbody><?php foreach ($log as $l) echo '<tr><td>' . h($l['created_at']) . '</td><td>' . h($l['email'] ?? '—') . '</td><td>' . h($l['action']) . '</td><td class="mono">' . h($l['ip']) . '</td></tr>'; ?></tbody></table></div>
<?php page_footer();
