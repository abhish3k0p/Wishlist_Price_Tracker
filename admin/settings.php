<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
if (empty($_SESSION['is_admin'])) { header('Location: ../public/dashboard.php'); exit; }
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/helpers.php';

$info = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pairs = [
        'SMTP_HOST' => trim($_POST['SMTP_HOST'] ?? ''),
        'SMTP_PORT' => trim($_POST['SMTP_PORT'] ?? ''),
        'SMTP_USER' => trim($_POST['SMTP_USER'] ?? ''),
        'SMTP_PASS' => trim($_POST['SMTP_PASS'] ?? ''),
        'SMTP_SECURE' => trim($_POST['SMTP_SECURE'] ?? ''),
        'SMTP_FROM' => trim($_POST['SMTP_FROM'] ?? ''),
        'SMTP_FROM_NAME' => trim($_POST['SMTP_FROM_NAME'] ?? ''),
    ];
    save_settings($pairs);
    $info = 'Settings saved.';
}

$vals = [
  'SMTP_HOST' => setting('SMTP_HOST', ''),
  'SMTP_PORT' => setting('SMTP_PORT', '587'),
  'SMTP_USER' => setting('SMTP_USER', ''),
  'SMTP_PASS' => setting('SMTP_PASS', ''),
  'SMTP_SECURE' => setting('SMTP_SECURE', 'tls'),
  'SMTP_FROM' => setting('SMTP_FROM', 'no-reply@example.com'),
  'SMTP_FROM_NAME' => setting('SMTP_FROM_NAME', 'Wishlist Tracker'),
];
?>
<!DOCTYPE html>
<html lang="en"><head>
  <meta charset="utf-8"><title>Admin Settings</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>label{display:block;margin:.4rem 0}</style>
</head><body>
  <h2>Settings</h2>
  <p><a href="dashboard.php">← Back</a></p>
  <?php if ($info): ?><div class="success"><?php echo h($info); ?></div><?php endif; ?>
  <form method="post">
    <fieldset><legend>SMTP</legend>
      <label>SMTP Host<br><input name="SMTP_HOST" value="<?php echo h($vals['SMTP_HOST']); ?>" required></label>
      <label>SMTP Port<br><input name="SMTP_PORT" value="<?php echo h($vals['SMTP_PORT']); ?>" required></label>
      <label>SMTP User<br><input name="SMTP_USER" value="<?php echo h($vals['SMTP_USER']); ?>"></label>
      <label>SMTP Pass<br><input name="SMTP_PASS" value="<?php echo h($vals['SMTP_PASS']); ?>"></label>
      <label>SMTP Secure (tls or ssl)<br><input name="SMTP_SECURE" value="<?php echo h($vals['SMTP_SECURE']); ?>"></label>
      <label>From Email<br><input name="SMTP_FROM" value="<?php echo h($vals['SMTP_FROM']); ?>" required></label>
      <label>From Name<br><input name="SMTP_FROM_NAME" value="<?php echo h($vals['SMTP_FROM_NAME']); ?>"></label>
    </fieldset>
    <button type="submit">Save</button>
  </form>
</body></html>
