<?php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UGPSC | Static Home Concept 1</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <style>
    :root {
      --ug-green: #1f5a3f;
      --ug-red: #c73f2e;
      --ug-ink: #121619;
      --ug-mid: #4f5d67;
      --ug-line: #d7dee3;
      --ug-soft: #f4f7f9;
    }

    body {
      font-family: "Public Sans", sans-serif;
      background: #fff;
      color: var(--ug-ink);
      min-height: 100vh;
    }

    h1, h2, h3, .display-font {
      font-family: "Archivo Black", sans-serif;
      letter-spacing: 0.01em;
      text-transform: uppercase;
    }

    .top-strip {
      background: #fff;
      border-bottom: 1px solid var(--ug-line);
      color: var(--ug-mid);
      font-size: 0.88rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .site-nav-wrap {
      background: #fff;
      border-bottom: 1px solid var(--ug-line);
    }

    .navbar-brand img {
      height: 92px;
      width: auto;
    }

    .nav-link {
      color: var(--ug-ink) !important;
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.06em;
      font-size: 0.9rem;
    }

    .nav-link:hover,
    .nav-link:focus {
      color: var(--ug-green) !important;
    }

    .btn-member {
      background: var(--ug-red);
      color: #fff;
      border: none;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-size: 0.88rem;
      padding: 0.55rem 1rem;
    }

    .btn-member:hover {
      background: #ad3324;
      color: #fff;
    }

    .hero {
      padding: 2rem 0 1.6rem;
    }

    .hero-shell {
      border: 1px solid var(--ug-line);
      border-radius: 1rem;
      overflow: hidden;
      background: #fff;
    }

    .hero-left {
      padding: 2rem;
      background:
        linear-gradient(135deg, rgba(31, 90, 63, 0.08) 0%, transparent 58%),
        linear-gradient(320deg, rgba(199, 63, 46, 0.08) 0%, transparent 52%),
        #fff;
    }

    .hero-kicker {
      display: inline-block;
      background: var(--ug-green);
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-size: 0.78rem;
      padding: 0.28rem 0.65rem;
      border-radius: 999px;
      margin-bottom: 1rem;
    }

    .hero-left h1 {
      font-size: clamp(2.2rem, 5vw, 4.5rem);
      line-height: 0.96;
      margin-bottom: 0.8rem;
    }

    .hero-left p {
      color: #2b353c;
      font-size: clamp(1rem, 1.9vw, 1.2rem);
      max-width: 52ch;
      margin-bottom: 1.3rem;
    }

    .btn-primary-ug {
      background: var(--ug-red);
      color: #fff;
      border: none;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 0.72rem 1.15rem;
      font-weight: 600;
      margin-right: 0.5rem;
    }

    .btn-outline-ug {
      background: #fff;
      color: var(--ug-ink);
      border: 1px solid #98a8b3;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 0.72rem 1.15rem;
      font-weight: 600;
    }

    .btn-primary-ug:hover {
      background: #a73325;
      color: #fff;
    }

    .btn-outline-ug:hover {
      background: #eff3f6;
      color: var(--ug-ink);
    }

    .hero-right {
      min-height: 420px;
      background-image: url('/filestore/images/banners/banner2.jpg');
      background-size: cover;
      background-position: center;
      position: relative;
    }

    .hero-right::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.04) 0%, rgba(0, 0, 0, 0.32) 100%);
    }

    .hero-badge {
      position: absolute;
      right: 1rem;
      bottom: 1rem;
      background: rgba(255, 255, 255, 0.92);
      color: var(--ug-ink);
      border: 1px solid var(--ug-line);
      border-radius: 0.7rem;
      padding: 0.65rem 0.75rem;
      z-index: 2;
      font-size: 0.9rem;
      max-width: 220px;
    }

    .section-space {
      padding: 2.2rem 0;
    }

    .section-title {
      font-size: clamp(1.7rem, 3.2vw, 2.8rem);
      margin-bottom: 0.25rem;
    }

    .section-intro {
      color: var(--ug-mid);
      margin-bottom: 1.25rem;
      max-width: 75ch;
    }

    .pillar {
      border: 1px solid var(--ug-line);
      border-radius: 0.85rem;
      padding: 1.1rem;
      height: 100%;
      background: #fff;
    }

    .pillar-tag {
      display: inline-block;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--ug-green);
      margin-bottom: 0.45rem;
      font-weight: 700;
    }

    .pillar h3 {
      font-size: 1.25rem;
      margin-bottom: 0.35rem;
    }

    .pillar p {
      color: #334048;
      margin-bottom: 0;
    }

    .timeline {
      border: 1px solid var(--ug-line);
      border-radius: 1rem;
      overflow: hidden;
      background: var(--ug-soft);
    }

    .timeline-step {
      padding: 1.15rem;
      border-right: 1px solid #d3dde3;
      height: 100%;
      background: #fff;
    }

    .timeline-step:last-child {
      border-right: none;
    }

    .timeline-year {
      color: var(--ug-red);
      font-weight: 700;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .timeline-step h3 {
      font-size: 1.05rem;
      margin: 0.3rem 0;
    }

    .timeline-step p {
      margin-bottom: 0;
      color: #3a474f;
      font-size: 0.94rem;
    }

    .event-band {
      border: 1px solid var(--ug-line);
      border-radius: 1rem;
      background:
        linear-gradient(90deg, rgba(31, 90, 63, 0.07), rgba(199, 63, 46, 0.08)),
        #fff;
      padding: 1.4rem;
    }

    .event-band h2 {
      font-size: clamp(1.8rem, 3.6vw, 3rem);
      margin-bottom: 0.45rem;
    }

    .event-band p {
      color: #37454d;
      margin-bottom: 0;
    }

    .photo-tile {
      width: 100%;
      border-radius: 0.75rem;
      border: 1px solid var(--ug-line);
      aspect-ratio: 4 / 3;
      object-fit: cover;
    }

    .footer-note {
      border-top: 1px solid var(--ug-line);
      color: #596771;
      font-size: 0.92rem;
    }

    @media (max-width: 991.98px) {
      .navbar-brand img {
        height: 80px;
      }

      .hero-left {
        padding: 1.4rem;
      }

      .timeline-step {
        border-right: none;
        border-bottom: 1px solid #d3dde3;
      }

      .timeline-step:last-child {
        border-bottom: none;
      }
    }

    @media (max-width: 575.98px) {
      .navbar-brand img {
        height: 68px;
      }

      .hero-right {
        min-height: 300px;
      }
    }
  </style>
</head>
<body>
  <div class="top-strip py-2">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>UGPSC • Motorcycle Road Race Supporters Club</div>
      <div>Alternative Homepage Concept 01</div>
    </div>
  </div>

  <div class="site-nav-wrap sticky-top">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
          <img src="/filestore/images/logos/ugpsc-logo.png" alt="UGPSC logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteMenu" aria-controls="siteMenu" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="siteMenu">
          <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-2">
            <li class="nav-item"><a class="nav-link" href="/static/home.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home1.php">About</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home2.php">Legacy</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home3.php">Events</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home4.php">Gallery</a></li>
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
        <div class="hero-shell">
          <div class="row g-0 align-items-stretch">
            <div class="col-lg-7">
              <div class="hero-left h-100 d-flex flex-column justify-content-center">
                <span class="hero-kicker">Built By Supporters</span>
                <h1>Road Racing Club. White Canvas. Fresh Direction.</h1>
                <p>This third concept intentionally moves to a clean editorial look with strong spacing, bright surfaces, and structured content blocks for straightforward CMS migration.</p>
                <div>
                  <a href="#about" class="btn btn-primary-ug">Discover UGPSC</a>
                  <a href="#events" class="btn btn-outline-ug">View Events</a>
                </div>
              </div>
            </div>
            <div class="col-lg-5">
              <div class="hero-right h-100">
                <div class="hero-badge">
                  <strong>Design note:</strong> intentionally light theme with a pure `#fff` page background.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="about" class="section-space">
      <div class="container">
        <h2 class="section-title">What This Version Emphasizes</h2>
        <p class="section-intro">A calmer visual rhythm that foregrounds content clarity. This approach is suitable if you want a cleaner member-first homepage with less dramatic styling.</p>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="pillar">
              <span class="pillar-tag">01 Community</span>
              <h3>Member-centered design</h3>
              <p>Navigation, calls-to-action, and content blocks are laid out for quick scanning and easier updates in CMS-managed sections.</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="pillar">
              <span class="pillar-tag">02 Club Story</span>
              <h3>Legacy made readable</h3>
              <p>The page leaves space for heritage storytelling without forcing dark overlays or dense visual treatment.</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="pillar">
              <span class="pillar-tag">03 Media-ready</span>
              <h3>Images still carry impact</h3>
              <p>Photo tiles and hero imagery remain prominent while maintaining the white-first visual system across the page.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="legacy" class="section-space pt-1">
      <div class="container">
        <h2 class="section-title">Legacy Timeline</h2>
        <div class="timeline">
          <div class="row g-0">
            <div class="col-lg-4">
              <div class="timeline-step">
                <div class="timeline-year">Early Era</div>
                <h3>Supporters Organize</h3>
                <p>UGPSC forms around shared commitment to motorcycle road racing and active support for the scene.</p>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="timeline-step">
                <div class="timeline-year">Growth Era</div>
                <h3>Events And Membership Expand</h3>
                <p>The club broadens participation and deepens ties with race meetings and local motorsport networks.</p>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="timeline-step">
                <div class="timeline-year">Next Era</div>
                <h3>Digital + Community Together</h3>
                <p>The website becomes a stronger hub for news, member journeys, galleries, and season updates.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="events" class="section-space">
      <div class="container">
        <div class="event-band d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <h2>2026 Season Content Block</h2>
            <p>This area can become a dynamic fixture feed, announcements rail, and quick access to race-day updates.</p>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <a href="#" class="btn btn-dark text-uppercase">Upcoming Fixtures</a>
            <a href="#" class="btn btn-outline-dark text-uppercase">Become A Member</a>
          </div>
        </div>
      </div>
    </section>

    <section id="gallery" class="section-space pt-1">
      <div class="container">
        <div class="row g-3">
          <div class="col-md-4">
            <img class="photo-tile" src="/filestore/images/banners/banner1.jpg" alt="Race action image one">
          </div>
          <div class="col-md-4">
            <img class="photo-tile" src="/filestore/images/banners/banner2.jpg" alt="Race action image two">
          </div>
          <div class="col-md-4">
            <img class="photo-tile" src="/filestore/images/banners/banner3.jpg" alt="Race action image three">
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer id="contact" class="footer-note py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
      <div>UGPSC Static Home Concept 01</div>
      <div>Light-theme alternative with pure white page background (`#fff`).</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
