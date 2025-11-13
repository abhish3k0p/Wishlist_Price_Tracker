<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$product_id = (int)($_GET['product_id'] ?? 0);
if ($product_id<=0) { header('Location: dashboard.php'); exit; }
// Verify access and get product name
$stmt = $db->prepare('SELECT p.name FROM products p JOIN wishlist w ON w.product_id=p.id WHERE p.id=? AND w.user_id=? LIMIT 1');
$stmt->execute([$product_id, $_SESSION['user_id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="container">
    <nav class="mb-md"><a href="dashboard.php">← Back to Dashboard</a></nav>
    <div class="card">
      <div class="card-header"><?php echo h($row['name']); ?></div>
      <div class="card-body">
        <canvas id="chart" height="120"></canvas>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    async function loadHistory(){
      const res = await fetch('get_price_history.php?product_id=<?php echo (int)$product_id; ?>');
      const js = await res.json();
      if(!js.ok){ return; }
      const data = js.data;
      const labels = data.map(r=>new Date(r.checked_at).toLocaleString());
      const prices = data.map(r=>Number(r.price));
      const ctx = document.getElementById('chart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: { labels, datasets: [{ label: 'Price', data: prices, borderColor: '#2563eb', fill:false, tension:.2 }]},
        options: {}
      });
    }
    loadHistory();
  </script>
</body>
</html>
