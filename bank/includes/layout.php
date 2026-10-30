<?php
function page_header(string $title): void {
    $f = flash(); $cur = basename($_SERVER['SCRIPT_NAME']);
    $nav = ['dashboard.php' => 'Accounts', 'transact.php' => 'Move money', 'history.php' => 'Statement'];
    if (($_SESSION['role'] ?? '') === 'admin') $nav['admin.php'] = 'Admin';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . h($title) . ' · Kaveri Bank</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600&family=Source+Serif+4:opsz,wght@8..60,600&display=swap">';
    echo '<link rel="stylesheet" href="assets/style.css"></head><body>';
    if (!empty($_SESSION['uid'])) {
        echo '<header class="bar"><a class="brand" href="dashboard.php">Kaveri Bank</a><nav>';
        foreach ($nav as $u => $l) echo '<a href="' . $u . '"' . ($cur === $u ? ' aria-current="page"' : '') . '>' . $l . '</a>';
        echo '</nav><form method="post" action="logout.php">' . csrf_field() . '<span class="who">' . h($_SESSION['name']) . '</span><button class="link">Sign out</button></form></header>';
    }
    echo '<main>';
    if ($f) echo '<p class="note ' . $f[1] . '" role="status">' . h($f[0]) . '</p>';
}
function page_footer(): void { echo '</main></body></html>'; }
