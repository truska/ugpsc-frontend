<?php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UGPSC | Static Home Concept 4</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <style>
    :root {
      --club-green: #1f5a3f;
      --club-green-deep: #163c2b;
      --club-red: #c43a32;
      --club-red-soft: #db6a55;
      --club-paper: #f7f4ed;
      --club-white: #fffdf8;
      --club-mist: #e8efe6;
      --club-ink: #1f2c25;
      --club-muted: #67756e;
      --club-line: #d9ddd3;
    }

    body {
      font-family: "Manrope", sans-serif;
      color: var(--club-ink);
      background:
        radial-gradient(circle at 10% 0%, rgba(196, 58, 50, 0.08), transparent 28%),
        radial-gradient(circle at 90% 10%, rgba(31, 90, 63, 0.1), transparent 26%),
        linear-gradient(180deg, #faf8f2 0%, #f4f1e8 100%);
      min-height: 100vh;
    }

    h1, h2, h3, .navbar-brand, .eyebrow {
      font-family: "Fraunces", serif;
      letter-spacing: 0.01em;
    }

    .alert-band {
      background: linear-gradient(90deg, var(--club-red), var(--club-red-soft));
      color: #fff;
      font-size: 0.88rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .site-nav-wrap {
      background: rgba(255, 253, 248, 0.94);
      border-bottom: 1px solid rgba(31, 44, 37, 0.08);
      backdrop-filter: blur(8px);
    }

    .navbar-brand img {
      height: 74px;
      width: auto;
    }

    .navbar-toggler {
      border-color: rgba(31, 44, 37, 0.16);
    }

    .navbar-toggler:focus {
      box-shadow: 0 0 0 0.2rem rgba(31, 90, 63, 0.16);
    }

    .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2831,44,37,0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
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
      background: var(--club-green-deep);
      color: #fff;
    }

    .btn-member-join,
    .btn-hero-secondary {
      background: transparent;
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.38);
    }

    .btn-member-join:hover,
    .btn-hero-secondary:hover {
      background: rgba(255, 255, 255, 0.08);
      color: #fff;
      border-color: rgba(255, 255, 255, 0.5);
    }

    .hero-carousel-shell {
      position: relative;
      overflow: hidden;
      background: #173127;
    }

    .hero-slide {
      min-height: clamp(430px, 62vh, 520px);
      max-height: 520px;
      background-size: cover;
      background-position: center;
      position: relative;
      display: flex;
      align-items: center;
    }

    .hero-slide::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        linear-gradient(110deg, rgba(19, 47, 35, 0.88) 0%, rgba(19, 47, 35, 0.72) 38%, rgba(196, 58, 50, 0.55) 100%),
        repeating-linear-gradient(115deg, rgba(255, 255, 255, 0.03) 0 20px, transparent 20px 40px);
    }

    .hero-slide .container {
      position: relative;
      z-index: 2;
    }

    .hero-copy {
      color: #fff;
      max-width: 690px;
      padding: 3rem 0 3.2rem;
    }

    .eyebrow {
      display: inline-block;
      margin-bottom: 0.9rem;
      padding: 0.35rem 0.8rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.12);
      border: 1px solid rgba(255, 255, 255, 0.24);
      color: #fff;
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .hero-copy h1 {
      font-size: clamp(2.45rem, 5vw, 4.8rem);
      line-height: 0.95;
      margin-bottom: 0.8rem;
    }

    .hero-copy p {
      font-size: clamp(1rem, 1.9vw, 1.2rem);
      max-width: 46ch;
      margin-bottom: 1.25rem;
      color: rgba(255, 255, 255, 0.93);
    }

    .hero-score {
      margin-top: 1.35rem;
      padding: 1.05rem 1.2rem;
      max-width: 560px;
      border-radius: 1.1rem;
      background: rgba(255, 255, 255, 0.09);
      border: 1px solid rgba(255, 255, 255, 0.16);
    }

    .hero-score-number {
      display: block;
      font-size: 1.8rem;
      font-weight: 800;
      line-height: 1;
    }

    .hero-score-label {
      display: block;
      margin-top: 0.25rem;
      font-size: 0.78rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.78);
    }

    .carousel-indicators {
      margin-bottom: 1rem;
    }

    .carousel-indicators [data-bs-target] {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin: 0 0.35rem;
      border: none;
    }

    .carousel-caption-note {
      position: absolute;
      right: 1rem;
      bottom: 1rem;
      z-index: 2;
      max-width: 280px;
      padding: 0.8rem 0.95rem;
      border-radius: 1rem;
      background: rgba(255, 253, 248, 0.92);
      color: var(--club-ink);
      font-size: 0.9rem;
      box-shadow: 0 12px 26px rgba(18, 25, 21, 0.14);
    }

    .main-shell {
      margin-top: -1.6rem;
      position: relative;
      z-index: 2;
    }

    .intro-panel,
    .facts-panel,
    .news-panel,
    .gallery-panel {
      padding: 1.6rem;
      background: rgba(255, 253, 248, 0.94);
      border: 1px solid rgba(31, 44, 37, 0.08);
      border-radius: 1.35rem;
      box-shadow: 0 18px 40px rgba(47, 55, 49, 0.08);
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
      margin-bottom: 0.9rem;
      border-radius: 999px;
      background: rgba(31, 90, 63, 0.08);
      color: var(--club-green);
      font-size: 0.8rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .fact-card {
      height: 100%;
      padding: 1.2rem;
      border-radius: 1.05rem;
      background: linear-gradient(180deg, var(--club-white) 0%, #eef3eb 100%);
      border: 1px solid var(--club-line);
    }

    .fact-card--green {
      background: linear-gradient(160deg, rgba(31, 90, 63, 0.12), rgba(255, 255, 255, 0.92));
    }

    .fact-card--accent {
      background: linear-gradient(160deg, rgba(196, 58, 50, 0.11), rgba(255, 255, 255, 0.92));
    }

    .fact-value {
      display: block;
      font-size: clamp(2rem, 4vw, 3rem);
      font-weight: 800;
      line-height: 1;
    }

    .fact-label {
      display: block;
      margin-top: 0.45rem;
      color: var(--club-green);
      font-size: 0.8rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .fact-text,
    .news-card p {
      margin: 0.65rem 0 0;
      color: var(--club-muted);
      font-size: 0.94rem;
    }

    .news-panel {
      background:
        linear-gradient(140deg, rgba(255, 255, 255, 0.96), rgba(247, 244, 237, 0.98)),
        #fff;
    }

    .news-card {
      height: 100%;
      border: 1px solid rgba(31, 44, 37, 0.08);
      border-radius: 1.1rem;
      overflow: hidden;
    }

    .news-card .card-body {
      padding: 1.3rem;
    }

    .news-meta {
      display: inline-block;
      margin-bottom: 0.65rem;
      color: var(--club-red);
      font-size: 0.78rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .news-card h3 {
      font-size: 1.4rem;
      margin-bottom: 0.45rem;
    }

    .news-link {
      color: var(--club-green);
      font-weight: 800;
      text-decoration: none;
    }

    .news-link:hover {
      color: var(--club-green-deep);
      text-decoration: underline;
    }

    .gallery-grid {
      display: grid;
      gap: 1rem;
      grid-template-columns: 1.15fr 1fr 1fr;
      grid-auto-rows: minmax(165px, auto);
    }

    .gallery-grid img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 1rem;
      border: 1px solid rgba(31, 44, 37, 0.08);
    }

    .gallery-grid .gallery-tall {
      grid-row: span 2;
      min-height: 340px;
    }

    .footer-note {
      margin-top: 2.4rem;
      border-top: 1px solid rgba(31, 44, 37, 0.08);
      color: var(--club-muted);
    }

    @media (max-width: 991.98px) {
      .navbar-brand img {
        height: 62px;
      }

      .hero-slide {
        min-height: clamp(400px, 58vh, 470px);
        max-height: 470px;
      }

      .hero-copy {
        padding: 2.5rem 0 2.8rem;
      }

      .carousel-caption-note {
        display: none;
      }

      .gallery-grid {
        grid-template-columns: 1fr 1fr;
      }

      .gallery-grid .gallery-tall {
        grid-row: span 1;
        min-height: 220px;
      }
    }

    @media (max-width: 767.98px) {
      .hero-copy h1 {
        font-size: 2.35rem;
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
      <div>Alternative homepage concept 04</div>
    </div>
  </div>

  <div class="site-nav-wrap sticky-top">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/static/home4.php">
          <img src="/filestore/images/logos/ugpsc-logo.png" alt="UGPSC logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteMenu" aria-controls="siteMenu" aria-expanded="false" aria-label="Toggle navigation">
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
    <section class="hero-carousel-shell">
      <div id="home4Hero" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4300">
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#home4Hero" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
          <button type="button" data-bs-target="#home4Hero" data-bs-slide-to="1" aria-label="Slide 2"></button>
          <button type="button" data-bs-target="#home4Hero" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>

        <div class="carousel-inner">
          <div class="carousel-item active">
            <div class="hero-slide" style="background-image: url('/filestore/images/banners/banner1.jpg');">
              <div class="container">
                <div class="hero-copy">
                  <span class="eyebrow">Above-The-Fold Headline</span>
                  <h1>A tighter hero that keeps the opening message visible straight away.</h1>
                  <p>This version keeps the full-width banner, but deliberately caps its height so the first headline and call-to-action remain comfortably above the fold on a standard desktop browser.</p>
                  <div class="d-flex flex-wrap gap-2">
                    <a href="/member-login.php" class="btn btn-hero-primary">Member Login</a>
                    <a href="#" class="btn btn-hero-secondary">Join UGPSC</a>
                  </div>
                  <div class="hero-score">
                    <div class="row g-3">
                      <div class="col-md-4">
                        <span class="hero-score-number">04</span>
                        <span class="hero-score-label">Concept Number</span>
                      </div>
                      <div class="col-md-4">
                        <span class="hero-score-number">3</span>
                        <span class="hero-score-label">Hero Slides</span>
                      </div>
                      <div class="col-md-4">
                        <span class="hero-score-number">Fade</span>
                        <span class="hero-score-label">Transition Style</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="carousel-caption-note">Design note: this hero uses Bootstrap's fade transition rather than a horizontal slide.</div>
            </div>
          </div>

          <div class="carousel-item">
            <div class="hero-slide" style="background-image: url('/filestore/images/banners/banner2.jpg');">
              <div class="container">
                <div class="hero-copy">
                  <span class="eyebrow">Cleaner Presentation</span>
                  <h1>Same family as home 3, but trimmed down for a faster first impression.</h1>
                  <p>The shorter banner makes room for more content higher up the page and creates a more immediate, practical homepage feel for members and first-time visitors.</p>
                  <div class="d-flex flex-wrap gap-2">
                    <a href="#about" class="btn btn-hero-primary">See The Layout</a>
                    <a href="#news" class="btn btn-hero-secondary">Jump To News</a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="carousel-item">
            <div class="hero-slide" style="background-image: url('/filestore/images/banners/banner3.jpg');">
              <div class="container">
                <div class="hero-copy">
                  <span class="eyebrow">Client Comparison Ready</span>
                  <h1>Another option that keeps the content blocks familiar while changing the pacing.</h1>
                  <p>This gives the client a close cousin to home 3 rather than a full reset, which should help when they start mixing and matching sections from different concepts.</p>
                  <div class="d-flex flex-wrap gap-2">
                    <a href="#gallery" class="btn btn-hero-primary">Open Gallery</a>
                    <a href="/static/home3.php" class="btn btn-hero-secondary">Compare Home 3</a>
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
              <h2>Home 4 keeps the same content structure with a shorter opening act.</h2>
              <p class="section-copy">This variant is intentionally close to home 3 so the comparison is useful: same general sections, same member-first tone, but the hero is more restrained and the page settles into content sooner.</p>
            </div>
            <div class="col-lg-5">
              <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <a href="/member-login.php" class="btn btn-member-login">Existing Members</a>
                <a href="#" class="btn btn-member-login">Member Area</a>
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
              <p class="section-copy">The compact structure still works well for quick stats, event counts, funding goals, or member metrics.</p>
            </div>
            <div class="col-lg-8">
              <div class="row g-3">
                <div class="col-sm-4">
                  <div class="fact-card fact-card--green">
                    <span class="fact-value">18</span>
                    <span class="fact-label">Club Meetups</span>
                    <p class="fact-text">Useful if you want the homepage to feel more active and event-focused.</p>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="fact-card fact-card--accent">
                    <span class="fact-value">420</span>
                    <span class="fact-label">Photo Archive</span>
                    <p class="fact-text">A placeholder stat that can promote history, archives, or featured galleries.</p>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="fact-card">
                    <span class="fact-value">£12k</span>
                    <span class="fact-label">Support Goal</span>
                    <p class="fact-text">Can be used for rider backing, fundraisers, or annual target messaging.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="news" class="pb-4">
        <div class="news-panel">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
            <div>
              <h2>Latest News</h2>
              <p class="section-copy">Still using two Bootstrap cards, but with slightly more concise teaser copy to fit the quicker page rhythm.</p>
            </div>
            <a href="#" class="news-link">See all updates</a>
          </div>
          <div class="row g-3">
            <div class="col-lg-6">
              <div class="card news-card">
                <div class="card-body">
                  <span class="news-meta">Event Note</span>
                  <h3>Spring briefing night moves into the new members lounge</h3>
                  <p>A practical announcement slot for event changes, guest updates, timings, or venue notes before visitors click through.</p>
                  <a href="#" class="news-link">Read the full story</a>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card news-card">
                <div class="card-body">
                  <span class="news-meta">Membership</span>
                  <h3>Renewal push opens with priority access for existing members</h3>
                  <p>This card can support renewals, online forms, club notices, and short promotional summaries without crowding the layout.</p>
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
              <p class="section-copy">The lower gallery remains prominent, giving the client another image-led option to compare against the other demos.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
              <a href="#" class="news-link">Open full gallery</a>
            </div>
          </div>
          <div class="gallery-grid">
            <img class="gallery-tall" src="/filestore/images/banners/banner2.jpg" alt="Race action at speed">
            <img src="/filestore/images/banners/banner3.jpg" alt="Supporters and paddock atmosphere">
            <img src="/filestore/images/banners/banner1.jpg" alt="Rider lined up before a road race">
            <img src="/filestore/images/content/master/prsentor-4a-1200x800.jpg" alt="Event presentation placeholder">
            <img src="/filestore/images/content/master/istockphoto-1249163124-1024x1024-letterbox.jpg" alt="Motorcycle detail placeholder">
          </div>
        </div>
      </section>
    </div>
  </main>

  <footer id="contact" class="footer-note py-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
      <div>UGPSC Static Home Concept 04</div>
      <div>Shorter fade-transition hero variant with cross-links through to the upcoming `home5`.</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
