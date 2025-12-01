<?php
// index.php — Enhanced ZeroHunger Landing Page with Pastel Blue, White & Purple Theme + Animations
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ZeroHunger — Share Surplus, Feed Communities</title>

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <meta name="description" content="ZeroHunger — connecting surplus food with communities in need through smart matching, volunteers, and real-time coordination.">

  <style>
    :root {
      --blue: #c7d2fe;
      --purple: #e9d5ff;
      --white: #ffffff;
      --primary: #6d28d9;
      --accent: #7dd3fc;
      --muted: #6b7280;
      --card: rgba(255, 255, 255, 0.85);
      --glass-border: rgba(255, 255, 255, 0.35);
      --radius: 14px;
      --shadow-sm: 0 6px 20px rgba(16, 24, 40, 0.08);
      --shadow-lg: 0 18px 50px rgba(16, 24, 40, 0.12);
      --max-width: 1200px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; scroll-behavior: smooth; }
    body {
      font-family: "Poppins", system-ui, sans-serif;
      background: linear-gradient(135deg, var(--blue) 0%, var(--purple) 50%, #f5f3ff 100%);
      color: #1e1b4b;
      line-height: 1.6;
      overflow-x: hidden;
    }

    a { color: inherit; text-decoration: none; transition: color 0.3s ease; }
    img { max-width: 100%; display: block; height: auto; }
    .container { width: 100%; max-width: var(--max-width); margin: 0 auto; padding: 0 1rem; }

    /* HEADER */
    header {
      position: sticky; top: 0; z-index: 100;
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(255,255,255,0.3);
      box-shadow: var(--shadow-sm);
      animation: fadeInDown 1s ease forwards;
    }
    .nav {
      display: flex; align-items: center; justify-content: space-between; padding: 1rem 0;
    }
    .brand {
      display: flex; align-items: center; gap: .6rem;
      font-weight: 700; color: var(--primary); font-size: 1.3rem;
    }
    .brand img { width: 42px; height: 42px; border-radius: 8px; box-shadow: var(--shadow-sm); }

    nav.desktop-links { display: none; }
    .btn {
      padding: .6rem 1.2rem; border-radius: 999px; font-weight: 600;
      border: none; cursor: pointer;
      display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
      transition: all 0.3s ease;
    }
    .btn-primary {
      background: linear-gradient(90deg, var(--primary), #9333ea);
      color: white; box-shadow: 0 6px 20px rgba(109, 40, 217, 0.3);
    }
    .btn-primary:hover { transform: translateY(-4px); }
    .btn-ghost {
      background: transparent; border: 1px solid var(--primary);
      color: var(--primary);
    }
    .btn-ghost:hover {
      background: var(--primary); color: white; transform: translateY(-4px);
    }

    /* HERO SECTION */
    .hero {
      position: relative; display: flex; align-items: center; justify-content: center;
      min-height: 85vh; overflow: hidden; padding: 4rem 0;
    }
    .hero-video {
      position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
      z-index: 0; filter: brightness(0.7) saturate(1.2);
    }
    .hero-overlay {
      position: absolute; inset: 0; z-index: 1;
      background: linear-gradient(180deg, rgba(109, 40, 217, 0.3), rgba(125, 211, 252, 0.2));
    }
    .hero-inner {
      position: relative; z-index: 2;
      display: grid; grid-template-columns: 1fr; gap: 1.5rem;
      background: rgba(255,255,255,0.85); border-radius: var(--radius);
      box-shadow: var(--shadow-lg); padding: 2rem; backdrop-filter: blur(6px);
      animation: fadeInUp 1.5s ease forwards;
    }

    .kicker {
      display: inline-block;
      background: linear-gradient(90deg, var(--accent), var(--purple));
      padding: .25rem .8rem; border-radius: 999px;
      font-weight: 600; color: #1e1b4b;
      margin-bottom: .6rem;
      animation: pulse 3s infinite;
    }

    .hero h1 {
      font-size: clamp(1.8rem, 3.5vw, 2.8rem);
      color: #1e1b4b; font-weight: 700; margin-bottom: .8rem;
    }

    .hero p.lead { color: var(--muted); font-size: 1.05rem; margin-bottom: 1.2rem; }

    .hero-stats {
      display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem;
    }

    .stat {
      background: var(--card); border-radius: 12px;
      padding: .8rem 1rem; box-shadow: var(--shadow-sm);
      text-align: center; min-width: 110px;
      transition: transform 0.3s ease;
    }
    .stat:hover { transform: translateY(-5px); }
    .stat h3 { color: var(--primary); font-size: 1.1rem; }
    .stat p { color: var(--muted); font-size: .85rem; }

    /* SECTIONS */
    section.content {
      padding: 3.5rem 0; animation: fadeInUp 1s ease forwards;
    }

    h2 {
      text-align: center; margin-bottom: 2rem;
      color: var(--primary); font-size: 2rem; position: relative;
    }
    h2::after {
      content: ''; display: block; width: 60px; height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--accent));
      margin: .5rem auto 0; border-radius: 2px;
    }

    .how-grid {
      display: grid; gap: 1.5rem;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .how-card {
      background: var(--card); padding: 1.5rem;
      border-radius: 14px; border: 1px solid var(--glass-border);
      box-shadow: var(--shadow-sm);
      transition: all 0.3s ease;
      text-align: center;
      animation: floatY 6s ease-in-out infinite;
    }
    .how-card:hover { transform: translateY(-8px) scale(1.03); box-shadow: var(--shadow-lg); }

    /* FOOTER */
    footer {
      background: linear-gradient(180deg, #ede9fe, #e0f2fe);
      padding: 2rem 0; text-align: center; border-top: 1px solid rgba(0,0,0,0.05);
    }
    footer small { color: var(--muted); }

    /* RESPONSIVENESS */
    @media(min-width:900px){
      .hero-inner { grid-template-columns: 1fr 320px; }
      nav.desktop-links { display: flex; gap: 1.2rem; align-items: center; }
      .mobile-toggle { display: none; }
    }

    @media(max-width:899px){
      header { padding: .5rem 0; }
      .hero-inner { margin: 1rem; }
      .hero h1 { font-size: 2rem; }
      .stat { flex: 1 1 45%; }
    }

    /* ANIMATIONS */
    @keyframes fadeInUp {
      0% { opacity: 0; transform: translateY(30px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInDown {
      0% { opacity: 0; transform: translateY(-30px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse {
      0%,100% { opacity: 1; transform: scale(1); }
      50% { opacity: .85; transform: scale(1.05); }
    }
    @keyframes floatY {
      0%,100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
  </style>
</head>

<body>
  <header>
    <div class="container nav">
      <div class="brand">
        <img src="images/zero.png" alt="ZeroHunger logo">
        ZeroHunger
      </div>
      <nav class="desktop-links">
        <a href="#how">How it works</a>
        <a href="#features">Features</a>
        <a href="#mission">Mission</a>
        <a href="#gallery">Impact</a>
        <a href="#stories">Stories</a>
        <div class="cta-row">
          <a href="register.html" class="btn btn-primary">Get Involved</a>
          <a href="login.php" class="btn btn-ghost">Sign In</a>
        </div>
      </nav>
      <div class="mobile-toggle">
        <a href="pages/login.html" class="btn btn-ghost"><i class="fa-solid fa-right-to-bracket"></i></a>
      </div>
    </div>
  </header>

  <main>
    <!-- HERO -->
    <section class="hero">
      <video class="hero-video" autoplay muted loop playsinline>
        <source src="images/bg.mp4" type="video/mp4">
      </video>
      <div class="hero-overlay"></div>

      <div class="container hero-inner">
        <div class="hero-content">
          <span class="kicker">Community • Rescue • Impact</span>
          <h1>ZeroHunger — turning surplus into sustenance for communities everywhere</h1>
          <p class="lead">We connect food donors, volunteers, and charities through smart matching, safe handling, and real-time coordination — preventing waste and feeding families.</p>
          <div style="display:flex;gap:.75rem;flex-wrap:wrap">
            <a href="register.html" class="btn btn-primary">Join ZeroHunger</a>
            <a href="#how" class="btn btn-ghost">See How It Works</a>
          </div>
          <div class="hero-stats">
            <div class="stat"><h3>2.4M lb</h3><p>Pounds Rescued</p></div>
            <div class="stat"><h3>89K</h3><p>Meals Enabled</p></div>
            <div class="stat"><h3>1,234</h3><p>Active Partners</p></div>
          </div>
        </div>
        <aside class="hero-card">
          <div class="how-card">
            <h4>Today's Activity</h4>
            <p>Live pickups & community efforts near you</p>
            <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=60" alt="Volunteers">
          </div>
          <div style="margin-top:.9rem">
            <a href="pages/login.html" class="btn btn-ghost" style="width:100%">Dashboard</a>
          </div>
        </aside>
      </div>
    </section>

    <!-- HOW IT WORKS -->
    <section id="how" class="content">
      <div class="container">
        <h2>How It Works</h2>
        <div class="how-grid">
          <div class="how-card"><i class="fa-solid fa-utensils fa-2x"></i><h3>1. Donate Food</h3><p>Local restaurants, markets, and individuals list surplus food safely via our web app.</p></div>
          <div class="how-card"><i class="fa-solid fa-hands-helping fa-2x"></i><h3>2. Match & Rescue</h3><p>Our AI system matches donations with nearby charities and volunteers in real time.</p></div>
          <div class="how-card"><i class="fa-solid fa-truck-fast fa-2x"></i><h3>3. Deliver Safely</h3><p>Trained volunteers collect and deliver meals efficiently and safely.</p></div>
          <div class="how-card"><i class="fa-solid fa-people-group fa-2x"></i><h3>4. Feed Communities</h3><p>Partner organizations distribute rescued food to those in need.</p></div>
        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section id="features" class="content">
      <div class="container">
        <h2>Platform Features</h2>
        <div class="how-grid">
          <div class="how-card"><i class="fa-solid fa-map-location-dot fa-2x"></i><h3>Smart Geolocation</h3><p>Automatically finds the nearest donation matches.</p></div>
          <div class="how-card"><i class="fa-solid fa-bell fa-2x"></i><h3>Instant Alerts</h3><p>Volunteers get notified instantly when pickups are available.</p></div>
          <div class="how-card"><i class="fa-solid fa-chart-line fa-2x"></i><h3>Impact Tracking</h3><p>Track pounds rescued, meals delivered, and carbon offset.</p></div>
          <div class="how-card"><i class="fa-solid fa-heart-circle-check fa-2x"></i><h3>Verified Safety</h3><p>Food safety and traceability built-in.</p></div>
        </div>
      </div>
    </section>

    <!-- MISSION -->
    <section id="mission" class="content">
      <div class="container" style="text-align:center;max-width:900px">
        <h2>Our Mission</h2>
        <p style="margin-top:1rem;color:var(--muted);font-size:1.1rem">ZeroHunger exists to ensure that no meal goes to waste while people go hungry. By connecting communities through compassion, technology, and sustainability — we make sharing simple and saving lives possible.</p>
      </div>
    </section>

    <!-- GALLERY -->
    <section id="gallery" class="content">
      <div class="container">
        <h2>Impact in Action</h2>
        <div class="how-grid">
          <img src="https://images.unsplash.com/photo-1581574201279-98943a1a8c02?auto=format&fit=crop&w=800&q=60">
          <img src="https://images.unsplash.com/photo-1506784365847-bbad939e9335?auto=format&fit=crop&w=800&q=60">
          <img src="https://images.unsplash.com/photo-1526312426976-f4d754fa9bd6?auto=format&fit=crop&w=800&q=60">
          <img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=60">
        </div>
      </div>
    </section>

    <!-- STORIES -->
    <section id="stories" class="content">
      <div class="container" style="max-width:850px">
        <h2>Stories of Change</h2>
        <div class="how-card">
          <p>“Thanks to ZeroHunger, our small restaurant now donates leftovers daily instead of throwing them out. We've helped feed over 300 families in our area.”</p>
          <p style="margin-top:.6rem;font-weight:600;color:var(--primary)">— Maria, Local Restaurant Owner</p>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="container">
      <div style="font-weight:700;color:var(--primary);font-size:1.2rem">ZeroHunger</div>
      <div style="margin-top:.5rem;color:var(--muted)">© <?php echo date("Y"); ?> ZeroHunger — Connecting surplus food with communities in need.</div>
    </div>
  </footer>
</body>
</html>
