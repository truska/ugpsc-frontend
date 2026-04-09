<?php
// Shared metadata include. Included from header-code.php after title/description/keywords.
$siteNameMeta = $siteName ?? trim((string) cms_pref('prefSiteName', 'WCCMS'));
$companyMeta = trim((string) cms_pref('prefCompanyName', $siteNameMeta));
$authorMeta = $companyMeta !== '' ? $companyMeta : $siteNameMeta;
$ownerEmail = trim((string) cms_pref('prefEmail', ''));
$canonicalBase = cms_base_url();
$currentUrl = $canonicalBase . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
$logoFile = trim((string) cms_pref('prefLogo', ''));
if ($logoFile === '') {
  $logoFile = trim((string) cms_pref('prefLogo1', ''));
}
if ($logoFile === '') {
  $logoFile = 'ugpsc-logo.png';
}
if (preg_match('#^https?://#i', $logoFile) || str_starts_with($logoFile, '/')) {
  $ogImage = $logoFile;
} else {
  $ogImage = $canonicalBase . '/filestore/images/logos/' . ltrim($logoFile, '/');
}

$robotsPref = strtolower((string) cms_pref('prefSiteSearchOn', 'No'));
$isLocal = !empty($IS_LOCAL);
$pageSearchAllowed = isset($pageSearchAllowed) ? (bool) $pageSearchAllowed : true;
$allowIndex = ($robotsPref === 'yes') && !$isLocal && $pageSearchAllowed;
$robots = $allowIndex ? 'index,follow,snippet,archive' : 'noindex,nofollow,noarchive';
?>
<meta name="author" content="<?php echo cms_h($authorMeta); ?>">
<meta name="owner" content="<?php echo cms_h($authorMeta . ($ownerEmail ? ', ' . $ownerEmail : '')); ?>">
<meta name="copyright" content="<?php echo cms_h($authorMeta); ?>">
<meta name="designer" content="site designed by digita.agency | https://www.digita.agency/">
<meta name="platform" content="wITeCanvas powered by Truska | https://witecanvas.com">
<meta name="url" content="<?php echo cms_h($canonicalBase); ?>">
<meta name="coverage" content="Worldwide">
<meta name="document-rights" content="Copyrighted Work">
<meta name="robots" content="<?php echo cms_h($robots); ?>">

<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo cms_h($pageTitle ?? $siteNameMeta); ?>">
<?php if (!empty($pageMetaDescription)): ?>
<meta property="og:description" content="<?php echo cms_h($pageMetaDescription); ?>">
<?php endif; ?>
<meta property="og:url" content="<?php echo cms_h($currentUrl); ?>">
<meta property="og:site_name" content="<?php echo cms_h($siteNameMeta); ?>">
<meta property="og:image" content="<?php echo cms_h($ogImage); ?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo cms_h($pageTitle ?? $siteNameMeta); ?>">
<?php if (!empty($pageMetaDescription)): ?>
<meta name="twitter:description" content="<?php echo cms_h($pageMetaDescription); ?>">
<?php endif; ?>
<meta name="twitter:image" content="<?php echo cms_h($ogImage); ?>">
