<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/config.php';

$sent = false; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $stmt = $db->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time()+3600);
            $up = $db->prepare('INSERT INTO password_resets(user_id, token_hash, expires_at) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash), expires_at=VALUES(expires_at)');
            $up->execute([(int)$user['id'], $hash, $expires]);
            $base = rtrim(env('APP_URL',''), '/');
            $link = $base . '/public/reset_password.php?uid=' . (int)$user['id'] . '&token=' . urlencode($token);
            sendPasswordReset($user, $link);
        }
        // Always show success regardless to prevent user enumeration
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head>
  <meta charset="utf-8"><title>Forgot Password</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head><body>
  <div class="container" style="max-width:520px">
    <div class="card">
      <div class="card-header">Forgot Password</div>
      <div class="card-body">
        <?php if ($sent): ?>
          <div class="alert success mb-md">If the email exists, a reset link has been sent.</div>
        <?php endif; ?>
        <?php if ($error): ?><div class="alert error mb-md"><?php echo h($error); ?></div><?php endif; ?>
        <form method="post">
          <label>Email</label>
          <input type="email" name="email" required placeholder="you@example.com">
          <div class="flex gap-sm" style="margin-top:.75rem">
            <button type="submit">Send Reset Link</button>
            <a class="btn secondary" href="login.php">Back to login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</body></html>
