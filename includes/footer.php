<?php
$baseURL = cms_base_url();
$siteName = cms_pref('prefSiteName', 'UGPSC');
$companyName = cms_pref('prefCompanyName', $siteName);
$email = cms_pref('prefEmail', '');
$telData = cms_tel_data('prefTel1', 'prefTelIntCode', '');
$logoFile = cms_pref('prefLogo', cms_pref('prefLogo1', 'ugpsc-logo.png'));
if ($logoFile !== '' && !preg_match('#^https?://#i', $logoFile)) {
  $logoUrl = $baseURL . '/filestore/images/logos/' . ltrim($logoFile, '/');
} else {
  $logoUrl = $logoFile;
}

$socials = [];
$socialQuery = "SELECT id, name, url, titletag, alttag, icon, sort
            FROM socials
            WHERE archived = 0
              AND showonweb = 'Yes'
              AND url IS NOT NULL
              AND TRIM(url) <> ''
            ORDER BY sort ASC, id ASC";
if (isset($pdo, $DB_OK) && $DB_OK && ($pdo instanceof PDO)) {
  try {
    $stmt = $pdo->query($socialQuery);
    $socials = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
  } catch (Throwable $e) {
    $socials = [];
  }
}
?>
<style>
  .site-footer {
    background: #10231c;
    color: #d9e3de;
    padding: 2.5rem 0 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.08);
  }
.site-footer a {
    color: #d9e3de;
    text-decoration: none;
  }
  .site-footer a:hover {
    color: #fff;
    text-decoration: none;
  }
  .site-footer h4,
  .site-footer h5 {
    color: #f5f8f6;
    letter-spacing: 0.04em;
  }
  .site-footer .footer-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 0.4rem;
  }
  .site-footer .footer-list li {
    line-height: 1.5;
  }
  .site-footer .social-links a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.2);
    margin-right: 8px;
  }
  .site-footer .footer-logo {
    max-width: 140px;
  }
  .site-footer .footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.08);
    padding-top: 1rem;
    margin-top: 1.5rem;
    color: #a9b6af;
    font-size: 0.9rem;
  }
  .site-footer .social-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.6rem;
  }
  .site-footer .social-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.6rem;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 0.5rem;
    background: rgba(255,255,255,0.02);
    transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
  }
  .site-footer .social-item:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.18);
    transform: translateY(-1px);
  }
  .site-footer .social-icon {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.08);
    color: #fff;
    flex-shrink: 0;
    font-size: 30px;
  }
  .site-footer .social-text {
    display: flex;
    flex-direction: column;
    line-height: 1.3;
  }
  .site-footer .social-text .name {
    font-weight: 600;
  }
</style>
<footer id="contact" class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <h4><?php echo cms_h($companyName); ?></h4>
        <p>Supporting the Ulster Grand Prix community with member funding, news, and events.</p>
        <?php if ($logoUrl !== ''): ?>
          <img src="<?php echo cms_h($logoUrl); ?>" alt="<?php echo cms_h($companyName); ?> logo" class="img-fluid footer-logo mt-2">
        <?php endif; ?>
      </div>
      <div class="col-md-6 col-lg-3">
        <h5>Contact</h5>
        <ul class="footer-list">
          <?php if (!empty($telData['dial'])): ?>
            <li><a href="tel:<?php echo cms_h($telData['dial']); ?>">Tel: <?php echo cms_h($telData['display']); ?></a></li>
          <?php endif; ?>
          <?php if ($email !== ''): ?>
            <li><a href="mailto:<?php echo cms_h($email); ?>"><?php echo cms_h($email); ?></a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="col-md-6 col-lg-3">
        <h5>Explore</h5>
        <ul class="footer-list">
          <li><a href="<?php echo $baseURL; ?>/member-join.php">Join</a></li>
          <li><a href="<?php echo $baseURL; ?>/member-login.php">Member Login</a></li>
          <li><a href="<?php echo $baseURL; ?>/member-dashboard.php">Dashboard</a></li>
          <li><a href="<?php echo $baseURL; ?>/member-admin-dashboard.php">Admin</a></li>
        </ul>
      </div>
      <div class="col-md-6 col-lg-3">
        <h5>Follow</h5>
        <?php if ($socials): ?>
          <div class="social-grid">
            <?php foreach ($socials as $s): ?>
              <?php
                $name = trim((string) ($s['name'] ?? ''));
                $href = trim((string) ($s['url'] ?? ''));
                $title = trim((string) ($s['titletag'] ?? $name));
                $alt = trim((string) ($s['alttag'] ?? $name));
                $iconVal = $s['icon'] ?? null;
                $iconClass = null;
                if (is_numeric($iconVal) && isset($pdo)) {
                  try {
                    $stmtIcon = $pdo->prepare('SELECT iconfamilyv7, iconstylev7, iconcodev7, code FROM cms_icons WHERE id = :id LIMIT 1');
                    $stmtIcon->execute([':id' => (int) $iconVal]);
                    $iconRow = $stmtIcon->fetch(PDO::FETCH_ASSOC);
                    if ($iconRow) {
                      $fam = trim((string) ($iconRow['iconfamilyv7'] ?? ''));
                      $sty = trim((string) ($iconRow['iconstylev7'] ?? ''));
                      $cod = trim((string) ($iconRow['iconcodev7'] ?? ''));
                      if ($cod === '' && !empty($iconRow['code'])) {
                        $cod = trim((string) $iconRow['code']);
                      }
                      $brandTokens = ['facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok', 'whatsapp', 'snapchat', 'pinterest', 'github', 'gitlab', 'discord', 'reddit', 'slack', 'messenger'];
                      $isBrand = false;
                      foreach ($brandTokens as $token) {
                        if ($cod !== '' && stripos($cod, $token) !== false) {
                          $isBrand = true;
                          break;
                        }
                      }
                      $fam = $fam !== '' ? $fam : ($isBrand ? 'fa-brands' : 'fa-solid');
                      $sty = $sty !== '' ? $sty : $fam;
                      $cod = $cod !== '' ? $cod : 'fa-link';

                      $classes = [];
                      $classes[] = str_starts_with($fam, 'fa-') ? $fam : 'fa-' . $fam;
                      $classes[] = str_starts_with($sty, 'fa-') ? $sty : 'fa-' . $sty;
                      $classes[] = str_starts_with($cod, 'fa-') ? $cod : 'fa-' . $cod;
                      $iconClass = implode(' ', array_unique($classes));
                    }
                  } catch (Throwable $e) {
                    $iconClass = null;
                  }
                } elseif (is_string($iconVal) && trim($iconVal) !== '') {
                  $iconClass = trim($iconVal);
                }
              ?>
              <a class="social-item" href="<?php echo cms_h($href); ?>" target="_blank" rel="noopener" title="<?php echo cms_h($title); ?>" aria-label="<?php echo cms_h($alt); ?>">
                <span class="social-icon" aria-hidden="true">
                  <?php if ($iconClass): ?>
                    <i class="<?php echo cms_h($iconClass); ?>"></i>
                  <?php else: ?>
                    <i class="fa-solid fa-link"></i>
                  <?php endif; ?>
                </span>
                <span class="social-text">
                  <span class="name"><?php echo cms_h($name !== '' ? $name : 'Social'); ?></span>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-secondary">Follow us on social media.</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <span>© <?php echo date('Y'); ?> <?php echo cms_h($companyName); ?>. All rights reserved.</span>
      <span>Built on wITeCanvas — By Truska</span>
    </div>
  </div>
</footer>
