<?php
require 'includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); if (!empty($_SESSION['uid'])) audit($db, $_SESSION['uid'], 'Signed out'); }
$_SESSION = []; session_destroy(); redirect('login.php');
