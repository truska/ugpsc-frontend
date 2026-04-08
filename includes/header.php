<?php
$siteName = trim((string) cms_pref('prefSiteName', 'UGPSC'));
$companyName = trim((string) cms_pref('prefCompanyName', $siteName));
$logoName = trim((string) cms_pref('prefLogoName', $siteName));
$logoFile = trim((string) cms_pref('prefLogo', ''));
if ($logoFile === '') {
  $logoFile = trim((string) cms_pref('prefLogo1', ''));
}
if ($logoFile === '' || stripos($logoFile, 'itfix') !== false) {
  $logoFile = 'ugpsc-logo.png';
}
if (preg_match('#^https?://#i', $logoFile) || str_starts_with($logoFile, '/')) {
  $logoUrl = $logoFile;
} else {
  $logoUrl = $baseURL . '/filestore/images/logos/' . ltrim($logoFile, '/');
}
$toplineLeft = $companyName !== '' ? $companyName : $siteName;
$toplineRight = $siteName !== '' ? $siteName : $toplineLeft;
?>
<?php if (!empty($IS_LOCAL)): ?>
  <div class="dev-banner">
    Development Site
  </div>
<?php endif; ?>
<header class="site-header">
  <div class="header-topline py-2">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div><?php echo cms_h($toplineLeft); ?></div>
      <div class="header-topline-meta"><?php echo cms_h($toplineRight); ?></div>
    </div>
  </div>
  <div class="site-nav-wrap sticky-top">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
      <div class="container">
        <a href="<?php echo cms_h($baseURL . '/'); ?>" class="navbar-brand d-flex align-items-center">
          <img src="<?php echo cms_h($logoUrl); ?>" alt="<?php echo cms_h($logoName); ?> logo" class="img-fluid site-logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteMenu" aria-controls="siteMenu" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="siteMenu">
          <div class="header-nav-right ms-lg-auto">
            <div class="header-actions d-flex flex-wrap gap-2">
              <a href="<?php echo cms_h($baseURL . '/member-login.php'); ?>" class="btn btn-member-login">Member Login</a>
              <a href="<?php echo cms_h($baseURL . '/member-join.php'); ?>" class="btn btn-member-join">Join</a>
            </div>
            <?php include __DIR__ . '/menu.php'; ?>
          </div>
        </div>
      </div>
    </nav>
  </div>
</header>
