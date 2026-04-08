<?php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UGPSC | Static Home Concept 6</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <style>
    :root {
      --ug-green: #205740;
      --ug-green-deep: #173c2c;
      --ug-red: #c33d33;
      --ug-red-soft: #e37d68;
      --ug-text: #34393d;
      --ug-muted: #697279;
      --ug-line: #d9dfdb;
      --ug-paper: #f7f4ec;
      --ug-white: #ffffff;
      --ug-panel: #eef2ed;
      --ug-topbar: #f4eee3;
      --ug-footer: #f1ede3;
    }

    body {
      font-family: "Manrope", sans-serif;
      color: var(--ug-text);
      background:
        radial-gradient(circle at 12% 8%, rgba(227, 125, 104, 0.12), transparent 24%),
        radial-gradient(circle at 88% 12%, rgba(32, 87, 64, 0.1), transparent 24%),
        linear-gradient(180deg, #fbf9f4 0%, #f6f2ea 100%);
      min-height: 100vh;
    }

    a {
      color: var(--ug-green);
      text-decoration: none;
    }

    a:hover {
      color: var(--ug-red);
      text-decoration: none;
    }

    h1, h2, h3, .navbar-brand {
      font-family: "Fraunces", serif;
      letter-spacing: 0.01em;
    }

    .topline {
      background: var(--ug-topbar);
      border-bottom: 1px solid rgba(52, 57, 61, 0.08);
      color: var(--ug-muted);
      font-size: 0.88rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .site-nav-wrap {
      background: rgba(255, 255, 255, 0.95);
      border-bottom: 1px solid rgba(52, 57, 61, 0.08);
      backdrop-filter: blur(8px);
    }

    .navbar {
      padding-top: 0.35rem;
      padding-bottom: 0.35rem;
    }

    .navbar-brand {
      position: relative;
      z-index: 3;
      margin-top: -1.1rem;
      margin-bottom: -1.1rem;
    }

    .navbar-brand img {
      height: 108px;
      width: auto;
      filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.12));
    }

    .navbar-toggler {
      border-color: rgba(52, 57, 61, 0.16);
    }

    .navbar-toggler:focus {
      box-shadow: 0 0 0 0.2rem rgba(32, 87, 64, 0.14);
    }

    .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2852,57,61,0.82%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    .nav-link {
      color: var(--ug-text) !important;
      font-weight: 700;
      font-size: 0.88rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    .nav-link:hover,
    .nav-link:focus {
      color: var(--ug-green) !important;
    }

    .btn-member-login,
    .btn-member-join,
    .btn-section-link {
      border-radius: 999px;
      font-size: 0.88rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 0.72rem 1.15rem;
    }

    .btn-member-login {
      background: var(--ug-green);
      color: #fff;
      border: none;
    }

    .btn-member-login:hover {
      background: var(--ug-green-deep);
      color: #fff;
    }

    .btn-member-join {
      background: transparent;
      color: var(--ug-red);
      border: 1px solid rgba(195, 61, 51, 0.34);
    }

    .btn-member-join:hover {
      background: rgba(195, 61, 51, 0.08);
      color: #a7322a;
      border-color: rgba(195, 61, 51, 0.45);
    }

    .btn-section-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(32, 87, 64, 0.08);
      color: var(--ug-green);
      border: 1px solid rgba(32, 87, 64, 0.16);
      text-decoration: none;
    }

    .btn-section-link:hover {
      background: rgba(32, 87, 64, 0.14);
      color: var(--ug-green-deep);
      border-color: rgba(32, 87, 64, 0.24);
    }

    .hero-shell {
      position: relative;
      overflow: hidden;
      background: #1e3028;
      border-bottom: 1px solid rgba(52, 57, 61, 0.08);
    }

    .hero-slide {
      min-height: clamp(300px, 42vh, 420px);
      max-height: 420px;
      background-size: cover;
      background-position: center;
      position: relative;
    }

    .hero-slide::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(10, 18, 13, 0.18), rgba(10, 18, 13, 0.22));
    }

    .carousel-indicators {
      margin-bottom: 1rem;
    }

    .carousel-indicators [data-bs-target] {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      margin: 0 0.3rem;
      border: none;
      background-color: rgba(255, 255, 255, 0.7);
    }

    .page-block {
      padding: 2.4rem 0 0;
    }

    .soft-panel,
    .news-panel,
    .gallery-panel {
      background: rgba(255, 255, 255, 0.92);
      border: 1px solid rgba(52, 57, 61, 0.08);
      border-radius: 1.35rem;
      box-shadow: 0 18px 40px rgba(58, 63, 67, 0.06);
    }

    .soft-panel,
    .news-panel,
    .gallery-panel,
    .footer-main {
      padding: 1.6rem;
    }

    .section-tag {
      display: inline-block;
      margin-bottom: 0.85rem;
      padding: 0.32rem 0.72rem;
      border-radius: 999px;
      background: rgba(32, 87, 64, 0.08);
      color: var(--ug-green);
      font-size: 0.78rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .section-title {
      font-size: clamp(1.9rem, 3vw, 3.2rem);
      margin-bottom: 0.45rem;
    }

    .section-copy {
      color: var(--ug-muted);
      margin-bottom: 0;
    }

    .fact-card {
      height: 100%;
      padding: 1.2rem;
      border-radius: 1.05rem;
      background: linear-gradient(160deg, #4b5258, #2f353a);
      border: 1px solid rgba(47, 53, 58, 0.12);
      box-shadow: 0 16px 30px rgba(58, 63, 67, 0.14);
    }

    .fact-card--accent {
      background: linear-gradient(155deg, var(--ug-red-soft), var(--ug-red));
      border-color: rgba(195, 61, 51, 0.2);
    }

    .fact-card--green {
      background: linear-gradient(155deg, #2c7656, var(--ug-green));
      border-color: rgba(32, 87, 64, 0.2);
    }

    .fact-value {
      display: block;
      font-size: clamp(2rem, 3vw, 2.8rem);
      font-weight: 800;
      line-height: 1;
      color: #fff;
    }

    .fact-label {
      display: block;
      margin-top: 0.45rem;
      color: rgba(255, 255, 255, 0.82);
      font-size: 0.8rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .fact-text {
      margin: 0.6rem 0 0;
      color: rgba(255, 255, 255, 0.84);
      font-size: 0.92rem;
    }

    .news-panel {
      background:
        linear-gradient(160deg, rgba(255, 255, 255, 0.96), rgba(247, 244, 236, 0.98)),
        #fff;
    }

    .news-card {
      height: 100%;
      border: 1px solid rgba(52, 57, 61, 0.08);
      border-radius: 1.1rem;
      overflow: hidden;
    }

    .news-card .card-body {
      padding: 1.35rem;
    }

    .news-meta {
      display: inline-block;
      margin-bottom: 0.65rem;
      color: var(--ug-red);
      font-size: 0.78rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .news-card h3 {
      font-size: 1.4rem;
      margin-bottom: 0.45rem;
      color: var(--ug-text);
    }

    .news-card p {
      color: var(--ug-muted);
      margin-bottom: 1rem;
    }

    .footer-link {
      color: var(--ug-green);
      font-weight: 800;
      text-decoration: none;
    }

    .footer-link:hover {
      color: var(--ug-red);
      text-decoration: none;
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
      border: 1px solid rgba(52, 57, 61, 0.08);
    }

    .gallery-tall {
      grid-row: span 2;
      min-height: 340px;
    }

    .site-footer {
      margin-top: 2.4rem;
      background: var(--ug-footer);
      border-top: 1px solid rgba(52, 57, 61, 0.08);
    }

    .footer-main {
      border-bottom: 1px solid rgba(52, 57, 61, 0.08);
    }

    .footer-title {
      font-size: 1rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--ug-text);
      margin-bottom: 0.8rem;
    }

    .footer-copy,
    .footer-list,
    .footer-meta {
      color: var(--ug-muted);
      font-size: 0.94rem;
    }

    .footer-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .footer-list li + li {
      margin-top: 0.45rem;
    }

    .social-links {
      display: flex;
      flex-wrap: wrap;
      gap: 0.6rem;
      margin-top: 0.8rem;
    }

    .social-chip {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 44px;
      height: 44px;
      padding: 0 0.8rem;
      border-radius: 999px;
      background: rgba(32, 87, 64, 0.08);
      color: var(--ug-green);
      font-weight: 800;
      text-decoration: none;
    }

    .social-chip:hover {
      background: rgba(32, 87, 64, 0.14);
      color: var(--ug-red);
    }

    .footer-legal {
      padding: 1rem 0 1.35rem;
      color: var(--ug-muted);
      font-size: 0.9rem;
    }

    .footer-legal a {
      color: var(--ug-green);
      text-decoration: none;
    }

    .footer-legal a:hover {
      color: var(--ug-red);
      text-decoration: none;
    }

    @media (max-width: 991.98px) {
      .navbar-brand {
        margin-top: -0.4rem;
        margin-bottom: -0.4rem;
      }

      .navbar-brand img {
        height: 84px;
      }

      .gallery-grid {
        grid-template-columns: 1fr 1fr;
      }

      .gallery-tall {
        grid-row: span 1;
        min-height: 240px;
      }
    }

    @media (max-width: 767.98px) {
      .hero-slide {
        min-height: 250px;
      }

      .gallery-grid {
        grid-template-columns: 1fr;
      }

      .footer-legal {
        text-align: left !important;
      }
    }
  </style>
</head>
<body>
  <div class="topline py-2">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>UGPSC Motorcycle Road Race Supporters Club</div>
      <div>Alternative homepage concept 06</div>
    </div>
  </div>

  <div class="site-nav-wrap sticky-top">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/static/home6.php">
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
    <section class="hero-shell">
      <div id="home6Hero" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4300">
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#home6Hero" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
          <button type="button" data-bs-target="#home6Hero" data-bs-slide-to="1" aria-label="Slide 2"></button>
          <button type="button" data-bs-target="#home6Hero" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
          <div class="carousel-item active">
            <div class="hero-slide" style="background-image: url('/filestore/images/banners/banner1.jpg');"></div>
          </div>
          <div class="carousel-item">
            <div class="hero-slide" style="background-image: url('/filestore/images/banners/banner2.jpg');"></div>
          </div>
          <div class="carousel-item">
            <div class="hero-slide" style="background-image: url('/filestore/images/banners/banner3.jpg');"></div>
          </div>
        </div>
      </div>
    </section>

    <div class="page-block">
      <div class="container">
        <section id="about" class="soft-panel mb-4">
          <div class="row g-4 align-items-center">
            <div class="col-lg-6">
              <span class="section-tag">About UGPSC</span>
              <h2 class="section-title">A bright, practical direction ready for CMS sections.</h2>
              <p class="section-copy">This route keeps the page light and readable, with a larger logo in the header, a clean full-width banner, compact content blocks, and clear member actions. It is designed to be the sensible build-out candidate that can be broken into CMS-managed sections later.</p>
              <div class="mt-3">
                <a href="#" class="btn btn-section-link">Read More About Us</a>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="row g-3">
                <div class="col-sm-6">
                  <div class="fact-card fact-card--green">
                    <span class="fact-value">300+</span>
                    <span class="fact-label">Supporters Reached</span>
                    <p class="fact-text">Ideal placeholder for members, mailing list, or supporter totals.</p>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="fact-card fact-card--accent">
                    <span class="fact-value">24</span>
                    <span class="fact-label">Volunteers</span>
                    <p class="fact-text">A clear, simple stat showing the club’s active support network.</p>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="fact-card">
                    <span class="fact-value">12</span>
                    <span class="fact-label">Club Dates</span>
                    <p class="fact-text">Useful for season planning, event counts, or race support activity.</p>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="fact-card fact-card--green">
                    <span class="fact-value">£8k</span>
                    <span class="fact-label">Fundraising Goal</span>
                    <p class="fact-text">Flexible space for targets, campaigns, or sponsorship progress.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="news" class="news-panel mb-4">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
            <div>
              <span class="section-tag">Latest News</span>
              <h2 class="section-title">Recent updates in a clean card layout.</h2>
              <p class="section-copy">This keeps the two-card news presentation from home 4, but set into the lighter overall style.</p>
            </div>
            <a href="#" class="btn btn-section-link">See All Updates</a>
          </div>
          <div class="row g-3">
            <div class="col-lg-6">
              <div class="card news-card">
                <div class="card-body">
                  <span class="news-meta">Event Note</span>
                  <h3>Spring briefing night moves into the new members lounge</h3>
                  <p>A practical announcement slot for event changes, guest updates, timings, or venue notes before visitors click through.</p>
                  <a href="#" class="btn btn-section-link">Read The Full Story</a>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card news-card">
                <div class="card-body">
                  <span class="news-meta">Membership</span>
                  <h3>Renewal push opens with priority access for existing members</h3>
                  <p>This card can support renewals, online forms, club notices, and short promotional summaries without crowding the layout.</p>
                  <a href="#" class="btn btn-section-link">View Update Details</a>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="gallery" class="gallery-panel mb-4">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
            <div>
              <span class="section-tag">Gallery</span>
              <h2 class="section-title">A strong image finish without overcomplicating the page.</h2>
              <p class="section-copy">The lower gallery follows the more structured home 4 arrangement and keeps the visual interest high.</p>
            </div>
            <a href="#" class="btn btn-section-link">Open Full Gallery</a>
          </div>
          <div class="gallery-grid">
            <img class="gallery-tall" src="/filestore/images/banners/banner2.jpg" alt="Race action at speed">
            <img src="/filestore/images/banners/banner3.jpg" alt="Supporters and paddock atmosphere">
            <img src="/filestore/images/banners/banner1.jpg" alt="Rider lined up before a road race">
            <img src="/filestore/images/content/master/prsentor-4a-1200x800.jpg" alt="Event presentation placeholder">
            <img src="/filestore/images/content/master/istockphoto-1249163124-1024x1024-letterbox.jpg" alt="Motorcycle detail placeholder">
          </div>
        </section>
      </div>
    </div>
  </main>

  <footer id="contact" class="site-footer">
    <div class="container footer-main">
      <div class="row g-4">
        <div class="col-lg-4">
          <h3 class="footer-title">UGPSC</h3>
          <p class="footer-copy mb-0">Motorcycle road race supporters club website concept prepared for CMS breakdown. A clean, content-led route with clear actions for members and new joiners.</p>
          <div class="social-links">
            <a href="#" class="social-chip" aria-label="Facebook">Fb</a>
            <a href="#" class="social-chip" aria-label="Instagram">Ig</a>
            <a href="#" class="social-chip" aria-label="X">X</a>
          </div>
        </div>
        <div class="col-sm-6 col-lg-2">
          <h3 class="footer-title">Pages</h3>
          <ul class="footer-list">
            <li><a href="#" class="footer-link">About Us</a></li>
            <li><a href="#" class="footer-link">Latest News</a></li>
            <li><a href="#" class="footer-link">Events</a></li>
            <li><a href="#" class="footer-link">Gallery</a></li>
          </ul>
        </div>
        <div class="col-sm-6 col-lg-3">
          <h3 class="footer-title">Contact</h3>
          <div class="footer-meta">
            UGPSC Club Office<br>
            12 Race Lane, Dundrod<br>
            County Antrim<br>
            Tel: 028 9000 1234<br>
            Email: hello@ugpsc.example
          </div>
        </div>
        <div class="col-lg-3">
          <h3 class="footer-title">Members</h3>
          <p class="footer-copy">Quick access for returning members and a clear prompt for new joiners.</p>
          <div class="d-flex flex-wrap gap-2">
            <a href="/member-login.php" class="btn btn-member-login">Member Login</a>
            <a href="#" class="btn btn-member-join">Join</a>
          </div>
        </div>
      </div>
    </div>
    <div class="container footer-legal">
      <div class="row g-3">
        <div class="col-md-6">
          <div>Copyright &copy; 2026 UGPSC</div>
          <div><a href="#">Privacy Policy</a> | <a href="#">Cookies Policy</a></div>
        </div>
        <div class="col-md-6 text-md-end">
          <div>Designed and hosted by <a href="#">Truska.com</a></div>
          <div>Powered by <a href="#">WiteCanvas</a></div>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
