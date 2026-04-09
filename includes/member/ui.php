<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/stripe.php';

function mem_page_header(string $title = 'UGPSC Members Area', array $options = []): void {
  $active = $options['active'] ?? '';
  $member = mem_current_member();
  $isLoggedIn = mem_is_logged_in();
  $base = mem_base_url();
  $siteHome = cms_base_url();
  $siteName = trim((string) cms_pref('prefSiteName', 'WCCMS'));
  $companyName = trim((string) cms_pref('prefCompanyName', $siteName));
  $logoName = trim((string) cms_pref('prefLogoName', $siteName));
  $logoFile = trim((string) cms_pref('prefLogo', ''));
  if ($logoFile === '') {
    $logoFile = trim((string) cms_pref('prefLogo1', ''));
  }
  if ($logoFile === '') {
    $logoFile = 'ugpsc-logo.png';
  }
  if (preg_match('#^https?://#i', $logoFile) || str_starts_with($logoFile, '/')) {
    $logoUrl = $logoFile;
  } else {
    $logoUrl = $base . '/filestore/images/logos/' . ltrim($logoFile, '/');
  }
  ?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow,noarchive">
  <title><?php echo mem_h($title); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <style>
    :root {
      --mem-green: #1f5a3f;
      --mem-red: #bf3b2b;
      --mem-bg: #f7faf8;
      --mem-ink: #152025;
      --mem-line: #d7e0db;
      --mem-link: #2d4148;
      --mem-link-hover: #1f5a3f;
    }
    body {
      background: var(--mem-bg);
      color: var(--mem-ink);
      font-family: "Public Sans", sans-serif;
      min-height: 100vh;
    }
    .mem-top {
      background: linear-gradient(90deg, var(--mem-green), #254e3c);
      color: #fff;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .mem-nav {
      background: #fff;
      border-bottom: 1px solid var(--mem-line);
    }
    .mem-nav .navbar-brand img {
      height: 72px;
      width: auto;
    }
    .mem-nav .nav-link {
      text-transform: uppercase;
      font-size: 0.86rem;
      letter-spacing: 0.08em;
      color: var(--mem-link);
      font-weight: 600;
    }
    .mem-nav .nav-link.active,
    .mem-nav .nav-link:hover,
    .mem-nav .nav-link:focus {
      color: var(--mem-green);
    }
    a {
      color: var(--mem-link);
      text-decoration: none;
    }
    a:hover,
    a:focus {
      color: var(--mem-link-hover);
      text-decoration: underline;
    }
    .mem-card {
      background: #fff;
      border: 1px solid var(--mem-line);
      border-radius: 0.95rem;
      box-shadow: 0 10px 26px rgba(17, 27, 31, 0.05);
    }
    .btn-mem-primary {
      background: var(--mem-red);
      color: #fff;
      border: none;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      font-size: 0.85rem;
      padding: 0.58rem 0.95rem;
      font-weight: 600;
    }
    .btn-mem-primary:hover {
      background: #aa2f22;
      color: #fff;
    }
    .mem-label {
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #51656d;
      margin-bottom: 0.25rem;
    }
    .mem-footer {
      border-top: 1px solid var(--mem-line);
      color: #60717a;
      font-size: 0.86rem;
    }
    @media (max-width: 575.98px) {
      .mem-nav .navbar-brand img {
        height: 60px;
      }
    }
  </style>
</head>
<body>
  <div class="mem-top py-2">
    <div class="container d-flex justify-content-between flex-wrap gap-2">
      <div><?php echo mem_h($siteName); ?> Members Area</div>
      <div>Secure Access</div>
    </div>
  </div>
  <div class="mem-nav sticky-top">
    <nav class="navbar navbar-expand-lg" aria-label="Member navigation">
      <div class="container">
        <a class="navbar-brand" href="<?php echo mem_h($siteHome . '/'); ?>">
          <img src="<?php echo mem_h($logoUrl); ?>" alt="<?php echo mem_h($logoName); ?> logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#memberMenu" aria-controls="memberMenu" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="memberMenu">
          <ul class="navbar-nav ms-auto gap-lg-2 align-items-lg-center">
            <li class="nav-item"><a class="nav-link" href="<?php echo mem_h($siteHome . '/'); ?>">Site Home</a></li>
            <li class="nav-item"><a class="nav-link <?php echo $active === 'login' ? 'active' : ''; ?>" href="<?php echo mem_h($base . '/member-login.php'); ?>">Login</a></li>
            <li class="nav-item"><a class="nav-link <?php echo $active === 'join' ? 'active' : ''; ?>" href="<?php echo mem_h($base . '/member-join.php'); ?>">Join</a></li>
            <?php if ($isLoggedIn): ?>
              <li class="nav-item"><a class="nav-link <?php echo $active === 'dashboard' ? 'active' : ''; ?>" href="<?php echo mem_h($base . '/member-dashboard.php'); ?>">Dashboard</a></li>
              <li class="nav-item"><a class="nav-link <?php echo $active === 'profile' ? 'active' : ''; ?>" href="<?php echo mem_h($base . '/member-profile.php'); ?>">My Details</a></li>
              <?php if (!empty($member['is_admin'])): ?>
                <li class="nav-item"><a class="nav-link <?php echo $active === 'admin' ? 'active' : ''; ?>" href="<?php echo mem_h($base . '/member-admin-dashboard.php'); ?>">Admin</a></li>
              <?php endif; ?>
              <li class="nav-item"><a class="btn btn-mem-primary" href="<?php echo mem_h($base . '/member-logout.php'); ?>">Logout</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="<?php echo mem_h($base . '/member-forgot-password.php'); ?>">Forgot Password</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
  </div>
  <main class="py-4 py-lg-5">
<?php
}

function mem_page_footer(): void {
  ?>
  </main>
  <footer class="mem-footer py-4 mt-4">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
      <div><?php echo mem_h($companyName); ?> Members v1</div>
      <div>Core login and dashboard foundation</div>
    </div>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
<?php
}
