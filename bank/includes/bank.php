<?php
class BankException extends Exception {}

function parse_amount(string $s): ?string {
    $s = trim($s);
    if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $s)) return null;
    $v = number_format((float)$s, 2, '.', '');
    return ((float)$v > 0 && (float)$v <= MAX_TXN) ? $v : null;
}
function new_account_number(PDO $db): string {
    do {
        $n = '10' . str_pad((string)random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        $q = $db->prepare('SELECT 1 FROM accounts WHERE account_number=?'); $q->execute([$n]);
    } while ($q->fetch());
    return $n;
}
function lock_own_account(PDO $db, int $acc, int $uid): array {
    $q = $db->prepare('SELECT * FROM accounts WHERE id=? AND user_id=? FOR UPDATE');
    $q->execute([$acc, $uid]);
    $a = $q->fetch();
    if (!$a) throw new BankException('Account not found.');
    if ($a['status'] !== 'active') throw new BankException('This account is frozen. Contact the bank.');
    return $a;
}
function log_txn(PDO $db, int $acc, string $type, string $amt, ?string $cp, string $ref, string $desc): void {
    $b = $db->prepare('SELECT balance FROM accounts WHERE id=?'); $b->execute([$acc]);
    $db->prepare('INSERT INTO transactions(account_id,type,amount,balance_after,counterparty,reference,description) VALUES(?,?,?,?,?,?,?)')
       ->execute([$acc, $type, $amt, $b->fetchColumn(), $cp, $ref, $desc]);
}
function atomic(PDO $db, callable $fn) {
    $db->beginTransaction();
    try { $r = $fn(); $db->commit(); return $r; }
    catch (Throwable $e) { $db->rollBack(); throw $e; }
}
function deposit(PDO $db, int $uid, int $acc, string $amt, string $desc): string {
    return atomic($db, function () use ($db, $uid, $acc, $amt, $desc) {
        lock_own_account($db, $acc, $uid);
        $db->prepare('UPDATE accounts SET balance = balance + ? WHERE id=?')->execute([$amt, $acc]);
        $ref = strtoupper(bin2hex(random_bytes(8)));
        log_txn($db, $acc, 'deposit', $amt, null, $ref, $desc);
        return $ref;
    });
}
function withdraw(PDO $db, int $uid, int $acc, string $amt, string $desc): string {
    return atomic($db, function () use ($db, $uid, $acc, $amt, $desc) {
        lock_own_account($db, $acc, $uid);
        $u = $db->prepare('UPDATE accounts SET balance = balance - ? WHERE id=? AND balance >= ?');
        $u->execute([$amt, $acc, $amt]);
        if ($u->rowCount() === 0) throw new BankException('Insufficient balance.');
        $ref = strtoupper(bin2hex(random_bytes(8)));
        log_txn($db, $acc, 'withdrawal', $amt, null, $ref, $desc);
        return $ref;
    });
}
function transfer(PDO $db, int $uid, int $from, string $toNumber, string $amt, string $desc): string {
    return atomic($db, function () use ($db, $uid, $from, $toNumber, $amt, $desc) {
        $t = $db->prepare('SELECT id FROM accounts WHERE account_number=?'); $t->execute([$toNumber]);
        $to = (int)$t->fetchColumn();
        if (!$to) throw new BankException('Recipient account number not found.');
        if ($to === $from) throw new BankException('Choose a different recipient account.');
        // Lock both rows in id order so simultaneous transfers can never deadlock.
        $db->prepare('SELECT id FROM accounts WHERE id IN (?,?) ORDER BY id FOR UPDATE')->execute([$from, $to]);
        $src = lock_own_account($db, $from, $uid);
        $dst = $db->prepare('SELECT status FROM accounts WHERE id=?'); $dst->execute([$to]);
        if ($dst->fetchColumn() !== 'active') throw new BankException('Recipient account cannot receive funds.');
        $u = $db->prepare('UPDATE accounts SET balance = balance - ? WHERE id=? AND balance >= ?');
        $u->execute([$amt, $from, $amt]);
        if ($u->rowCount() === 0) throw new BankException('Insufficient balance.');
        $db->prepare('UPDATE accounts SET balance = balance + ? WHERE id=?')->execute([$amt, $to]);
        $ref = strtoupper(bin2hex(random_bytes(8)));
        log_txn($db, $from, 'transfer_out', $amt, $toNumber, $ref, $desc);
        log_txn($db, $to, 'transfer_in', $amt, $src['account_number'], $ref, $desc);
        return $ref;
    });
}
function clean_text(string $s): string { return mb_substr(trim(strip_tags($s)), 0, 120); }
