<?php
require 'includes/bootstrap.php'; require 'includes/bank.php'; require 'includes/layout.php';
require_login(); $uid = $_SESSION['uid'];
$q = $db->prepare('SELECT id, account_number, account_type FROM accounts WHERE user_id=? ORDER BY id'); $q->execute([$uid]); $accounts = $q->fetchAll();
$sel = (int)($_GET['account'] ?? ($accounts[0]['id'] ?? 0)); $page = max(1, (int)($_GET['page'] ?? 1)); $per = 15;
$own = array_filter($accounts, fn($a) => $a['id'] === $sel);
$rows = [];
if ($own) {
    $s = $db->prepare('SELECT * FROM transactions WHERE account_id=? ORDER BY id DESC LIMIT ? OFFSET ?');
    $s->bindValue(1, $sel, PDO::PARAM_INT); $s->bindValue(2, $per + 1, PDO::PARAM_INT); $s->bindValue(3, ($page - 1) * $per, PDO::PARAM_INT);
    $s->execute(); $rows = $s->fetchAll();
}
$more = count($rows) > $per; $rows = array_slice($rows, 0, $per);
page_header('Statement'); ?>
<h1>Statement</h1>
<form method="get" class="inline"><select name="account" aria-label="Account" onchange="this.form.submit()">
<?php foreach ($accounts as $a) echo '<option value="' . $a['id'] . '"' . ($a['id'] === $sel ? ' selected' : '') . '>' . ucfirst($a['account_type']) . ' …' . h(substr($a['account_number'], -4)) . '</option>'; ?>
</select><noscript><button class="btn ghost">Show</button></noscript></form>
<?php if (!$rows): ?><p class="empty">No transactions on this account yet.</p><?php else: ?>
<div class="scroll"><table class="ledger"><thead><tr><th>Date</th><th>Reference</th><th>Details</th><th class="r">Amount</th><th class="r">Balance</th></tr></thead><tbody>
<?php foreach ($rows as $r): $in = in_array($r['type'], ['deposit', 'transfer_in']); ?>
<tr><td><?= h(date('d M Y, H:i', strtotime($r['created_at']))) ?></td><td class="mono"><?= h($r['reference']) ?></td>
<td><?= h(str_replace('_', ' ', ucfirst($r['type']))) ?><?= $r['counterparty'] ? ' · ' . h($r['counterparty']) : '' ?><?= $r['description'] ? '<br><small>' . h($r['description']) . '</small>' : '' ?></td>
<td class="r <?= $in ? 'cr' : 'dr' ?>"><?= $in ? '+' : '−' ?><?= money($r['amount']) ?></td><td class="r"><?= money($r['balance_after']) ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<p class="pager"><?php if ($page > 1) echo '<a href="?account=' . $sel . '&page=' . ($page - 1) . '">Newer</a>'; if ($more) echo '<a href="?account=' . $sel . '&page=' . ($page + 1) . '">Older</a>'; ?></p>
<?php endif; page_footer();
