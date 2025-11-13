<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$tagFilter = trim($_GET['tag'] ?? '');
$stmt = $db->prepare('SELECT w.id as wid, w.user_id, w.product_id, w.target_price, w.note, w.tag, w.alert_sent, p.name, p.url, p.image_url, p.store_name, p.current_price, p.last_checked
                      FROM wishlist w
                      JOIN products p ON p.id = w.product_id
                      WHERE w.user_id = ?' . ($tagFilter !== '' ? ' AND w.tag = ?' : '') . '
                      ORDER BY p.name');
if ($tagFilter !== '') {
    $stmt->execute([$_SESSION['user_id'], $tagFilter]);
} else {
    $stmt->execute([$_SESSION['user_id']]);
}
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = count($items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    .below{color:#0a7a0a;font-weight:600}
    .above{color:#b00020}
  </style>
</head>
<body style="background: #0a192f; min-height: 100vh; overflow-x: hidden;">
  <div id="vanta-bg" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;"></div>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <main class="site-main" style="position: relative; z-index: 1;">
    <div class="container">
      <div class="dashboard-wrap fade-in">
        <header class="mb-md" style="display:flex;align-items:center">
          <div>
            <h1 class="mb-sm" style="margin:0;font-size:1.6rem">Your Wishlist <span class="chip gradient"><?php echo (int)$count; ?></span></h1>
            <div class="muted">Track your items and get notified when they drop below your target price.</div>
          </div>
        </header>

        <div class="card mb-md">
          <div class="card-header">Filter & Manage</div>
          <div class="card-body">
          <form method="get" class="mb-md" style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
            <div>
              <label>Filter by tag</label>
              <input type="text" name="tag" value="<?php echo h($tagFilter); ?>" placeholder="e.g., gifts" style="max-width:240px">
            </div>
            <div style="display:flex;gap:.5rem">
              <button type="submit" class="gradient btn">Apply</button>
              <a class="btn secondary" href="dashboard.php">Clear</a>
            </div>
          </form>

        <table class="table glass-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Current Price</th>
              <th>Target Price</th>
              <th>from Target</th>
              <th>Tag</th>
              <th>Note</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it):
              $pct = $it['target_price'] > 0 ? (($it['current_price'] - $it['target_price']) / $it['target_price']) * 100 : 0;
              // Percentage severity classes
              if ($pct <= 0) { $pctClass = 'pct pct-good'; }
              elseif ($pct <= 15) { $pctClass = 'pct pct-warn'; }
              else { $pctClass = 'pct pct-bad'; }
            ?>
            <tr data-wid="<?php echo (int)$it['wid']; ?>" data-pid="<?php echo (int)$it['product_id']; ?>">
              <td>
                <div class="media">
                  <?php if (!empty($it['image_url'])): ?><img src="<?php echo h($it['image_url']); ?>" alt=""><?php endif; ?>
                  <div>
                    <a href="product.php?product_id=<?php echo (int)$it['product_id']; ?>"><?php echo h($it['name']); ?></a><br>
                    <small class="muted"><?php echo h($it['store_name']); ?> • <a href="<?php echo h($it['url']); ?>" target="_blank">View</a></small>
                  </div>
                </div>
              </td>
              <td>
                <div style="display: flex; align-items: center;">
                  <span class="price" id="price-<?php echo (int)$it['product_id']; ?>">₹<?php echo number_format((float)$it['current_price'], 0); ?></span>
                  <span id="price-change-<?php echo (int)$it['product_id']; ?>" class="price-change"></span>
                </div>
                <small class="muted" id="last-checked-<?php echo (int)$it['product_id']; ?>">Checked: <?php echo h($it['last_checked']); ?></small>
              </td>
              <td>₹<?php echo number_format((float)$it['target_price'], 0); ?></td>
              <td class="<?php echo $pctClass; ?>"><?php echo number_format($pct, 2); ?>%</td>
              <td><?php echo $it['tag'] !== '' ? '<span class="chip gradient">'.h($it['tag']).'</span>' : ''; ?></td>
              <td><?php echo nl2br(h($it['note'])); ?></td>
              <td>
                <div class="row-actions">
                  <form method="post" action="wishlist_edit.php" style="display:inline">
                    <input type="hidden" name="wid" value="<?php echo (int)$it['wid']; ?>">
                    <input type="number" step="0.01" name="target_price" value="<?php echo h($it['target_price']); ?>" style="width:7rem">
                    <input type="text" name="tag" value="<?php echo h($it['tag']); ?>" placeholder="tag" style="width:6rem">
                    <input type="text" name="note" value="<?php echo h($it['note']); ?>" placeholder="note" style="width:10rem">
                    <button type="submit" class="btn gradient">Save</button>
                  </form>
                  <form method="post" action="wishlist_delete.php" style="display:inline" onsubmit="return confirm('Remove from wishlist?');">
                    <input type="hidden" name="wid" value="<?php echo (int)$it['wid']; ?>">
                    <button type="submit" class="danger">Delete</button>
                  </form>
                  <button class="btn gradient" onclick="refreshPrice(<?php echo (int)$it['product_id']; ?>, this)">
                    <span class="refresh-text">Refresh</span>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <tr><td colspan="7" class="muted">No items yet. Click <a href="add_product.php">Add Product</a> to start tracking.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
          </div>
        </div>
      </div>
    </div>
  </main>
  <footer class="site-footer">
    <div class="inner">  Wishlist Price Tracker</div>
  </footer>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/vanta@0.5.24/dist/vanta.fog.min.js"></script>
  <script src="../assets/js/app.js"></script>
  <script>
  // Initialize Vanta.js cells effect
  document.addEventListener('DOMContentLoaded', function() {
    VANTA.FOG({
      el: "#vanta-bg",
      mouseControls: true,
  touchControls: true,
  gyroControls: false,
  minHeight: 200.00,
  minWidth: 200.00,
  highlightColor: 0xe3b521,
  midtoneColor: 0x191663,
  lowlightColor: 0x907d15,
  baseColor: 0xffefeb
    });
  });
  </script>
  <script>
  async function refreshPrice(pid, btn) {
    const originalText = btn.innerHTML;
    const priceElement = document.getElementById('price-'+pid);
    const oldPrice = parseFloat(priceElement ? priceElement.textContent.replace(/[^0-9.]/g, '') : 0);
    
    try {
      // Show loading state
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner"></span>';
      
      const response = await fetch('price_refresh.php?product_id=' + encodeURIComponent(pid));
      const data = await response.json();
      
      if (data.ok) {
        const newPrice = parseFloat(data.data.current_price);
        
        // Update the price with animation
        if (priceElement) {
          priceElement.textContent = '₹' + newPrice.toFixed(0);
          
          // Add animation class if price changed
          if (oldPrice !== newPrice) {
            priceElement.classList.add('price-updated');
            setTimeout(() => priceElement.classList.remove('price-updated'), 1000);
            
            // Update price change indicator
            const priceChangeEl = document.getElementById('price-change-'+pid);
            if (priceChangeEl) {
              const change = newPrice - oldPrice;
              priceChangeEl.textContent = change >= 0 ? `+₹${Math.abs(change).toFixed(0)}` : `-₹${Math.abs(change).toFixed(0)}`;
              priceChangeEl.className = 'price-change ' + (change >= 0 ? 'price-up' : 'price-down');
              setTimeout(() => priceChangeEl.className = 'price-change', 2000);
            }
          }
        }
        
        // Update the "last checked" time
        const lastCheckedEl = document.getElementById('last-checked-'+pid);
        if (lastCheckedEl) {
          lastCheckedEl.textContent = 'Just now';
        }
        
        // Show success feedback
        showToast('Price updated successfully!', 'success');
      } else {
        throw new Error(data.error || 'Failed to refresh price');
      }
    } catch (error) {
      console.error('Error refreshing price:', error);
      showToast(error.message || 'Failed to refresh price', 'error');
    } finally {
      // Restore button state
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  }
  
  function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Hide and remove toast after delay
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }
  </script>
  <style>
  /* Toast notifications */
  .toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #333;
    color: white;
    padding: 12px 24px;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.3s, transform 0.3s;
    z-index: 1000;
  }
  
  .toast.show {
    opacity: 1;
    transform: translateY(0);
  }
  
  .toast-success {
    background: #4caf50;
  }
  
  .toast-error {
    background: #f44336;
  }
  
  /* Price update animation */
  @keyframes priceUpdate {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); color: #4caf50; }
    100% { transform: scale(1); }
  }
  
  .price-updated {
    animation: priceUpdate 0.6s ease-in-out;
    display: inline-block;
  }
  
  .price-change {
    font-size: 0.8em;
    margin-left: 8px;
    opacity: 0.8;
    transition: opacity 0.3s;
  }
  
  .price-up { color: #4caf50; }
  .price-down { color: #f44336; }
  
  /* Spinner for refresh button */
  .spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 0.8s ease-in-out infinite;
  }
  
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
  </style>
  <script>
  // Force dark theme
  document.documentElement.classList.add('dark');
  </script>
</body>
</html>
