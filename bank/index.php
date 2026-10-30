<?php require 'includes/bootstrap.php'; redirect(empty($_SESSION['uid']) ? 'login.php' : 'dashboard.php');
