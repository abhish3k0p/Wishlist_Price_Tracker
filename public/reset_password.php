<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$uid = (int)($_GET['uid'] ?? $_POST['uid'] ?? 0);
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$ok = false; $error = null; $done = false;

if ($uid > 0 && is_string($token) && $token !== '') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pass = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($pass !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = hash('sha256', $token);
            $q = $db->prepare('SELECT user_id, expires_at FROM password_resets WHERE user_id=? AND token_hash=? LIMIT 1');
            $q->execute([$uid, $hash]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if ($row && strtotime($row['expires_at']) > time()) {
                $newHash = password_hash($pass, PASSWORD_DEFAULT);
                $up = $db->prepare('UPDATE users SET password_hash=? WHERE id=?');
                $up->execute([$newHash, $uid]);
                $del = $db->prepare('DELETE FROM password_resets WHERE user_id=?');
                $del->execute([$uid]);
                $done = true;
            } else {
                $error = 'Invalid or expired token.';
            }
        }
    } else {
        // Verify token for GET
        $hash = hash('sha256', $token);
        $q = $db->prepare('SELECT user_id, expires_at FROM password_resets WHERE user_id=? AND token_hash=? LIMIT 1');
        $q->execute([$uid, $hash]);
        $ok = (bool)($q->fetch(PDO::FETCH_ASSOC));
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head>
  <meta charset="utf-8"><title>Reset Password</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head><body>
  <div class="container" style="max-width:520px">
    <div class="card">
      <div class="card-header">Reset Password</div>
      <div class="card-body">
        <?php if ($done): ?>
          <div class="alert success mb-md">Password has been reset. <a href="login.php">Login</a></div>
        <?php elseif (!$uid || !$token || (!$ok && $_SERVER['REQUEST_METHOD'] !== 'POST')): ?>
          <div class="alert error mb-md">Invalid reset link.</div>
        <?php else: ?>
          <?php if ($error): ?><div class="alert error mb-md"><?php echo h($error); ?></div><?php endif; ?>
          <form method="post">
            <input type="hidden" name="uid" value="<?php echo (int)$uid; ?>">
            <input type="hidden" name="token" value="<?php echo h($token); ?>">
            <label>New Password</label>
            <input type="password" name="password" required minlength="6">
            <label>Confirm Password</label>
            <input type="password" name="confirm" required minlength="6">
            <div class="flex gap-sm" style="margin-top:.75rem">
              <button type="submit">Reset Password</button>
              <a class="btn secondary" href="login.php">Back to login</a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body></html>
