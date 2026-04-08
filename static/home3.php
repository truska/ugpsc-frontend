<?php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UGPSC | Static Home Concept 3</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <style>
    :root {
      --club-green: #215940;
      --club-green-dark: #163c2b;
      --club-red: #c43a32;
      --club-red-deep: #9f2d27;
      --club-paper: #f7f4ec;
      --club-cream: #fffdf8;
      --club-sage: #dfe9df;
      --club-ink: #1f2b25;
      --club-muted: #63726a;
      --club-line: #d8ddd4;
    }

    body {
      font-family: "Manrope", sans-serif;
      color: var(--club-ink);
      background:
        radial-gradient(circle at top left, rgba(196, 58, 50, 0.1), transparent 32%),
        radial-gradient(circle at 90% 12%, rgba(33, 89, 64, 0.13), transparent 28%),
        linear-gradient(180deg, #f9f8f2 0%, #f4f1e8 100%);
      min-height: 100vh;
    }

    h1, h2, h3, .navbar-brand, .eyebrow {
      font-family: "Fraunces", serif;
      letter-spacing: 0.01em;
    }

    .alert-band {
      background: linear-gradient(90deg, var(--club-red), #d55b3f);
      color: #fff;
      font-size: 0.88rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .site-nav-wrap {
      background: rgba(255, 253, 248, 0.94);
      border-bottom: 1px solid rgba(31, 43, 37, 0.08);
      backdrop-filter: blur(8px);
    }

    .navbar-brand img {
      height: 74px;
      width: auto;
    }

    .navbar-toggler {
      border-color: rgba(31, 43, 37, 0.16);
    }

    .navbar-toggler:focus {
      box-shadow: 0 0 0 0.2rem rgba(33, 89, 64, 0.15);
    }

    .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2831,43,37,0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    .nav-link {
      color: var(--club-ink) !important;
      font-weight: 700;
      font-size: 0.9rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .nav-link:hover,
    .nav-link:focus {
      color: var(--club-green) !important;
    }

    .btn-member-login,
    .btn-member-join,
    .btn-hero-primary,
    .btn-hero-secondary {
      border-radius: 999px;
      font-size: 0.92rem;
      font-weight: 800;
      letter-spacing: 0.06em;
      padding: 0.72rem 1.2rem;
      text-transform: uppercase;
    }

    .btn-member-login,
    .btn-hero-primary {
      background: var(--club-green);
      color: #fff;
      border: none;
    }

    .btn-member-login:hover,
    .btn-hero-primary:hover {
      background: var(--club-green-dark);
      color: #fff;
    }

    .btn-member-join,
    .btn-hero-secondary {
      background: transparent;
      color: var(--club-red);
      border: 1px solid rgba(196, 58, 50, 0.35);
    }

    .btn-member-join:hover,
    .btn-hero-secondary:hover {
      background: rgba(196, 58, 50, 0.08);
      color: var(--club-red-deep);
      border-color: rgba(196, 58, 50, 0.45);
    }

    .hero-banner {
      position: relative;
      overflow: hidden;
      padding: 0;
      background:
        linear-gradient(115deg, rgba(22, 60, 43, 0.9) 0%, rgba(22, 60, 43, 0.78) 38%, rgba(196, 58, 50, 0.78) 100%),
        url('/filestore/images/banners/banner3.jpg');
      background-size: cover;
      background-position: center;
    }

    .hero-banner::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        linear-gradient(0deg, rgba(247, 244, 236, 0.16), rgba(247, 244, 236, 0)),
        repeating-linear-gradient(100deg, rgba(255, 255, 255, 0.035) 0 18px, transparent 18px 36px);
    }

    .hero-inner {
      position: relative;
      z-index: 1;
      min-height: 560px;
      display: flex;
      align-items: center;
      padding: 4.5rem 0;
    }

    .hero-copy {
      color: #fff;
      max-width: 720px;
    }

    .eyebrow {
      display: inline-block;
      margin-bottom: 0.95rem;
      padding: 0.35rem 0.8rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.14);
      border: 1px solid rgba(255, 255, 255, 0.28);
      color: #fff;
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .hero-copy h1 {
      font-size: clamp(2.8rem, 6vw, 5.6rem);
      line-height: 0.94;
      margin-bottom: 0.9rem;
    }

    .hero-copy p {
      font-size: clamp(1.05rem, 2vw, 1.28rem);
      max-width: 48ch;
      margin-bottom: 1.4rem;
      color: rgba(255, 255, 255, 0.94);
    }

    .hero-score {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 1.25rem;
      padding: 1.2rem;
      margin-top: 1.6rem;
      max-width: 540px;
    }

    .hero-score-item {
      padding-right: 1rem;
      border-right: 1px solid rgba(255, 255, 255, 0.18);
    }

    .hero-score-item:last-child {
      padding-right: 0;
      border-right: none;
    }

    .hero-score-number {
      display: block;
      font-size: 2rem;
      font-weight: 800;
      line-height: 1;
    }

    .hero-score-label {
      display: block;
      font-size: 0.8rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.82);
      margin-top: 0.3rem;
    }

    .main-shell {
      margin-top: -2.25rem;
      position: relative;
      z-index: 2;
    }

    .intro-panel,
    .facts-panel,
    .news-panel,
    .gallery-panel {
      background: rgba(255, 253, 248, 0.92);
      border: 1px solid rgba(31, 43, 37, 0.08);
      border-radius: 1.35rem;
      box-shadow: 0 18px 42px rgba(49, 58, 52, 0.08);
    }

    .intro-panel {
      padding: 1.6rem;
    }

    .intro-panel h2,
    .facts-panel h2,
    .news-panel h2,
    .gallery-panel h2 {
      font-size: clamp(1.8rem, 3vw, 3rem);
      margin-bottom: 0.45rem;
    }

    .section-copy {
      color: var(--club-muted);
      margin-bottom: 0;
    }

    .about-flag {
      display: inline-block;
      padding: 0.3rem 0.7rem;
      background: rgba(33, 89, 64, 0.09);
      color: var(--club-green);
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      margin-bottom: 0.9rem;
    }

    .fact-card {
      height: 100%;
      padding: 1.25rem;
      border-radius: 1.1rem;
      background: linear-gradient(180deg, var(--club-cream) 0%, #eef3eb 100%);
      border: 1px solid var(--club-line);
    }

    .fact-card--accent {
      background: linear-gradient(160deg, rgba(196, 58, 50, 0.12), rgba(255, 255, 255, 0.9));
    }

    .fact-card--green {
      background: linear-gradient(160deg, rgba(33, 89, 64, 0.13), rgba(255, 255, 255, 0.92));
    }

    .fact-value {
      display: block;
      font-size: clamp(2rem, 4vw, 3.2rem);
      font-weight: 800;
      line-height: 1;
      color: var(--club-ink);
    }

    .fact-label {
      display: block;
      margin-top: 0.45rem;
      color: var(--club-green);
      font-size: 0.82rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .fact-text {
      margin: 0.65rem 0 0;
      color: var(--club-muted);
      font-size: 0.94rem;
    }

    .facts-panel,
    .news-panel,
    .gallery-panel {
      padding: 1.6rem;
    }

    .news-panel {
      background:
        linear-gradient(140deg, rgba(255, 255, 255, 0.95), rgba(247, 244, 236, 0.98)),
        #fff;
    }

    .news-card {
      height: 100%;
      border: 1px solid rgba(31, 43, 37, 0.08);
      border-radius: 1.15rem;
      overflow: hidden;
      box-shadow: none;
    }

    .news-card .card-body {
      padding: 1.35rem;
    }

    .news-card .news-meta {
      display: inline-block;
      margin-bottom: 0.7rem;
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--club-red);
    }

    .news-card h3 {
      font-size: 1.45rem;
      margin-bottom: 0.55rem;
    }

    .news-card p {
      color: var(--club-muted);
      margin-bottom: 1rem;
    }

    .news-link {
      color: var(--club-green);
      font-weight: 800;
      text-decoration: none;
    }

    .news-link:hover {
      color: var(--club-green-dark);
      text-decoration: underline;
    }

    .gallery-grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: 1.35fr 1fr 1fr;
      grid-auto-rows: minmax(170px, auto);
    }

    .gallery-grid img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 1rem;
      border: 1px solid rgba(31, 43, 37, 0.08);
    }

    .gallery-grid .gallery-tall {
      grid-row: span 2;
      min-height: 360px;
    }

    .footer-note {
      margin-top: 2.4rem;
      border-top: 1px solid rgba(31, 43, 37, 0.08);
      color: var(--club-muted);
    }

    @media (max-width: 991.98px) {
      .navbar-brand img {
        height: 62px;
      }

      .hero-inner {
        min-height: 500px;
        padding: 3.75rem 0;
      }

      .main-shell {
        margin-top: -1.5rem;
      }

      .gallery-grid {
        grid-template-columns: 1fr 1fr;
      }

      .gallery-grid .gallery-tall {
        grid-row: span 1;
        min-height: 240px;
      }
    }

    @media (max-width: 767.98px) {
      .hero-copy h1 {
        font-size: 2.7rem;
      }

      .hero-score-item {
        border-right: none;
        padding-right: 0;
      }

      .gallery-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="alert-band py-2">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>UGPSC Motorcycle Road Race Supporters Club</div>
      <div>Alternative homepage concept 03</div>
    </div>
  </div>

  <div class="site-nav-wrap sticky-top">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/static/home3.php">
          <img src="/filestore/images/logos/ugpsc-logo.png" alt="UGPSC logo">
        </a>
        <button class="navbar-toggler border border-secondary-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#siteMenu" aria-controls="siteMenu" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="siteMenu">
          <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-2">
            <li class="nav-item"><a class="nav-link" href="/static/home.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home1.php">About</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home2.php">News</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home3.php">Events</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home4.php">Gallery</a></li>
            <li class="nav-item"><a class="nav-link" href="/static/home6.php">Contact</a></li>
          </ul>
          <div class="d-flex flex-wrap gap-2">
            <a href="/member-login.php" class="btn btn-member-login">Member Login</a>
            <a href="#" class="btn btn-member-join">Join</a>
          </div>
        </div>
      </div>
    </nav>
  </div>

  <main>
    <section class="hero-banner">
      <div class="container-fluid px-0">
        <div class="container hero-inner">
          <div class="hero-copy">
            <span class="eyebrow">Fresh Route For Presentation</span>
            <h1>A brighter, sharper homepage with a proper race-weekend pulse.</h1>
            <p>This version shifts away from the previous concepts with a lighter editorial feel, a full-width banner, stronger green and red accents, and cleaner content blocks for quick client review.</p>
            <div class="d-flex flex-wrap gap-2">
              <a href="/member-login.php" class="btn btn-hero-primary">Member Login</a>
              <a href="#" class="btn btn-hero-secondary">Join UGPSC</a>
            </div>
            <div class="hero-score">
              <div class="row g-3">
                <div class="col-md-4">
                  <div class="hero-score-item">
                    <span class="hero-score-number">60+</span>
                    <span class="hero-score-label">Years Supporting Racing</span>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="hero-score-item">
                    <span class="hero-score-number">12</span>
                    <span class="hero-score-label">Planned Club Dates</span>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="hero-score-item">
                    <span class="hero-score-number">2x</span>
                    <span class="hero-score-label">Monthly Member Updates</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="container main-shell">
      <section id="about" class="pb-4">
        <div class="intro-panel">
          <div class="row g-4 align-items-center">
            <div class="col-lg-7">
              <span class="about-flag">About UGPSC</span>
              <h2>Compact intro, clear story, easy next steps.</h2>
              <p class="section-copy">UGPSC is a long-running motorcycle road race supporters club built around community, race-day backing, and preserving the club’s heritage. This sample keeps the content deliberately concise so the design does the selling while leaving space for CMS-driven content later.</p>
            </div>
            <div class="col-lg-5">
              <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="/member-login.php" class="btn btn-member-login">Existing Members</a>
                <a href="#" class="btn btn-member-join">Become A Member</a>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="pb-4">
        <div class="facts-panel">
          <div class="row g-4 align-items-end">
            <div class="col-lg-4">
              <h2>Facts & Figures</h2>
              <p class="section-copy">A simple stats area that can become editable cards, sponsor counters, member totals, or season milestones.</p>
            </div>
            <div class="col-lg-8">
              <div class="row g-3">
                <div class="col-sm-4">
                  <div class="fact-card fact-card--green">
                    <span class="fact-value">300+</span>
                    <span class="fact-label">Supporters Reached</span>
                    <p class="fact-text">Potential space for active supporters, members, or mailing list figures.</p>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="fact-card fact-card--accent">
                    <span class="fact-value">24</span>
                    <span class="fact-label">Volunteers</span>
                    <p class="fact-text">Useful for demonstrating club activity and race-day support capacity.</p>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="fact-card">
                    <span class="fact-value">£8k</span>
                    <span class="fact-label">Fundraising Target</span>
                    <p class="fact-text">A clear placeholder for annual fundraising goals, progress, or campaign totals.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="pb-4">
        <div class="news-panel">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
            <div>
              <h2>Latest News</h2>
              <p class="section-copy">Two recent-style items shown as Bootstrap cards, ready to become dynamic news posts later.</p>
            </div>
            <a href="#" class="news-link">See all updates</a>
          </div>
          <div class="row g-3">
            <div class="col-lg-6">
              <div class="card news-card">
                <div class="card-body">
                  <span class="news-meta">Club Update</span>
                  <h3>Season launch evening confirmed for members and guests</h3>
                  <p>Tickets, rider guests, and club notices can be surfaced here in a short summary format that gives visitors enough context before they click through.</p>
                  <a href="#" class="news-link">Read the full story</a>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card news-card">
                <div class="card-body">
                  <span class="news-meta">Fundraiser</span>
                  <h3>Volunteer drive opens to support upcoming race weekends</h3>
                  <p>This second card provides a compact teaser for news, appeals, or event preparation updates without making the homepage feel crowded.</p>
                  <a href="#" class="news-link">View update details</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="gallery" class="pb-2">
        <div class="gallery-panel">
          <div class="row g-4 align-items-end mb-3">
            <div class="col-lg-7">
              <h2>Gallery</h2>
              <p class="section-copy">A stronger bottom-of-page image section with a more custom arrangement than the earlier demos.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
              <a href="#" class="news-link">Open full gallery</a>
            </div>
          </div>
          <div class="gallery-grid">
            <img class="gallery-tall" src="/filestore/images/banners/banner1.jpg" alt="Rider lined up before a road race">
            <img src="/filestore/images/banners/banner2.jpg" alt="Race action at speed">
            <img src="/filestore/images/banners/banner3.jpg" alt="Supporters and paddock atmosphere">
            <img src="/filestore/images/content/master/istockphoto-1249163124-1024x1024-letterbox.jpg" alt="Motorcycle detail placeholder">
            <img src="/filestore/images/content/master/prsentor-4a-1200x800.jpg" alt="Event presentation placeholder">
          </div>
        </div>
      </section>
    </div>
  </main>

  <footer id="contact" class="footer-note py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
      <div>UGPSC Static Home Concept 03</div>
      <div>Light-theme concept with full-width banner, demo links, news cards, and member CTAs.</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
