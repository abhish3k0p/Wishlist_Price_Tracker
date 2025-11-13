<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
if (empty($_SESSION['is_admin'])) { header('Location: ../public/dashboard.php'); exit; }
require_once __DIR__ . '/../includes/db.php';

$status = trim($_GET['status'] ?? '');
$pid = (int)($_GET['product_id'] ?? 0);
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');

$where = [];$args = [];
if ($status !== '') { $where[] = 'l.status = ?'; $args[] = $status; }
if ($pid > 0) { $where[] = 'l.product_id = ?'; $args[] = $pid; }
if ($from !== '') { $where[] = 'l.checked_at >= ?'; $args[] = $from; }
if ($to !== '') { $where[] = 'l.checked_at <= ?'; $args[] = $to; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT l.id, l.product_id, l.status, l.message, l.checked_at, p.name as product_name
        FROM scrape_logs l LEFT JOIN products p ON p.id=l.product_id
        $whereSql ORDER BY l.checked_at DESC LIMIT 500";
$stmt = $db->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Admin Logs</title>
<link rel="stylesheet" href="../assets/css/style.css"></head><body>
<h2>Logs</h2>
<p><a href="dashboard.php">← Back</a></p>
<form method="get" style="margin-bottom:1rem">
  <label>Status
    <select name="status">
      <option value="">Any</option>
      <option value="success" <?php echo $status==='success'?'selected':''; ?>>Success</option>
      <option value="error" <?php echo $status==='error'?'selected':''; ?>>Error</option>
    </select>
  </label>
  <label>Product ID <input type="number" name="product_id" value="<?php echo $pid?:''; ?>" style="width:8rem"></label>
  <label>From <input type="datetime-local" name="from" value="<?php echo htmlspecialchars($from); ?>"></label>
  <label>To <input type="datetime-local" name="to" value="<?php echo htmlspecialchars($to); ?>"></label>
  <button type="submit">Filter</button>
  <a href="logs.php">Clear</a>
  </form>

<table style="border-collapse:collapse;width:100%">
  <thead>
    <tr><th style="text-align:left">Time</th><th>Status</th><th>Product</th><th>Message</th></tr>
  </thead>
  <tbody>
  <?php foreach($rows as $r): ?>
    <tr>
      <td><?php echo htmlspecialchars($r['checked_at']); ?></td>
      <td><?php echo htmlspecialchars($r['status']); ?></td>
      <td>
        <?php if ($r['product_id']): ?>
          #<?php echo (int)$r['product_id']; ?> — <?php echo htmlspecialchars($r['product_name'] ?? ''); ?>
        <?php else: ?>
          —
        <?php endif; ?>
      </td>
      <td><?php echo htmlspecialchars($r['message']); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (empty($rows)): ?>
    <tr><td colspan="4">No logs found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</body></html>
