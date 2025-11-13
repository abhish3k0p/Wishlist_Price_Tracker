<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
if (empty($_SESSION['is_admin'])) { header('Location: ../public/dashboard.php'); exit; }
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid = (int)($_POST['product_id'] ?? 0);
    if ($pid > 0 && $action === 'delete') {
        $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$pid]);
    }
    header('Location: products.php');
    exit;
}

$products = $db->query('SELECT p.id, p.name, p.store_name, p.current_price, p.last_checked,
   (SELECT COUNT(*) FROM wishlist w WHERE w.product_id=p.id) as trackers
   FROM products p ORDER BY p.name')->fetchAll(PDO::FETCH_ASSOC);

$view_pid = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$trackers = [];
if ($view_pid) {
    $st = $db->prepare('SELECT u.id, u.name, u.email, w.target_price, w.created_at
                        FROM wishlist w JOIN users u ON u.id=w.user_id
                        WHERE w.product_id=? ORDER BY w.created_at DESC');
    $st->execute([$view_pid]);
    $trackers = $st->fetchAll(PDO::FETCH_ASSOC);
}
?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Admin Products</title>
<link rel="stylesheet" href="../assets/css/style.css"></head><body>
<h2>Products</h2>
<p><a href="dashboard.php">← Back</a></p>
<table style="border-collapse:collapse;width:100%">
  <thead>
    <tr><th style="text-align:left">ID</th><th>Name</th><th>Store</th><th>Current Price</th><th>Last Checked</th><th>Trackers</th><th>Actions</th></tr>
  </thead>
  <tbody>
  <?php foreach($products as $p): ?>
    <tr>
      <td><?php echo (int)$p['id']; ?></td>
      <td><?php echo htmlspecialchars($p['name']); ?></td>
      <td><?php echo htmlspecialchars($p['store_name']); ?></td>
      <td>₹<?php echo number_format((float)$p['current_price'], 0); ?></td>
      <td><?php echo htmlspecialchars($p['last_checked']); ?></td>
      <td><?php echo (int)$p['trackers']; ?></td>
      <td>
        <a href="products.php?product_id=<?php echo (int)$p['id']; ?>">View Trackers</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this product? This removes it from all wishlists.');">
          <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
          <button type="submit" name="action" value="delete">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php if ($view_pid): ?>
<h3>Tracking Users for Product #<?php echo (int)$view_pid; ?></h3>
<table style="border-collapse:collapse;width:100%">
  <thead><tr><th>User</th><th>Email</th><th>Target Price</th><th>Added</th></tr></thead>
  <tbody>
  <?php foreach($trackers as $t): ?>
    <tr>
      <td><?php echo htmlspecialchars($t['name']); ?></td>
      <td><?php echo htmlspecialchars($t['email']); ?></td>
      <td>₹<?php echo number_format((float)$t['target_price'], 0); ?></td>
      <td><?php echo htmlspecialchars($t['created_at']); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (empty($trackers)): ?>
    <tr><td colspan="4">No trackers.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
<?php endif; ?>
</body></html>
