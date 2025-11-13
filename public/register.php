<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $created = auth_register($db, $name, $email, $password);
        if ($created) {
            // Auto-login then redirect to dashboard to avoid root-path 404s
            $user = auth_login($db, $email, $password);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = (int)($user['is_admin'] ?? 0);
                header('Location: dashboard.php');
                exit;
            }
            header('Location: login.php');
            exit;
        }
        $error = 'Registration failed (email may already exist).';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    /* Page-scoped styling for register UI (matches login) */
    body.register-page{background:linear-gradient(135deg,#a1c4fd 0%, #c2e9fb 35%, #d4c2fc 100%)}
    .auth-card{width:400px;max-width:95%;margin:2.5rem auto;background:rgba(255,255,255,.6);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.45);border-radius:16px;box-shadow:0 10px 30px rgba(31,38,135,.18);animation:fadeInUp .6s ease-out}
    @keyframes fadeInUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
    .field{position:relative;margin-bottom:.9rem}
    .field input{width:100%;padding:1.1rem 2.5rem .6rem 2.5rem;border:1px solid var(--border);border-radius:12px;background:rgba(255,255,255,.75);box-shadow:0 1px 2px rgba(0,0,0,.06);position:relative;z-index:1;}
    .field label{position:absolute;left:2.5rem;top:.9rem;color:var(--muted);font-size:.95rem;transition:all .18s ease;padding:0 .25rem;pointer-events:none;z-index:2;background:transparent;}
    .field input:focus{outline:none;border-color:#9ab6ff;box-shadow:0 0 0 3px rgba(154,182,255,.25)}
    .field input::placeholder{color:transparent}
    .field input:focus + label, .field input:not(:placeholder-shown) + label{transform:translateY(-1.5rem) scale(.9);background:rgba(255,255,255,.95);border-radius:6px;padding:0 5px;left:1.5rem;z-index:3;box-shadow:0 0 0 2px rgba(255,255,255,.95);}
    .field .icon{position:absolute;left:.8rem;top:50%;transform:translateY(-50%);width:18px;height:18px;opacity:.7}
    .btn-gradient{background:linear-gradient(90deg,#0078ff 0%, #00bcd4 100%);border:none}
    .btn-gradient:hover{box-shadow:0 0 0 3px rgba(0,120,255,.15),0 10px 24px rgba(0,188,212,.35)}
    .muted a{color:inherit}
  </style>
</head>
<body class="register-page">
  <?php include __DIR__ . '/includes/header.php'; ?>
  <main class="site-main">
    <div class="container" style="max-width:720px">
      <div class="auth-card card">
        <div class="card-header">Create Account</div>
        <div class="card-body">
          <?php if (!empty($error)): ?>
            <div class="alert error mb-md"><?php echo h($error); ?></div>
          <?php endif; ?>
          <form method="post" autocomplete="on">
            <div class="field">
              <input type="text" name="name" required placeholder=" ">
              <label>Name</label>
              <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M4 20c1.2-3.2 4.4-5 8-5s6.8 1.8 8 5" stroke="currentColor" stroke-width="1.8" fill="none"/></svg>
            </div>
            <div class="field">
              <input type="email" name="email" required placeholder=" ">
              <label>Email</label>
              <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 8l8 5 8-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="5" width="18" height="14" rx="2" ry="2" stroke="currentColor" stroke-width="1.8" fill="none"/></svg>
            </div>
            <div class="field">
              <input type="password" name="password" required minlength="6" placeholder=" ">
              <label>Password</label>
              <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="11" width="18" height="10" rx="2" ry="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0110 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
            <div class="field">
              <input type="password" name="confirm" required minlength="6" placeholder=" ">
              <label>Confirm Password</label>
              <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="11" width="18" height="10" rx="2" ry="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 11V7a5 5 0 0110 0v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
            <div class="flex gap-sm mb-md" style="margin-top:.5rem;align-items:center;justify-content:space-between">
              <a class="muted" href="login.php">Back to login</a>
              <button type="submit" class="btn btn-gradient">Create account</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
  <footer class="site-footer">
    <div class="inner">© <?php echo date('Y'); ?> Wishlist Price Tracker</div>
  </footer>
  <script>
  // Clear placeholders on input focus
  document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.field input');
    inputs.forEach(input => {
      // Clear placeholder on input
      input.addEventListener('input', function() {
        this.setAttribute('data-has-value', this.value.length > 0);
      });
      
      // Handle initial state
      if (input.value) {
        input.setAttribute('data-has-value', 'true');
      }
    });
  });

  // Theme toggle (persist to localStorage)
  (function(){
    const STORAGE_KEY = 'wpt_theme';
    const root = document.documentElement;
    const saved = localStorage.getItem(STORAGE_KEY);
    if(saved === 'dark'){ root.classList.add('dark'); }
    const btn = document.createElement('button');
    btn.className = 'btn secondary toggle';
    btn.type = 'button';
    function label(){ btn.textContent = root.classList.contains('dark') ? 'Light mode' : 'Dark mode'; }
    label();
    btn.addEventListener('click',()=>{
      root.classList.toggle('dark');
      localStorage.setItem(STORAGE_KEY, root.classList.contains('dark') ? 'dark' : 'light');
      label();
    });
    document.body.appendChild(btn);
  })();
  </script>
</body>
</html>
