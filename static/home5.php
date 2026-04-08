<?php
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UGPSC | Static Home Concept 5</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;700;800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <style>
    :root {
      --shock-green: #5ef07d;
      --shock-green-deep: #1b7e39;
      --shock-red: #ff5b4d;
      --shock-red-deep: #ba2e2e;
      --shock-ink: #09110c;
      --shock-night: #0e1d15;
      --shock-panel: rgba(10, 22, 15, 0.7);
      --shock-line: rgba(255, 255, 255, 0.12);
      --shock-text: #eef8ee;
      --shock-muted: rgba(238, 248, 238, 0.76);
    }

    body {
      font-family: "Instrument Sans", sans-serif;
      color: var(--shock-text);
      background:
        radial-gradient(circle at 15% 18%, rgba(94, 240, 125, 0.22), transparent 0 16%, rgba(94, 240, 125, 0.08) 16%, transparent 17%),
        radial-gradient(circle at 85% 20%, rgba(255, 91, 77, 0.24), transparent 0 15%, rgba(255, 91, 77, 0.08) 15%, transparent 16%),
        radial-gradient(circle at 50% 32%, rgba(255, 255, 255, 0.05), transparent 0 10%, rgba(255, 255, 255, 0.02) 10%, transparent 11%),
        radial-gradient(circle at 50% 50%, #173523 0%, #102319 34%, #0b160f 68%, #050a07 100%);
      min-height: 100vh;
      overflow-x: hidden;
    }

    body::before,
    body::after {
      content: "";
      position: fixed;
      inset: auto;
      width: 48vw;
      height: 48vw;
      border-radius: 50%;
      pointer-events: none;
      z-index: 0;
      filter: blur(26px);
      opacity: 0.32;
    }

    body::before {
      top: -14vw;
      left: -10vw;
      background: radial-gradient(circle, rgba(94, 240, 125, 0.22) 0%, transparent 62%);
    }

    body::after {
      top: 10vw;
      right: -12vw;
      background: radial-gradient(circle, rgba(255, 91, 77, 0.24) 0%, transparent 62%);
    }

    h1, h2, h3, .navbar-brand, .orbit-label {
      font-family: "Syne", sans-serif;
      letter-spacing: 0.01em;
    }

    .site-frame {
      position: relative;
      z-index: 1;
    }

    .alert-band {
      background:
        linear-gradient(90deg, rgba(255, 91, 77, 0.92), rgba(94, 240, 125, 0.84)),
        #111;
      color: #fff;
      font-size: 0.84rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }

    .site-nav-wrap {
      background: rgba(5, 10, 7, 0.62);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(12px);
    }

    .navbar-brand img {
      height: 76px;
      width: auto;
      filter: drop-shadow(0 10px 18px rgba(0, 0, 0, 0.4));
    }

    .navbar-toggler {
      border-color: rgba(255, 255, 255, 0.16);
    }

    .navbar-toggler:focus {
      box-shadow: 0 0 0 0.2rem rgba(94, 240, 125, 0.16);
    }

    .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255,255,255,0.85%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    .nav-link {
      color: rgba(238, 248, 238, 0.9) !important;
      font-weight: 700;
      font-size: 0.88rem;
      letter-spacing: 0.07em;
      text-transform: uppercase;
    }

    .nav-link:hover,
    .nav-link:focus {
      color: var(--shock-green) !important;
    }

    .btn-member-login,
    .btn-member-join,
    .btn-orbit-primary,
    .btn-orbit-secondary {
      border-radius: 999px;
      font-size: 0.9rem;
      font-weight: 800;
      letter-spacing: 0.06em;
      padding: 0.8rem 1.25rem;
      text-transform: uppercase;
    }

    .btn-member-login,
    .btn-orbit-primary {
      background: linear-gradient(135deg, var(--shock-green), #2bcf5f);
      color: #06200d;
      border: none;
      box-shadow: 0 14px 28px rgba(94, 240, 125, 0.22);
    }

    .btn-member-login:hover,
    .btn-orbit-primary:hover {
      background: linear-gradient(135deg, #7bf59a, var(--shock-green));
      color: #06200d;
    }

    .btn-member-join,
    .btn-orbit-secondary {
      background: linear-gradient(135deg, rgba(255, 91, 77, 0.9), rgba(186, 46, 46, 0.92));
      color: #fff;
      border: none;
      box-shadow: 0 14px 28px rgba(255, 91, 77, 0.18);
    }

    .btn-member-join:hover,
    .btn-orbit-secondary:hover {
      background: linear-gradient(135deg, #ff7366, rgba(186, 46, 46, 0.95));
      color: #fff;
    }

    .hero-orbit {
      position: relative;
      padding: 2.25rem 0 1.8rem;
      overflow: hidden;
    }

    .hero-orbit::before,
    .hero-orbit::after {
      content: "";
      position: absolute;
      border-radius: 50%;
      border: 1px solid rgba(255, 255, 255, 0.08);
      pointer-events: none;
    }

    .hero-orbit::before {
      width: 72rem;
      height: 72rem;
      top: -48rem;
      left: 50%;
      transform: translateX(-50%);
      background:
        radial-gradient(circle, rgba(94, 240, 125, 0.09) 0%, transparent 32%),
        radial-gradient(circle at 70% 40%, rgba(255, 91, 77, 0.09) 0%, transparent 28%);
    }

    .hero-orbit::after {
      width: 46rem;
      height: 46rem;
      top: -18rem;
      right: -10rem;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.04) 0%, transparent 64%);
    }

    .hero-grid {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
      gap: 1.4rem;
      align-items: center;
    }

    .hero-copy {
      padding: 3.2rem 0 2.6rem;
    }

    .orbit-label {
      display: inline-block;
      margin-bottom: 1rem;
      padding: 0.45rem 0.85rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.12);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .hero-copy h1 {
      font-size: clamp(3rem, 6.7vw, 6.4rem);
      line-height: 0.9;
      margin-bottom: 1rem;
      max-width: 9ch;
      text-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }

    .hero-copy p {
      max-width: 48ch;
      font-size: clamp(1.05rem, 2vw, 1.25rem);
      color: var(--shock-muted);
      margin-bottom: 1.4rem;
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .hero-metrics {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.8rem;
      margin-top: 1.6rem;
      max-width: 640px;
    }

    .metric-orb {
      position: relative;
      min-height: 136px;
      border-radius: 1.35rem;
      padding: 1rem;
      background:
        radial-gradient(circle at 30% 30%, rgba(94, 240, 125, 0.18), transparent 34%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02)),
        rgba(12, 25, 17, 0.72);
      border: 1px solid var(--shock-line);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }

    .metric-orb:nth-child(2) {
      background:
        radial-gradient(circle at 70% 30%, rgba(255, 91, 77, 0.2), transparent 34%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02)),
        rgba(12, 25, 17, 0.72);
    }

    .metric-orb:nth-child(3) {
      background:
        radial-gradient(circle at 50% 22%, rgba(255, 255, 255, 0.12), transparent 30%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.02)),
        rgba(12, 25, 17, 0.72);
    }

    .metric-value {
      display: block;
      font-family: "Syne", sans-serif;
      font-size: clamp(1.9rem, 3vw, 2.7rem);
      line-height: 1;
    }

    .metric-label {
      display: block;
      margin-top: 0.4rem;
      font-size: 0.76rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--shock-muted);
    }

    .metric-copy {
      margin: 0.55rem 0 0;
      font-size: 0.9rem;
      color: rgba(238, 248, 238, 0.72);
    }

    .radial-stage {
      position: relative;
      min-height: 640px;
      display: grid;
      place-items: center;
    }

    .radial-core {
      position: relative;
      width: min(34rem, 100%);
      aspect-ratio: 1;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background:
        radial-gradient(circle, rgba(94, 240, 125, 0.2) 0%, rgba(94, 240, 125, 0.05) 20%, rgba(14, 29, 21, 0.88) 52%, rgba(8, 17, 12, 0.96) 100%);
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow:
        0 0 0 18px rgba(255, 255, 255, 0.02),
        0 0 0 42px rgba(255, 255, 255, 0.02),
        0 40px 80px rgba(0, 0, 0, 0.35);
    }

    .radial-core::before,
    .radial-core::after {
      content: "";
      position: absolute;
      inset: 14%;
      border-radius: 50%;
      border: 1px dashed rgba(255, 255, 255, 0.08);
    }

    .radial-core::after {
      inset: 5%;
      border-style: solid;
      border-color: rgba(94, 240, 125, 0.12);
    }

    .core-logo {
      width: 8.5rem;
      height: 8.5rem;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, rgba(94, 240, 125, 0.2), rgba(255, 91, 77, 0.18));
      border: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: 0 20px 34px rgba(0, 0, 0, 0.3);
      margin: 0 auto 1rem;
    }

    .core-logo img {
      width: 5rem;
      height: auto;
      filter: drop-shadow(0 8px 14px rgba(0, 0, 0, 0.3));
    }

    .core-text {
      max-width: 16rem;
      text-align: center;
      padding: 0 1rem;
    }

    .core-text h2 {
      font-size: clamp(1.6rem, 2.7vw, 2.5rem);
      margin-bottom: 0.4rem;
    }

    .core-text p {
      margin-bottom: 0;
      color: var(--shock-muted);
      font-size: 0.95rem;
    }

    .orbit-card {
      position: absolute;
      width: clamp(180px, 14vw, 240px);
      padding: 1rem;
      border-radius: 1.2rem;
      background: var(--shock-panel);
      border: 1px solid var(--shock-line);
      box-shadow: 0 18px 38px rgba(0, 0, 0, 0.22);
      backdrop-filter: blur(12px);
    }

    .orbit-card h3 {
      font-size: 1.1rem;
      margin-bottom: 0.35rem;
    }

    .orbit-card p {
      margin-bottom: 0;
      color: var(--shock-muted);
      font-size: 0.88rem;
      line-height: 1.45;
    }

    .orbit-card.orbit-1 {
      top: 3%;
      left: 10%;
    }

    .orbit-card.orbit-2 {
      top: 18%;
      right: 0;
    }

    .orbit-card.orbit-3 {
      bottom: 18%;
      left: 2%;
    }

    .orbit-card.orbit-4 {
      bottom: 4%;
      right: 9%;
    }

    .content-shell {
      padding-bottom: 2rem;
    }

    .glass-panel,
    .news-panel,
    .gallery-panel,
    .footer-panel {
      position: relative;
      overflow: hidden;
      border-radius: 1.6rem;
      border: 1px solid rgba(255, 255, 255, 0.08);
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.03)),
        rgba(9, 17, 12, 0.72);
      box-shadow: 0 24px 56px rgba(0, 0, 0, 0.18);
      backdrop-filter: blur(14px);
    }

    .glass-panel::before,
    .news-panel::before,
    .gallery-panel::before,
    .footer-panel::before {
      content: "";
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at top right, rgba(94, 240, 125, 0.08), transparent 26%),
        radial-gradient(circle at bottom left, rgba(255, 91, 77, 0.08), transparent 24%);
      pointer-events: none;
    }

    .glass-panel,
    .news-panel,
    .gallery-panel {
      padding: 1.6rem;
    }

    .section-title {
      font-size: clamp(1.9rem, 3.4vw, 3.6rem);
      margin-bottom: 0.45rem;
    }

    .section-copy {
      color: var(--shock-muted);
      margin-bottom: 0;
    }

    .section-eyebrow {
      display: inline-block;
      margin-bottom: 0.8rem;
      padding: 0.35rem 0.75rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 0.76rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--shock-green);
    }

    .fact-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 1rem;
    }

    .fact-node {
      position: relative;
      min-height: 180px;
      border-radius: 1.4rem;
      padding: 1.2rem;
      background:
        radial-gradient(circle at 20% 20%, rgba(94, 240, 125, 0.16), transparent 34%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02)),
        rgba(10, 22, 15, 0.72);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .fact-node:nth-child(2),
    .fact-node:nth-child(4) {
      background:
        radial-gradient(circle at 75% 20%, rgba(255, 91, 77, 0.17), transparent 34%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02)),
        rgba(10, 22, 15, 0.72);
    }

    .fact-value {
      display: block;
      font-family: "Syne", sans-serif;
      font-size: clamp(2rem, 3vw, 3.1rem);
      line-height: 1;
    }

    .fact-label {
      display: block;
      margin-top: 0.45rem;
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--shock-green);
    }

    .fact-node:nth-child(2) .fact-label,
    .fact-node:nth-child(4) .fact-label {
      color: #ff9e8d;
    }

    .fact-node p {
      margin: 0.65rem 0 0;
      color: var(--shock-muted);
      font-size: 0.9rem;
    }

    .news-card {
      height: 100%;
      border-radius: 1.25rem;
      border: 1px solid rgba(255, 255, 255, 0.08);
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.03)),
        rgba(7, 14, 10, 0.72);
      color: var(--shock-text);
    }

    .news-card .card-body {
      padding: 1.35rem;
    }

    .news-meta {
      display: inline-block;
      margin-bottom: 0.65rem;
      font-size: 0.76rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #ff9e8d;
    }

    .news-card h3 {
      font-size: 1.45rem;
      margin-bottom: 0.5rem;
    }

    .news-card p {
      color: var(--shock-muted);
      margin-bottom: 1rem;
    }

    .news-link {
      color: var(--shock-green);
      font-weight: 700;
      text-decoration: none;
    }

    .news-link:hover {
      color: #95ffaa;
      text-decoration: underline;
    }

    .gallery-layout {
      display: grid;
      gap: 1rem;
      grid-template-columns: 1.2fr 1fr 0.9fr;
      grid-template-rows: repeat(2, minmax(170px, auto));
    }

    .gallery-layout img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 1.25rem;
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 20px 34px rgba(0, 0, 0, 0.16);
    }

    .gallery-layout .lead-shot {
      grid-row: span 2;
      min-height: 360px;
    }

    .footer-panel {
      padding: 1.35rem 1.6rem;
      margin-top: 2rem;
    }

    .footer-copy {
      color: var(--shock-muted);
      margin-bottom: 0;
    }

    @media (max-width: 1199.98px) {
      .hero-grid {
        grid-template-columns: 1fr;
      }

      .hero-copy h1 {
        max-width: 12ch;
      }

      .radial-stage {
        min-height: 560px;
      }

      .orbit-card.orbit-1 {
        left: 2%;
      }

      .orbit-card.orbit-2 {
        right: 2%;
      }

      .fact-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 991.98px) {
      .navbar-brand img {
        height: 62px;
      }

      .hero-metrics {
        grid-template-columns: 1fr;
        max-width: 100%;
      }

      .radial-stage {
        min-height: auto;
        padding: 0.5rem 0 0;
      }

      .radial-core {
        width: min(28rem, 100%);
      }

      .orbit-card {
        position: relative;
        inset: auto !important;
        width: 100%;
      }

      .orbit-stack {
        display: grid;
        gap: 0.9rem;
        margin-top: 1rem;
      }

      .gallery-layout {
        grid-template-columns: 1fr 1fr;
      }

      .gallery-layout .lead-shot {
        grid-row: span 1;
        min-height: 230px;
      }
    }

    @media (max-width: 767.98px) {
      .hero-copy {
        padding-top: 2.2rem;
      }

      .hero-copy h1 {
        font-size: 2.7rem;
        max-width: none;
      }

      .fact-grid,
      .gallery-layout {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="site-frame">
    <div class="alert-band py-2">
      <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>UGPSC Motorcycle Road Race Supporters Club</div>
        <div>Alternative homepage concept 05</div>
      </div>
    </div>

    <div class="site-nav-wrap sticky-top">
      <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container">
          <a class="navbar-brand d-flex align-items-center" href="/static/home5.php">
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
      <section class="hero-orbit">
        <div class="container">
          <div class="hero-grid">
            <div class="hero-copy">
              <span class="orbit-label">Radial Showpiece Concept</span>
              <h1>This is the one that makes them stop scrolling.</h1>
              <p>Home 5 keeps the same useful homepage ingredients as the other demos, but turns the whole presentation into a dramatic radial stage: glowing rings, floating content nodes, glass panels, and a much more theatrical sense of energy.</p>
              <div class="hero-actions">
                <a href="/member-login.php" class="btn btn-orbit-primary">Member Login</a>
                <a href="#" class="btn btn-orbit-secondary">Join UGPSC</a>
              </div>
              <div class="hero-metrics">
                <div class="metric-orb">
                  <span class="metric-value">65+</span>
                  <span class="metric-label">Years of support</span>
                  <p class="metric-copy">A fast visual hit for legacy and credibility.</p>
                </div>
                <div class="metric-orb">
                  <span class="metric-value">2</span>
                  <span class="metric-label">Latest news cards</span>
                  <p class="metric-copy">Still practical, just presented louder.</p>
                </div>
                <div class="metric-orb">
                  <span class="metric-value">5</span>
                  <span class="metric-label">Concept routes</span>
                  <p class="metric-copy">Easy for the client to compare and mix ideas.</p>
                </div>
              </div>
            </div>

            <div class="radial-stage">
              <div class="radial-core">
                <div class="core-text">
                  <div class="core-logo">
                    <img src="/filestore/images/logos/ugpsc-logo.png" alt="UGPSC logo">
                  </div>
                  <h2>UGPSC Central</h2>
                  <p>A homepage built like a visual hub, with the club story and calls to action placed at the centre.</p>
                </div>
              </div>

              <div class="orbit-card orbit-1">
                <h3>About</h3>
                <p>Long-running motorcycle road race supporters club with a clear member-first focus.</p>
              </div>
              <div class="orbit-card orbit-2">
                <h3>Events</h3>
                <p>Race dates, club nights, fundraiser pushes, and season build-up can all branch from here.</p>
              </div>
              <div class="orbit-card orbit-3">
                <h3>News</h3>
                <p>Latest updates stay compact and readable, even in a much more expressive layout.</p>
              </div>
              <div class="orbit-card orbit-4">
                <h3>Gallery</h3>
                <p>Strong image placement remains part of the sales pitch at the bottom of the page.</p>
              </div>
            </div>

            <div class="orbit-stack d-lg-none">
              <div class="orbit-card">
                <h3>About</h3>
                <p>Long-running motorcycle road race supporters club with a clear member-first focus.</p>
              </div>
              <div class="orbit-card">
                <h3>Events</h3>
                <p>Race dates, club nights, fundraiser pushes, and season build-up can all branch from here.</p>
              </div>
              <div class="orbit-card">
                <h3>News</h3>
                <p>Latest updates stay compact and readable, even in a much more expressive layout.</p>
              </div>
              <div class="orbit-card">
                <h3>Gallery</h3>
                <p>Strong image placement remains part of the sales pitch at the bottom of the page.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="content-shell">
        <div class="container">
          <section class="glass-panel mb-4">
            <div class="row g-4 align-items-center">
              <div class="col-lg-7">
                <span class="section-eyebrow">About UGPSC</span>
                <h2 class="section-title">Same core content, presented like a launch poster.</h2>
                <p class="section-copy">This still works as a real website homepage: there is a quick club introduction, facts and figures, latest news, a gallery, and the member join/login routes. The difference is that the entire page now feels like an event in itself.</p>
              </div>
              <div class="col-lg-5">
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                  <a href="/member-login.php" class="btn btn-orbit-primary">Existing Members</a>
                  <a href="#" class="btn btn-orbit-secondary">Become A Member</a>
                </div>
              </div>
            </div>
          </section>

          <section class="glass-panel mb-4">
            <div class="row g-4 align-items-end">
              <div class="col-lg-4">
                <span class="section-eyebrow">Facts & Figures</span>
                <h2 class="section-title">Numbers with some theatre.</h2>
                <p class="section-copy">These can become editable counts later, but here they help sell momentum, scale, and club energy.</p>
              </div>
              <div class="col-lg-8">
                <div class="fact-grid">
                  <div class="fact-node">
                    <span class="fact-value">300+</span>
                    <span class="fact-label">Supporters Reached</span>
                    <p>Ideal for active members, email subscribers, or event audience reach.</p>
                  </div>
                  <div class="fact-node">
                    <span class="fact-value">24</span>
                    <span class="fact-label">Volunteers</span>
                    <p>Shows race-day backing and reinforces the club’s practical support role.</p>
                  </div>
                  <div class="fact-node">
                    <span class="fact-value">12</span>
                    <span class="fact-label">Club Dates</span>
                    <p>A simple seasonal count that makes the homepage feel active and current.</p>
                  </div>
                  <div class="fact-node">
                    <span class="fact-value">£8k</span>
                    <span class="fact-label">Fund Target</span>
                    <p>Useful for fundraising drives, rider backing, or sponsor-led campaigns.</p>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section class="news-panel mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
              <div>
                <span class="section-eyebrow">Latest News</span>
                <h2 class="section-title">Recent updates, still clean and usable.</h2>
                <p class="section-copy">Even with the big visual direction, the news remains simple Bootstrap cards so the practical content still lands.</p>
              </div>
              <a href="#" class="news-link">See all updates</a>
            </div>
            <div class="row g-3">
              <div class="col-lg-6">
                <div class="card news-card">
                  <div class="card-body">
                    <span class="news-meta">Club Update</span>
                    <h3>Season launch event expands with guest riders and live club announcements</h3>
                    <p>The first news slot stays compact enough for a homepage teaser while still feeling substantial in a more dramatic design system.</p>
                    <a href="#" class="news-link">Read the full story</a>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="card news-card">
                  <div class="card-body">
                    <span class="news-meta">Fundraiser</span>
                    <h3>Volunteer and sponsorship drive opens ahead of the next run of race weekends</h3>
                    <p>This second card can carry membership pushes, volunteer calls, fundraising asks, or short event bulletins without losing readability.</p>
                    <a href="#" class="news-link">View update details</a>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section class="gallery-panel">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
              <div>
                <span class="section-eyebrow">Gallery</span>
                <h2 class="section-title">Finish with a proper visual punch.</h2>
                <p class="section-copy">The page closes with a strong image block so the client gets one more memorable hit before the footer.</p>
              </div>
              <a href="#" class="news-link">Open full gallery</a>
            </div>
            <div class="gallery-layout">
              <img class="lead-shot" src="/filestore/images/banners/banner3.jpg" alt="Supporters and paddock atmosphere">
              <img src="/filestore/images/banners/banner1.jpg" alt="Rider lined up before a road race">
              <img src="/filestore/images/banners/banner2.jpg" alt="Race action at speed">
              <img src="/filestore/images/content/master/istockphoto-1249163124-1024x1024-letterbox.jpg" alt="Motorcycle detail placeholder">
              <img src="/filestore/images/content/master/prsentor-4a-1200x800.jpg" alt="Event presentation placeholder">
            </div>
          </section>

          <footer id="contact" class="footer-panel">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
              <div>UGPSC Static Home Concept 05</div>
              <p class="footer-copy">Radial, high-drama presentation concept designed to feel unlike the rest while still behaving like a usable homepage.</p>
            </div>
          </footer>
        </div>
      </section>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
