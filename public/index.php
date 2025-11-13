<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wishlist Price Tracker</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    /* Page-scoped styles for the marketing home */
    /* Mesh gradient + blurred abstract shapes */
    body.home-page{
      background:
        radial-gradient(60vw 30vw at 15% -10%, rgba(99,102,241,.25), transparent 60%),
        radial-gradient(60vw 30vw at 85% -10%, rgba(236,72,153,.22), transparent 60%),
        radial-gradient(50vw 25vw at 50% 120%, rgba(59,130,246,.18), transparent 60%),
        conic-gradient(from 210deg at 50% 20%, rgba(99,102,241,.08), rgba(236,72,153,.08), rgba(59,130,246,.08), rgba(99,102,241,.08));
      background-repeat: no-repeat, no-repeat, no-repeat, no-repeat;
      background-attachment: fixed, fixed, fixed, fixed;
      position: relative;
      overflow-x: hidden;
    }
    /* Blurred abstract blobs */
    body.home-page::before,
    body.home-page::after{
      content:"";
      position: fixed;
      inset: auto auto 10% -10%;
      width: 420px; height: 420px;
      background: radial-gradient(closest-side, rgba(99,102,241,.55), rgba(99,102,241,0));
      filter: blur(60px);
      opacity:.7;
      z-index: 0;
      pointer-events:none;
      transform: rotate(8deg);
    }
    body.home-page::after{
      inset: -10% -10% auto auto;
      background: radial-gradient(closest-side, rgba(236,72,153,.45), rgba(236,72,153,0));
      transform: rotate(-6deg);
    }
    .site-header,.site-footer, .hero, .container{ position: relative; z-index: 1; }
    .hero{padding:3rem 1rem;text-align:center}
    .hero h1{font-size:2.2rem;margin:0 0 .5rem}
    .hero p{color:var(--muted);max-width:720px;margin:.5rem auto 1.25rem}
    .cta{display:inline-flex;gap:.6rem}
    .btn-lg{padding:.8rem 1.15rem;font-size:1rem;border-radius:10px}
    .btn-gradient{background:linear-gradient(90deg,#0078ff 0%, #00bcd4 100%);border:none;color:#fff}
    .btn-gradient:hover{box-shadow:0 0 0 3px rgba(0,120,255,.15),0 10px 24px rgba(0,188,212,.35)}
    .grid{display:grid;gap:1rem}
    @media(min-width:720px){.grid.cols-3{grid-template-columns:repeat(3,1fr)}.grid.cols-2{grid-template-columns:repeat(2,1fr)}}
    .feature{background:rgba(255, 255, 255, 0.05);border:1px solid rgba(255, 255, 255, 0.1);border-radius:12px;padding:1.5rem;color:#e2e8f0}
    .muted{color:rgba(255, 255, 255, 0.6);font-size:.95rem}
    .mock{display:grid;grid-template-columns:1.2fr .8fr;gap:1rem;align-items:stretch}
    @media(max-width:900px){.mock{grid-template-columns:1fr}}
    .preview{background:rgba(255, 255, 255, 0.05);border:1px solid rgba(255, 255, 255, 0.1);border-radius:12px;padding:1.5rem;color:#e2e8f0}
    .testimonials .quote{background:rgba(37, 99, 235, 0.05);border:1px solid rgba(37, 99, 235, 0.1);border-radius:12px;padding:1.5rem;color:#e2e8f0}
    .logos{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:center}
    .logo-chip{border:1px solid var(--border);background:#fff;border-radius:999px;padding:.4rem .7rem;color:var(--muted)}
    .steps{display:grid;gap:1rem}
    @media(min-width:720px){.steps{grid-template-columns:repeat(3,1fr)}}
    .stat{background:rgba(255, 255, 255, 0.05);border:1px solid rgba(255, 255, 255, 0.1);border-radius:12px;padding:1.5rem;text-align:center;color:#e2e8f0}
    .fade{animation:fadeIn .6s ease both}
    @keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
    /* Enforce dark theme colors */
    body, .card, .feature, .preview, .quote, .stat { background: #0b1220; color: #e2e8f0; }

    /* Anime.js animation styles */
    .ml4 {
      position: relative;
      font-weight: 900;
      font-size: 4.5em;
    }
    .ml4 .letters {
      position: absolute;
      margin: auto;
      left: 0;
      top: 0.3em;
      right: 0;
      opacity: 0;
    }
  </style>
</head>
<body style="background: #0a192f; min-height: 100vh; overflow-x: hidden;">
  <div id="vanta-bg" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;"></div>
  <?php include __DIR__ . '/includes/header.php'; ?>

  <main class="site-main" style="position: relative; z-index: 1;">
    <!-- Hero -->
    <section class="hero fade" id="hero">
      <div class="container">
        <h1>Track, save, and get notified when prices drop.</h1>
        <p>Monitor products across your favorite stores, see price history at a glance, and receive real-time alerts the moment a deal hits your target.</p>
        <div class="cta">
          <?php if (!empty($_SESSION['user_id'])): ?>
            <a class="btn btn-gradient btn-lg" href="dashboard.php">Go to Dashboard</a>
          <?php else: ?>
            <a class="btn btn-gradient btn-lg" href="register.php">Start Tracking</a>
            <a class="btn secondary btn-lg" href="login.php">Login</a>
            <a class="btn secondary btn-lg" href="#features" data-scroll>See features</a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Modern Visual Mockup -->
    <section class="container mock fade" id="preview">
      <div class="preview elevated">
        <div class="muted mb-sm">Live Trend Preview</div>
        <canvas id="homeChart" height="120"></canvas>
      </div>
      <div class="preview elevated">
        <div class="muted mb-sm">Example Product Card</div>
        <div class="card elevated" style="border:none">
          <div class="card-body">
            <div class="media mb-sm">
              <img src="https://via.placeholder.com/64" alt="" style="border-radius:8px">
              <div>
                <strong>Noise Cancelling Headphones</strong><br>
                <small class="muted">Amazon • <span class="below">Price drop</span></small>
              </div>
            </div>
            <div class="flex" style="align-items:center;gap:.75rem">
              <div><div class="muted-sm">Current</div><div style="font-weight:700">$129.99</div></div>
              <div><div class="muted-sm">Target</div><div>$149.00</div></div>
              <div class="chip">-13%</div>
              <div class="right"><a class="btn" href="register.php">Track now</a></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Animated Ready Set Go Section -->
    <section class="container fade" style="margin-top:1.5rem;text-align:center;position:relative;min-height:150px;margin-bottom:4rem" id="features">
  <h1 class="ml4">
    <span class="letters letters-1">Real time price alerts</span>
    <span class="letters letters-2">Price history tracking</span>
    <span class="letters letters-3">Multi-store comparison</span>
    <span class="letters letters-4">email notifications</span>
    <span class="letters letters-5">Simple & fast</span>
    <span class="letters letters-6">Secure</span>
  </h1>
</section>


    <!-- Social Proof -->
    <section class="container testimonials fade" style="margin-top:4rem" id="social-proof">
      <div class="grid cols-2">
        <div class="quote elevated">“Saved me hundreds during the holiday sales. The alerts are instant and spot-on.”<br><small class="muted">— Priya, power shopper</small></div>
        <div class="quote elevated">“Price history made it easy to time my purchase. Brilliant.”<br><small class="muted">— Daniel, tech enthusiast</small></div>
      </div>
    </section>

    <!-- Supported Logos -->
    <section class="container fade" style="margin-top:1.5rem;text-align:center" id="logos">
      <div class="muted mb-sm">Works great with your favorite stores</div>
      <div class="logos">
        <!-- Minimal inline SVG brand placeholders -->
        <span class="logo-chip" title="Amazon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 16c2-5 12-5 14 0" stroke="#111" stroke-width="1.6"/><path d="M7 8c1-1 3-2 5-2 3 0 5 2 5 5v7" stroke="#111" stroke-width="1.6"/></svg> Amazon
        </span>
        <span class="logo-chip" title="eBay">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="#111" stroke-width="1.6"/><path d="M8 12h8" stroke="#111" stroke-width="1.6"/></svg> Flipkart
        </span>
        <span class="logo-chip" title="BestBuy">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="4" y="6" width="16" height="12" rx="2" stroke="#111" stroke-width="1.6"/><path d="M16 12l4 2-4 2z" fill="#111"/></svg> Myntra
        </span>
        <span class="logo-chip">Walmart</span>
        <span class="logo-chip">Target</span>
      </div>
    </section>

    <!-- How it works -->
    <section class="container fade" style="margin-top:1.5rem" id="how">
      <div class="steps">
        <div class="feature elevated"><div class="title">1) Add item</div><div class="desc">Paste a product URL and set your target price.</div></div>
        <div class="feature elevated"><div class="title">2) We track</div><div class="desc">Our engine monitors prices and trends for you.</div></div>
        <div class="feature elevated"><div class="title">3) Get notified</div><div class="desc">Instant email alert when price drops below your target.</div></div>
      </div>
    </section>

    <!-- Stats -->
    <section class="container fade" style="margin-top:1.5rem" id="stats">
      <div class="grid cols-3">
        <div class="stat elevated"><div class="muted-sm">Products tracked</div><div style="font-size:1.6rem;font-weight:800">10K+</div></div>
        <div class="stat elevated"><div class="muted-sm">Average savings</div><div style="font-size:1.6rem;font-weight:800">Up to 40%</div></div>
        <div class="stat elevated"><div class="muted-sm">Active users</div><div style="font-size:1.6rem;font-weight:800">1,000+</div></div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="hero fade" style="padding-top:1rem">
      <div class="container">
        <h2 style="margin:0 0 .6rem">Start tracking and never miss a price drop.</h2>
        <p>No spam. Just smart savings.</p>
        <?php if (!empty($_SESSION['user_id'])): ?>
          <a class="btn btn-gradient btn-lg" href="dashboard.php">Open Dashboard</a>
        <?php else: ?>
          <a class="btn btn-gradient btn-lg" href="register.php">Start Free</a>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="inner">  Wishlist Price Tracker</div>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/vanta@0.5.24/dist/vanta.birds.min.js"></script>
  <script src="../assets/js/app.js"></script>
  <script>
  // Force dark theme
  document.documentElement.classList.add('dark');
  </script>
  <script>
  // Initialize Vanta.js birds effect
  document.addEventListener('DOMContentLoaded', function() {
    VANTA.BIRDS({
      el: "#vanta-bg",
      mouseControls: true,
      touchControls: true,
      gyroControls: false,
      minHeight: 200.00,
      minWidth: 200.00,
      scale: 1.00,
      scaleMobile: 1.00,
      backgroundColor: 0x0a192f,
      color1: 0xff6b35,
      color2: 0x0ff0fc,
      birdSize: 1.20,
      speedLimit: 3.00,
      separation: 30.00,
      alignment: 30.00,
      cohesion: 30.00,
      quantity: 3.00
    });
  });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/2.0.2/anime.min.js"></script>
  <script>
    // Home preview chart (mock data)
    const ctx = document.getElementById('homeChart');
    if(ctx){
      const labels = Array.from({length:12},(_,i)=>`Day ${i+1}`);
      const prices = [199,196,194,192,189,187,184,178,169,159,149,129];
      new Chart(ctx,{type:'line',data:{labels,datasets:[{label:'Sample Price',data:prices,borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.1)',fill:true,tension:.25}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:false}}}});
    }

    // Smooth scroll for CTA links with data-scroll
    document.querySelectorAll('[data-scroll]').forEach(a=>{
      a.addEventListener('click', (e)=>{
        const href = a.getAttribute('href');
        if(href && href.startsWith('#')){
          e.preventDefault();
          const el = document.querySelector(href);
          if(el){ el.scrollIntoView({behavior:'smooth', block:'start'}); }
        }
      })
    });

    // Anime.js animation for "Ready Set Go!"
    var ml4 = {};
    ml4.opacityIn = [0,1];
    ml4.scaleIn = [0.2, 1];
    ml4.scaleOut = 3;
    ml4.durationIn = 800;
    ml4.durationOut = 600;
    ml4.delay = 500;

    anime.timeline({loop: true})
      .add({
        targets: '.ml4 .letters-1',
        opacity: ml4.opacityIn,
        scale: ml4.scaleIn,
        duration: ml4.durationIn
      }).add({
        targets: '.ml4 .letters-1',
        opacity: 0,
        scale: ml4.scaleOut,
        duration: ml4.durationOut,
        easing: "easeInExpo",
        delay: ml4.delay
      }).add({
        targets: '.ml4 .letters-2',
        opacity: ml4.opacityIn,
        scale: ml4.scaleIn,
        duration: ml4.durationIn
      }).add({
        targets: '.ml4 .letters-2',
        opacity: 0,
        scale: ml4.scaleOut,
        duration: ml4.durationOut,
        easing: "easeInExpo",
        delay: ml4.delay}).add({
        targets: '.ml4 .letters-3',
        opacity: ml4.opacityIn,
        scale: ml4.scaleIn,
        duration: ml4.durationIn}).add({
        targets: '.ml4 .letters-3',
        opacity: 0,
        scale: ml4.scaleOut,
        duration: ml4.durationOut,
        easing: "easeInExpo",
        delay: ml4.delay}).add({
        targets: '.ml4 .letters-4',
        opacity: ml4.opacityIn,
        scale: ml4.scaleIn,
        duration: ml4.durationIn}).add({
        targets: '.ml4 .letters-4',
        opacity: 0,
        scale: ml4.scaleOut,
        duration: ml4.durationOut,
        easing: "easeInExpo",
        delay: ml4.delay}).add({
        targets: '.ml4 .letters-5',
        opacity: ml4.opacityIn,
        scale: ml4.scaleIn,
        duration: ml4.durationIn}).add({
        targets: '.ml4 .letters-5',
        opacity: 0,
        scale: ml4.scaleOut,
        duration: ml4.durationOut,
        easing: "easeInExpo",
        delay: ml4.delay}).add({
        targets: '.ml4 .letters-6',
        opacity: ml4.opacityIn,
        scale: ml4.scaleIn,
        duration: ml4.durationIn}).add({
        targets: '.ml4 .letters-6',
        opacity: 0,
        scale: ml4.scaleOut,
        duration: ml4.durationOut,
        easing: "easeInExpo",
        delay: ml4.delay})
        .add({
        targets: '.ml4',
        opacity: 0,
        duration: 500,
        delay: 500
      });
  </script>
</body>
</html>
