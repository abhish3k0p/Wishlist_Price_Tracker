<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
if (empty($_SESSION['is_admin'])) { header('Location: ../public/dashboard.php'); exit; }
require_once __DIR__ . '/../includes/db.php';

// Totals
$total_users = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$total_products = (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
$total_wishlist = (int)$db->query('SELECT COUNT(*) FROM wishlist')->fetchColumn();

// Most popular stores (top 5)
$stores = $db->query('SELECT store_name, COUNT(*) as cnt FROM products GROUP BY store_name ORDER BY cnt DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

// Most tracked product
$most_tracked = $db->query('SELECT p.id, p.name, COUNT(w.id) cnt FROM products p JOIN wishlist w ON w.product_id=p.id GROUP BY p.id ORDER BY cnt DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: ['id'=>null,'name'=>null,'cnt'=>0];

// Daily additions (last 7 days)
$daily_users = $db->query("SELECT DATE(created_at) d, COUNT(*) c FROM users GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$daily_wishlist = $db->query("SELECT DATE(created_at) d, COUNT(*) c FROM wishlist GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);

?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Admin Analytics</title>
<link rel="stylesheet" href="../assets/css/style.css"></head><body>
<h2>Analytics</h2>
<p><a href="dashboard.php">← Back</a></p>

<div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
  <div style="border:1px solid #ddd;padding:1rem;min-width:200px"><strong>Total Users</strong><div style="font-size:1.5rem"><?php echo $total_users; ?></div></div>
  <div style="border:1px solid #ddd;padding:1rem;min-width:200px"><strong>Total Products</strong><div style="font-size:1.5rem"><?php echo $total_products; ?></div></div>
  <div style="border:1px solid #ddd;padding:1rem;min-width:200px"><strong>Total Wishlist Items</strong><div style="font-size:1.5rem"><?php echo $total_wishlist; ?></div></div>
  <div style="border:1px solid #ddd;padding:1rem;min-width:200px"><strong>Most Tracked Product</strong><div><?php echo htmlspecialchars($most_tracked['name'] ?? '—'); ?> (<?php echo (int)($most_tracked['cnt'] ?? 0); ?>)</div></div>
  
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
  <div>
    <h3>Top Stores</h3>
    <canvas id="storesChart" height="140"></canvas>
  </div>
  <div>
    <h3>Daily New Users (last 7)</h3>
    <canvas id="usersChart" height="140"></canvas>
  </div>
  <div>
    <h3>Daily Wishlist Adds (last 7)</h3>
    <canvas id="wishlistChart" height="140"></canvas>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const storesLabels = <?php echo json_encode(array_map(fn($r)=>($r['store_name']?:'Unknown'), $stores)); ?>;
const storesData = <?php echo json_encode(array_map(fn($r)=>(int)$r['cnt'], $stores)); ?>;

const dailyUsers = <?php echo json_encode(array_reverse($daily_users)); ?>;
const usersLabels = dailyUsers.map(r=>r.d);
const usersData = dailyUsers.map(r=>Number(r.c));

const dailyWishlist = <?php echo json_encode(array_reverse($daily_wishlist)); ?>;
const wishlistLabels = dailyWishlist.map(r=>r.d);
const wishlistData = dailyWishlist.map(r=>Number(r.c));

new Chart(document.getElementById('storesChart'), {
  type: 'bar', data: { labels: storesLabels, datasets: [{ label: 'Items', data: storesData, backgroundColor:'#60a5fa'}] }, options: { plugins: { legend: { display:false } } }
});
new Chart(document.getElementById('usersChart'), {
  type: 'line', data: { labels: usersLabels, datasets: [{ label: 'Users', data: usersData, borderColor:'#34d399', fill:false, tension:.2 }]}
});
new Chart(document.getElementById('wishlistChart'), {
  type: 'line', data: { labels: wishlistLabels, datasets: [{ label: 'Wishlist', data: wishlistData, borderColor:'#f59e0b', fill:false, tension:.2 }]}
});
</script>
</body></html>
