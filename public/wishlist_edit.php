<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }
$wid = isset($_POST['wid']) ? (int)$_POST['wid'] : 0;
$target = isset($_POST['target_price']) ? (float)$_POST['target_price'] : 0;
$tag = trim(isset($_POST['tag']) ? $_POST['tag'] : '');
$note = trim(isset($_POST['note']) ? $_POST['note'] : '');

if ($wid<=0 || $target<=0) { header('Location: dashboard.php'); exit; }

// Verify ownership
$chk = $db->prepare('SELECT id FROM wishlist WHERE id=? AND user_id=?');
$chk->execute([$wid, $_SESSION['user_id']]);
if (!$chk->fetch()) { header('Location: dashboard.php'); exit; }

$upd = $db->prepare('UPDATE wishlist SET target_price=?, tag=?, note=? WHERE id=?');
$upd->execute([$target, $tag, $note, $wid]);
header('Location: dashboard.php');