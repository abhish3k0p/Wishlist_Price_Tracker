<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/scraper.php';
require_once __DIR__ . '/../includes/api_fetch.php';

$success = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = sanitize_url($_POST['url'] ?? '');
    $target_price = isset($_POST['target_price']) ? (float)$_POST['target_price'] : 0;
    $tag = trim($_POST['tag'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (!$url) {
        $error = 'Please provide a valid URL.';
    } elseif ($target_price <= 0) {
        $error = 'Please provide a valid target price.';
    } else {
        try {
            $db->beginTransaction();

            // Find or create product by URL
            $stmt = $db->prepare('SELECT * FROM products WHERE url = ? LIMIT 1');
            $stmt->execute([$url]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                // Try API price, then scraper
                $price = api_fetch_price($url);
                $meta = scrape_product($url);
                if ($price === null) { $price = (float)($meta['current_price'] ?? 0); }
                $name = $meta['name'] ?? parse_url($url, PHP_URL_HOST);
                $image_url = $meta['image_url'] ?? null;
                $store_name = $meta['store_name'] ?? (parse_url($url, PHP_URL_HOST) ?: '');

                $ins = $db->prepare('INSERT INTO products(name, url, image_url, store_name, current_price, last_checked) VALUES (?, ?, ?, ?, ?, NOW())');
                $ins->execute([$name, $url, $image_url, $store_name, $price]);
                $product_id = (int)$db->lastInsertId();
            } else {
                $product_id = (int)$product['id'];
            }

            // Link to wishlist (dedupe by unique key)
            $insW = $db->prepare('INSERT INTO wishlist(user_id, product_id, target_price, note, tag, alert_sent) VALUES (?, ?, ?, ?, ?, 0)
                                   ON DUPLICATE KEY UPDATE target_price = VALUES(target_price), note = VALUES(note), tag = VALUES(tag)');
            $insW->execute([$_SESSION['user_id'], $product_id, $target_price, $note, $tag]);

            $db->commit();
            $success = 'Product added to your wishlist.';
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            $error = 'Failed to add product.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Product</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: #0b1220; color: #e2e8f0;">
  <header class="site-header">
    <div class="inner">
      <div class="brand">Wishlist Price Tracker</div>
      <nav class="right">
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
      </nav>
    </div>
  </header>
  <main class="site-main">
    <div class="container">
      <div class="card">
        <div class="card-header">Add Product</div>
        <div class="card-body">
          <?php if (!empty($success)): ?>
            <div class="alert success mb-md"><?php echo h($success); ?></div>
          <?php endif; ?>
          <?php if (!empty($error)): ?>
            <div class="alert error mb-md"><?php echo h($error); ?></div>
          <?php endif; ?>
          <form method="post">
            <div class="row">
              <div style="flex:1 1 360px">
                <label>Product URL</label>
                <input type="url" name="url" required placeholder="https://example.com/product">
              </div>
              <div style="flex:0 0 200px">
                <label>Target Price</label>
                <input type="number" step="0.01" name="target_price" required>
              </div>
            </div>
            <div class="row mb-md">
              <div style="flex:0 0 240px">
                <label>Tag (optional)</label>
                <input type="text" name="tag" placeholder="e.g., gifts">
              </div>
              <div style="flex:1 1 360px">
                <label>Note (optional)</label>
                <textarea name="note" placeholder="Any notes..."></textarea>
              </div>
            </div>
            <div class="flex gap-sm">
              <button type="submit">Add</button>
              <a class="btn secondary" href="dashboard.php">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
  <footer class="site-footer">
    <div class="inner"> <?php echo date('Y'); ?> Wishlist Price Tracker</div>
  </footer>
  <script src="../assets/js/app.js"></script>
  <script>
  // Force dark theme
  document.documentElement.classList.add('dark');
  </script>
</body>
</html>
