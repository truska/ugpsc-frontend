<?php
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
  $logoUrl = $baseURL . '/filestore/images/logos/' . ltrim($logoFile, '/');
}
$toplineLeft = $companyName !== '' ? $companyName : $siteName;
$toplineRight = $siteName !== '' ? $siteName : $toplineLeft;
$memberSession = isset($_SESSION['mem_member']) && is_array($_SESSION['mem_member'])
  ? $_SESSION['mem_member']
  : null;
$memberIsLoggedIn = !empty($memberSession['id']);
$memberIdentity = '';
if ($memberIsLoggedIn) {
  $memberName = trim((string) ($memberSession['firstname'] ?? '') . ' ' . (string) ($memberSession['surname'] ?? ''));
  $membershipNumber = (int) ($memberSession['membership_number'] ?? 0);
  $memberIdentity = $memberName;
  if ($membershipNumber > 0) {
    $memberIdentity .= ($memberIdentity !== '' ? ' ' : '') . '[' . $membershipNumber . ']';
  }
}
?>
<?php include __DIR__ . '/development-banner.php'; ?>
<header class="site-header">
  <div class="header-topline py-2">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div><?php echo cms_h($toplineLeft); ?></div>
      <div class="header-topline-meta d-flex flex-wrap justify-content-end align-items-center gap-2 gap-sm-3">
        <?php if ($memberIdentity !== ''): ?>
          <span class="header-member-identity"><?php echo cms_h($memberIdentity); ?></span>
        <?php endif; ?>
        <span><?php echo cms_h($toplineRight); ?></span>
      </div>
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
              <?php if ($memberIsLoggedIn): ?>
                <a href="<?php echo cms_h($baseURL . '/member-dashboard.php'); ?>" class="btn btn-member-login">Return to My Dashboard</a>
                <a href="<?php echo cms_h($baseURL . '/member-logout.php'); ?>" class="btn btn-member-join">Logout</a>
              <?php else: ?>
                <a href="<?php echo cms_h($baseURL . '/member-login.php'); ?>" class="btn btn-member-login">Member Login</a>
                <a href="<?php echo cms_h($baseURL . '/member-join.php'); ?>" class="btn btn-member-join">Join</a>
              <?php endif; ?>
            </div>
            <?php include __DIR__ . '/menu.php'; ?>
          </div>
        </div>
      </div>
    </nav>
  </div>
</header>
