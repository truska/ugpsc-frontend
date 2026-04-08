<?php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UGPSC | Static Home Concept</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow+Condensed:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <style>
    :root {
      --ugpsc-green: #1f4a37;
      --ugpsc-red: #bf3b2b;
      --ugpsc-black: #111111;
      --ugpsc-cream: #f6f1e8;
      --ugpsc-gold: #ccb06e;
      --ugpsc-panel: #1a1a1a;
    }

    body {
      font-family: "Barlow Condensed", sans-serif;
      background:
        radial-gradient(circle at 10% 10%, rgba(204, 176, 110, 0.16), transparent 35%),
        radial-gradient(circle at 90% 0%, rgba(191, 59, 43, 0.16), transparent 35%),
        linear-gradient(180deg, #0b0d0d 0%, #111414 45%, #0d0f10 100%);
      color: var(--ugpsc-cream);
      min-height: 100vh;
    }

    h1, h2, h3, .navbar-brand {
      font-family: "Bebas Neue", sans-serif;
      letter-spacing: 0.06em;
    }

    .site-topline {
      background: linear-gradient(90deg, var(--ugpsc-red), #8e2518);
      color: #fff;
      font-size: 1rem;
      letter-spacing: 0.04em;
    }

    .site-nav-wrap {
      background: rgba(15, 17, 17, 0.92);
      border-bottom: 1px solid rgba(204, 176, 110, 0.25);
      backdrop-filter: blur(5px);
    }

    .navbar-brand img {
      height: 56px;
      width: auto;
    }

    .nav-link {
      font-size: 1.1rem;
      letter-spacing: 0.04em;
      color: rgba(246, 241, 232, 0.9) !important;
      text-transform: uppercase;
    }

    .nav-link:hover,
    .nav-link:focus {
      color: var(--ugpsc-gold) !important;
    }

    .btn-login {
      border: 1px solid var(--ugpsc-gold);
      color: var(--ugpsc-cream);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 0.45rem 1rem;
    }

    .btn-login:hover {
      background-color: var(--ugpsc-gold);
      color: #151515;
    }

    .hero {
      padding: 1.5rem 0 0;
    }

    .hero-carousel {
      border: 1px solid rgba(204, 176, 110, 0.25);
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
    }

    .hero-slide {
      min-height: 520px;
      background-position: center;
      background-size: cover;
      position: relative;
      display: flex;
      align-items: end;
    }

    .hero-slide::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.15) 10%, rgba(0, 0, 0, 0.82) 88%);
    }

    .hero-content {
      position: relative;
      z-index: 2;
      padding: 2.4rem;
      max-width: 760px;
      animation: riseIn 0.85s ease;
    }

    .hero-kicker {
      display: inline-block;
      background: rgba(31, 74, 55, 0.9);
      border: 1px solid rgba(246, 241, 232, 0.5);
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-size: 0.95rem;
      padding: 0.2rem 0.6rem;
      margin-bottom: 0.75rem;
    }

    .hero-content h1 {
      font-size: clamp(2.8rem, 6vw, 5.2rem);
      line-height: 0.95;
      margin-bottom: 0.6rem;
    }

    .hero-content p {
      font-size: clamp(1.15rem, 2.2vw, 1.45rem);
      max-width: 48ch;
      margin-bottom: 1rem;
    }

    .btn-primary-ugpsc {
      background: var(--ugpsc-red);
      color: #fff;
      border: none;
      font-size: 1.05rem;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      padding: 0.65rem 1.1rem;
    }

    .btn-primary-ugpsc:hover {
      background: #a83022;
      color: #fff;
    }

    .section-panel {
      background: rgba(17, 17, 17, 0.82);
      border: 1px solid rgba(204, 176, 110, 0.18);
      border-radius: 1rem;
      padding: 1.5rem;
      height: 100%;
    }

    .section-title {
      color: var(--ugpsc-gold);
      font-size: 2.2rem;
      margin-bottom: 0.3rem;
    }

    .section-subtitle {
      color: rgba(246, 241, 232, 0.75);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-size: 0.95rem;
    }

    .timeline-item {
      border-left: 3px solid rgba(204, 176, 110, 0.5);
      padding-left: 0.9rem;
      margin-bottom: 1rem;
    }

    .timeline-item h3 {
      font-size: 1.5rem;
      margin-bottom: 0.25rem;
      color: #fff;
    }

    .timeline-item p {
      margin-bottom: 0;
      color: rgba(246, 241, 232, 0.82);
    }

    .stat-card {
      background: linear-gradient(145deg, rgba(31, 74, 55, 0.55), rgba(15, 17, 17, 0.9));
      border: 1px solid rgba(204, 176, 110, 0.2);
      border-radius: 0.75rem;
      padding: 1rem;
      text-align: center;
      height: 100%;
    }

    .stat-value {
      font-family: "Bebas Neue", sans-serif;
      font-size: 2.4rem;
      color: var(--ugpsc-gold);
      line-height: 1;
    }

    .stat-label {
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: rgba(246, 241, 232, 0.9);
    }

    .cta-band {
      background: linear-gradient(90deg, rgba(191, 59, 43, 0.95), rgba(31, 74, 55, 0.95));
      border: 1px solid rgba(246, 241, 232, 0.25);
      border-radius: 1rem;
      padding: 1.5rem;
    }

    .footer-note {
      border-top: 1px solid rgba(204, 176, 110, 0.22);
      color: rgba(246, 241, 232, 0.75);
      font-size: 1rem;
    }

    @keyframes riseIn {
      from {
        transform: translateY(18px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    @media (max-width: 991.98px) {
      .hero-slide {
        min-height: 460px;
      }

      .navbar-brand img {
        height: 48px;
      }

      .hero-content {
        padding: 1.4rem;
      }
    }

    @media (max-width: 575.98px) {
      .hero-slide {
        min-height: 400px;
      }

      .hero-content h1 {
        font-size: 2.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="site-topline py-2">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>UGPSC • Motorcycle Road Race Supporters Club</div>
      <div>Built for speed, driven by community</div>
    </div>
  </div>

  <div class="site-nav-wrap sticky-top">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
          <img src="/filestore/images/logos/ugpsc-logo.png" alt="UGPSC logo">
        </a>

        <button class="navbar-toggler text-light border border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#siteMenu" aria-controls="siteMenu" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="siteMenu">
          <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-2">
            <li class="nav-item"><a class="nav-link" href="/static/home.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home1.php">About</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home2.php">History</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home3.php">Events</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home4.php">Gallery</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home6.php">Contact</a></li>
          </ul>
          <a href="/member-login.php" class="btn btn-login">Member Login</a>
        </div>
      </div>
    </nav>
  </div>

  <main>
    <section class="hero">
      <div class="container">
        <div id="homeHero" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="5000">
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#homeHero" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#homeHero" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#homeHero" data-bs-slide-to="2" aria-label="Slide 3"></button>
          </div>

          <div class="carousel-inner">
            <div class="carousel-item active">
              <div class="hero-slide" style="background-image: url('/filestore/images/banners/banner1.jpg');">
                <div class="hero-content">
                  <span class="hero-kicker">Est. Legacy</span>
                  <h1>Backing Road Racing Across Generations</h1>
                  <p>UGPSC is a passionate motorcycle road race supporters club with a long and proud history of helping the sport and its community thrive.</p>
                  <a href="#about" class="btn btn-primary-ugpsc">Discover UGPSC</a>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <div class="hero-slide" style="background-image: url('/filestore/images/banners/banner2.jpg');">
                <div class="hero-content">
                  <span class="hero-kicker">Action Ready</span>
                  <h1>Race Day Atmosphere, Club Day Pride</h1>
                  <p>This rotating banner is ready for your high-impact action photography as soon as your final image set is prepared.</p>
                  <a href="#gallery" class="btn btn-primary-ugpsc">View Gallery Plan</a>
                </div>
              </div>
            </div>

            <div class="carousel-item">
              <div class="hero-slide" style="background-image: url('/filestore/images/banners/banner3.jpg');">
                <div class="hero-content">
                  <span class="hero-kicker">Members First</span>
                  <h1>Join, Support, Keep The Wheels Turning</h1>
                  <p>Built to support future membership journeys, event promotion, archives, sponsor spotlighting, and secure member login flows.</p>
                  <a href="#events" class="btn btn-primary-ugpsc">See Events</a>
                </div>
              </div>
            </div>
          </div>

          <button class="carousel-control-prev" type="button" data-bs-target="#homeHero" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#homeHero" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>
    </section>

    <section id="about" class="py-5 py-lg-6">
      <div class="container py-4">
        <div class="row g-4">
          <div class="col-lg-7">
            <div class="section-panel">
              <p class="section-subtitle mb-1">About The Organisation</p>
              <h2 class="section-title">A Motorcycle Road Race Supporters Club</h2>
              <p class="fs-5 mb-3">UGPSC exists to champion road racing and the people who make it possible. From rider support and volunteer spirit to preserving local racing heritage, the club continues to stand at the heart of the scene.</p>
              <p class="mb-0 text-light-emphasis">This is a static concept page to shape look-and-feel first. It can be broken down later into CMS-driven sections and content fields inside `wccms`.</p>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="row g-3 h-100">
              <div class="col-6">
                <div class="stat-card">
                  <div class="stat-value">60+ yrs</div>
                  <div class="stat-label">Club Heritage</div>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-card">
                  <div class="stat-value">100s</div>
                  <div class="stat-label">Supporters</div>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-card">
                  <div class="stat-value">Annual</div>
                  <div class="stat-label">Race Events</div>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-card">
                  <div class="stat-value">New</div>
                  <div class="stat-label">Member Login</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="history" class="pb-5">
      <div class="container">
        <div class="section-panel">
          <p class="section-subtitle mb-1">Club History</p>
          <h2 class="section-title">Built On Decades Of Support</h2>
          <div class="row g-4 mt-1">
            <div class="col-md-4">
              <div class="timeline-item">
                <h3>Early Years</h3>
                <p>Formed by dedicated fans committed to promoting and supporting motorcycle road racing in the region.</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="timeline-item">
                <h3>Community Era</h3>
                <p>Expanded membership, regular club events, and stronger links with race meetings and local motorsport circles.</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="timeline-item mb-0">
                <h3>Next Chapter</h3>
                <p>Digital-first communication, richer media storytelling, and an improved online experience for members and visitors.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="events" class="pb-5">
      <div class="container">
        <div class="cta-band d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h2 class="mb-1">Ready For The 2026 Season Build-Out</h2>
            <p class="mb-0 fs-5">Next step: plug in your final race action photography and live event content feeds from CMS.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <a href="#" class="btn btn-light text-dark text-uppercase">Upcoming Fixtures</a>
            <a href="#" class="btn btn-outline-light text-uppercase">Become A Member</a>
          </div>
        </div>
      </div>
    </section>

    <section id="gallery" class="pb-5">
      <div class="container">
        <div class="section-panel">
          <p class="section-subtitle mb-1">Media Direction</p>
          <h2 class="section-title">Banner + Action Gallery Placeholders</h2>
          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <img class="img-fluid rounded" src="/filestore/images/content/master/istockphoto-1249163124-1024x1024-letterbox.jpg" alt="Placeholder banner image 1">
            </div>
            <div class="col-md-4">
              <img class="img-fluid rounded" src="/filestore/images/content/master/prsentor-4a-1200x800.jpg" alt="Placeholder banner image 2">
            </div>
            <div class="col-md-4">
              <img class="img-fluid rounded" src="/filestore/images/content/master/3-1-computer-and-server-sales.jpg" alt="Placeholder banner image 3">
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer id="contact" class="footer-note py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
      <div>UGPSC Static Home Concept</div>
      <div>Prepared for migration into `wccms` templates and dynamic content fields.</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
