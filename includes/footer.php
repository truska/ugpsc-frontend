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
    text-decoration: underline;
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
        <div class="social-links">
          <!--<a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>-->

          <a href="https://www.facebook.com/profile.php?id=100057528366793" aria-label="Ulster Grand Supporthers Club" target="_blank">
            <i class="fa-brands fa-facebook"></i><br>Ulster Grand Supporters Club
          </a>

          <a href="https://www.facebook.com/UlsterGrandPrix" aria-label="Ulster Grand Prix" target="_blank">
            <i class="fa-brands fa-facebook"></i><BR>"Ulster Grand Prix"
          </a>

        </div>
      </div>
    </div>
    <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <span>© <?php echo date('Y'); ?> <?php echo cms_h($companyName); ?>. All rights reserved.</span>
      <span>Built on wITeCanvas — By Truska</span>
    </div>
  </div>
</footer>
