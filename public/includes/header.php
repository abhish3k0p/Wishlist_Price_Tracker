<header class="site-header">
  <div class="inner">
    <div class="brand">
      <div class="logo-container">
        <svg width="32" height="32" viewBox="0 0 128 128" class="logo-svg">
          <filter id="displacementFilter">
            <feTurbulence type="turbulence" numOctaves="2" baseFrequency="0" result="turbulence"/>
            <feDisplacementMap in2="turbulence" in="SourceGraphic" scale="1" xChannelSelector="R" yChannelSelector="G"/>
          </filter>
          <polygon points="64 128 8.574 96 8.574 32 64 0 119.426 32 119.426 96" fill="currentColor" style="color: #FF6B35"/>
        </svg>
      </div>
      <span>Wishlist Price Tracker</span>
    </div>
    <nav class="right">
      <a href="index.php">Home</a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php">Dashboard</a>
        <a href="add_product.php">Add Product</a>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<style>
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 700;
  font-size: 1.25rem;
}

.logo-container {
  display: flex;
  align-items: center;
}

.logo-svg {
  width: 32px;
  height: 32px;
  color: #FF6B35;
  filter: url(#displacementFilter);
  transition: transform 0.3s ease;
}

.logo-svg:hover {
  transform: rotate(15deg);
}
</style>

<script type="module">
import { animate } from 'https://esm.sh/animejs';

const initLogoAnimation = () => {
  const svg = document.querySelector('.logo-svg');
  if (!svg) return;

  animate(['feTurbulence', 'feDisplacementMap'], {
  baseFrequency: .005,
  scale: 15,
  alternate: true,
  loop: true
});

  animate('polygon', {
  points: '64 68.64 8.574 100 63.446 67.68 64 4 64.554 67.68 119.426 100',
  alternate: true,
  loop: true
});
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initLogoAnimation);
} else {
  initLogoAnimation();
}
</script>
