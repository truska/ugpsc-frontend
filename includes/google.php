<?php
/**
 * Analytics / tracking includes.
 * Outputs GA only when enabled in preferences.
 */
$gaOn = strtolower((string) cms_pref('prefGoogleAnalyticsOn', 'No', 'web'));
$gaCode = trim((string) cms_pref('prefGoogleAnalyticsCode', '', 'web'));

if ($gaOn === 'yes' && $gaCode !== ''): ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo cms_h($gaCode); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?php echo cms_h($gaCode); ?>');
</script>
<?php endif; ?>
