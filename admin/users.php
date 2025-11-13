<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
if (empty($_SESSION['is_admin'])) { header('Location: ../public/dashboard.php'); exit; }
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid > 0 && $uid !== (int)$_SESSION['user_id']) {
        if ($action === 'toggle_admin') {
            $stmt = $db->prepare('UPDATE users SET is_admin = 1 - is_admin WHERE id = ?');
            $stmt->execute([$uid]);
        } elseif ($action === 'delete') {
            $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$uid]);
        }
    }
    header('Location: users.php');
    exit;
}

$users = $db->query('SELECT u.id, u.name, u.email, u.is_admin, u.created_at,
    (SELECT COUNT(*) FROM wishlist w WHERE w.user_id = u.id) as wishlist_count
    FROM users u ORDER BY u.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Admin Users</title>
<link rel="stylesheet" href="../assets/css/style.css"></head><body>
<h2>Users</h2>
<p><a href="dashboard.php">← Back</a></p>
<table style="border-collapse:collapse;width:100%">
  <thead>
    <tr><th style="text-align:left">ID</th><th>Name</th><th>Email</th><th>Admin</th><th>Wishlist</th><th>Created</th><th>Actions</th></tr>
  </thead>
  <tbody>
  <?php foreach($users as $u): ?>
    <tr>
      <td><?php echo (int)$u['id']; ?></td>
      <td><?php echo htmlspecialchars($u['name']); ?></td>
      <td><?php echo htmlspecialchars($u['email']); ?></td>
      <td><?php echo $u['is_admin'] ? 'Yes' : 'No'; ?></td>
      <td><?php echo (int)$u['wishlist_count']; ?></td>
      <td><?php echo htmlspecialchars($u['created_at']); ?></td>
      <td>
        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
        <form method="post" style="display:inline">
          <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
          <button type="submit" name="action" value="toggle_admin"><?php echo $u['is_admin']?'Revoke Admin':'Make Admin'; ?></button>
        </form>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this user?');">
          <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
          <button type="submit" name="action" value="delete">Delete</button>
        </form>
        <?php else: ?>
          <em>Self</em>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</body></html>
