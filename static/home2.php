<?php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UGPSC | Static Home Concept 2</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <style>
    :root {
      --race-green: #1f5a3f;
      --race-red: #cf4130;
      --race-black: #0f1214;
      --race-charcoal: #1a1f24;
      --race-sand: #e9deca;
      --race-ink: #d9d3c4;
    }

    body {
      font-family: "Outfit", sans-serif;
      color: var(--race-ink);
      background:
        linear-gradient(120deg, rgba(207, 65, 48, 0.08) 0%, transparent 40%),
        linear-gradient(305deg, rgba(31, 90, 63, 0.12) 0%, transparent 45%),
        repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.02) 0 8px, transparent 8px 16px),
        var(--race-black);
      min-height: 100vh;
    }

    h1, h2, h3, .brand-mark {
      font-family: "Oswald", sans-serif;
      letter-spacing: 0.03em;
      text-transform: uppercase;
    }

    .topline {
      background: #11161a;
      border-bottom: 1px solid rgba(233, 222, 202, 0.16);
      font-size: 0.9rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .site-nav-wrap {
      background: rgba(15, 18, 20, 0.92);
      border-bottom: 1px solid rgba(233, 222, 202, 0.2);
      backdrop-filter: blur(8px);
    }

    .navbar-brand img {
      height: 84px;
      width: auto;
      filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.38));
    }

    .nav-link {
      color: var(--race-sand) !important;
      font-weight: 500;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      font-size: 0.95rem;
    }

    .nav-link:hover,
    .nav-link:focus {
      color: #fff !important;
    }

    .btn-member {
      background: var(--race-red);
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      border: none;
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
    }

    .btn-member:hover {
      background: #b93527;
      color: #fff;
    }

    .hero {
      padding: 2rem 0;
    }

    .hero-frame {
      border: 1px solid rgba(233, 222, 202, 0.25);
      border-radius: 1rem;
      overflow: hidden;
      background: #12171b;
      box-shadow: 0 24px 50px rgba(0, 0, 0, 0.45);
    }

    .hero-main {
      min-height: 560px;
      background-image:
        linear-gradient(90deg, rgba(10, 12, 13, 0.82) 0%, rgba(10, 12, 13, 0.65) 45%, rgba(10, 12, 13, 0.25) 100%),
        url('/filestore/images/banners/banner1.jpg');
      background-size: cover;
      background-position: center;
      display: flex;
      align-items: center;
      position: relative;
    }

    .hero-main::after {
      content: "";
      position: absolute;
      inset: auto 0 0 0;
      height: 8px;
      background: linear-gradient(90deg, var(--race-red), var(--race-green));
    }

    .hero-copy {
      max-width: 700px;
      padding: 2.2rem;
      position: relative;
      z-index: 1;
      animation: fadeLift 0.8s ease;
    }

    .hero-tag {
      display: inline-block;
      background: rgba(31, 90, 63, 0.9);
      color: #fff;
      border: 1px solid rgba(233, 222, 202, 0.45);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      padding: 0.25rem 0.65rem;
      margin-bottom: 1rem;
      font-size: 0.82rem;
    }

    .hero-copy h1 {
      font-size: clamp(2.8rem, 6vw, 5.8rem);
      line-height: 0.95;
      margin-bottom: 0.8rem;
    }

    .hero-copy p {
      font-size: clamp(1.05rem, 2.2vw, 1.35rem);
      color: rgba(233, 222, 202, 0.95);
      max-width: 52ch;
      margin-bottom: 1.25rem;
    }

    .btn-hero-primary {
      background: var(--race-red);
      color: #fff;
      border: none;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      font-weight: 600;
      padding: 0.72rem 1.15rem;
      margin-right: 0.5rem;
    }

    .btn-hero-outline {
      background: transparent;
      color: var(--race-sand);
      border: 1px solid rgba(233, 222, 202, 0.6);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      font-weight: 600;
      padding: 0.72rem 1.15rem;
    }

    .btn-hero-primary:hover {
      background: #ad2d21;
      color: #fff;
    }

    .btn-hero-outline:hover {
      background: rgba(233, 222, 202, 0.12);
      color: #fff;
    }

    .section-space {
      padding: 2.8rem 0;
    }

    .race-card {
      height: 100%;
      background: linear-gradient(145deg, rgba(31, 90, 63, 0.18), rgba(26, 31, 36, 0.95));
      border: 1px solid rgba(233, 222, 202, 0.2);
      border-radius: 0.9rem;
      padding: 1.3rem;
    }

    .race-card h3 {
      font-size: 1.55rem;
      color: #fff;
      margin-bottom: 0.45rem;
    }

    .race-card p {
      margin-bottom: 0;
      color: rgba(217, 211, 196, 0.9);
    }

    .events-strip {
      background:
        linear-gradient(110deg, rgba(207, 65, 48, 0.92), rgba(31, 90, 63, 0.92)),
        url('/filestore/images/banners/banner3.jpg');
      background-size: cover;
      background-position: center;
      border-radius: 1rem;
      border: 1px solid rgba(233, 222, 202, 0.32);
      padding: 1.6rem;
    }

    .events-strip h2 {
      font-size: clamp(2rem, 4.4vw, 3.4rem);
      margin-bottom: 0.5rem;
      color: #fff;
    }

    .events-strip p {
      margin-bottom: 0;
      font-size: 1.1rem;
      color: rgba(255, 255, 255, 0.94);
    }

    .gallery-image {
      width: 100%;
      border-radius: 0.75rem;
      border: 1px solid rgba(233, 222, 202, 0.2);
      aspect-ratio: 4 / 3;
      object-fit: cover;
    }

    .footer-note {
      border-top: 1px solid rgba(233, 222, 202, 0.2);
      color: rgba(217, 211, 196, 0.8);
      font-size: 0.95rem;
    }

    @keyframes fadeLift {
      from {
        opacity: 0;
        transform: translateY(16px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 991.98px) {
      .navbar-brand img {
        height: 74px;
      }

      .hero-main {
        min-height: 500px;
      }
    }

    @media (max-width: 575.98px) {
      .navbar-brand img {
        height: 66px;
      }

      .hero-copy {
        padding: 1.4rem;
      }

      .hero-main {
        min-height: 440px;
      }
    }
  </style>
</head>
<body>
  <div class="topline py-2">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>UGPSC Motorcycle Road Race Supporters Club</div>
      <div>Alternative homepage concept 02</div>
    </div>
  </div>

  <div class="site-nav-wrap sticky-top">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
          <img src="/filestore/images/logos/ugpsc-logo.png" alt="UGPSC logo">
        </a>
        <button class="navbar-toggler text-light border border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#siteMenu" aria-controls="siteMenu" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="siteMenu">
          <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-2">
            <li class="nav-item"><a class="nav-link" href="/static/home.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home1.php">Club</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home2.php">Heritage</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home3.php">Events</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home4.php">Photos</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home6.php">Contact</a></li>
          </ul>
          <a href="/member-login.php" class="btn btn-member">Member Login</a>
        </div>
      </div>
    </nav>
  </div>

  <main>
    <section class="hero">
      <div class="container">
        <div class="hero-frame">
          <div class="hero-main">
            <div class="hero-copy">
              <span class="hero-tag">Road Race Support Since Day One</span>
              <h1>Fueling Race Weekends And Club Tradition</h1>
              <p>A sharper, bolder concept designed as a second homepage option. This version leans into a race-poster feel while keeping your core UGPSC identity front and center.</p>
              <a href="#about" class="btn btn-hero-primary">Explore The Club</a>
              <a href="#events" class="btn btn-hero-outline">Upcoming Events</a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="about" class="section-space">
      <div class="container">
        <div class="row g-3">
          <div class="col-md-4">
            <div class="race-card">
              <h3>Community First</h3>
              <p>UGPSC unites supporters, volunteers, and race fans to keep road racing visible, valued, and moving forward.</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="race-card">
              <h3>Rider Support</h3>
              <p>From fundraising to race-day momentum, members help create practical support where it matters most.</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="race-card">
              <h3>Club Legacy</h3>
              <p>Decades of stories, photos, and memories can be surfaced here through CMS-managed sections as the site evolves.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="history" class="section-space pt-1">
      <div class="container">
        <div class="events-strip d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h2>Built On Heritage. Aimed At The Next Era.</h2>
            <p>Designed to support rotating hero media, club updates, member journeys, and event promotion in a modular way.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <a href="#" class="btn btn-light text-dark text-uppercase">Club History</a>
            <a href="#" class="btn btn-outline-light text-uppercase">Join The Club</a>
          </div>
        </div>
      </div>
    </section>

    <section id="gallery" class="section-space">
      <div class="container">
        <div class="row g-3">
          <div class="col-md-4">
            <img class="gallery-image" src="/filestore/images/banners/banner2.jpg" alt="Motorcycle race action image">
          </div>
          <div class="col-md-4">
            <img class="gallery-image" src="/filestore/images/banners/banner3.jpg" alt="Road racing paddock scene">
          </div>
          <div class="col-md-4">
            <img class="gallery-image" src="/filestore/images/banners/banner1.jpg" alt="Rider and crowd atmosphere">
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer id="contact" class="footer-note py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
      <div>UGPSC Static Home Concept 02</div>
      <div>Alternative design route with enlarged logo and race-poster styling.</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
