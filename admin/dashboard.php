<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
if (empty($_SESSION['is_admin'])) { header('Location: ../public/dashboard.php'); exit; }
?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Admin Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css"></head><body>
<h2>Admin Dashboard</h2>
<nav>
  <a href="users.php">Users</a> |
  <a href="products.php">Products</a> |
  <a href="logs.php">Logs</a> |
  <a href="analytics.php">Analytics</a> |
  <a href="settings.php">Settings</a>
</nav>
</body></html>
