<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }
$wid = (int)($_POST['wid'] ?? 0);
if ($wid<=0) { header('Location: dashboard.php'); exit; }

// Verify ownership
$chk = $db->prepare('SELECT id FROM wishlist WHERE id=? AND user_id=?');
$chk->execute([$wid, $_SESSION['user_id']]);
if (!$chk->fetch()) { header('Location: dashboard.php'); exit; }

$del = $db->prepare('DELETE FROM wishlist WHERE id=?');
$del->execute([$wid]);
header('Location: dashboard.php');
