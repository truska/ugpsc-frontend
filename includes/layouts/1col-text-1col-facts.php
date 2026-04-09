<?php
/**
 * Left column: heading/subheading/text from content.
 * Right column: fact cards sourced from the facts table by content.id.
 */

$facts = cms_load_facts_for_content((int) ($contentItem['id'] ?? 0));
$factLinks = [];
$subheading1 = trim((string) ($contentSubheading1 ?? $contentItem['subheading1'] ?? $contentItem['subheading_1'] ?? ''));
// Prefer pre-normalized links from includes/content.php; fall back to raw fields if missing.
if (!empty($contentLinks) && is_array($contentLinks)) {
  foreach ($contentLinks as $link) {
    $label = trim((string) ($link['label'] ?? ''));
    $url = trim((string) ($link['url'] ?? ''));
    if ($label === '' || $url === '') {
      continue;
    }
    $factLinks[] = [
      'label' => $label,
      'url' => $url,
      'icon' => $link['icon'] ?? null,
      'target' => ($link['target'] ?? 'self') === 'blank' ? 'blank' : 'self',
    ];
  }
} else {
  for ($i = 1; $i <= 3; $i++) {
    $linkLabel = trim((string) ($contentItem['link_label_' . $i] ?? ''));
    $linkUrl = trim((string) ($contentItem['link_url_' . $i] ?? ''));
    if ($linkLabel === '' || $linkUrl === '') {
      continue;
    }

    $iconClass = null;
    $iconId = $contentItem['link_icon_' . $i] ?? null;
    if ($iconId !== null && function_exists('cms_icon_class') && isset($DB_OK, $pdo) && $DB_OK && $pdo instanceof PDO) {
      $iconClass = cms_icon_class($pdo, $iconId);
    }

    $factLinks[] = [
      'label' => $linkLabel,
      'url' => $linkUrl,
      'icon' => $iconClass,
      'target' => ((string) ($contentItem['link_target_' . $i] ?? 'self')) === 'blank' ? 'blank' : 'self',
    ];
  }
}
?>
<!-- layout=1col-text-1col-facts.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<section class="facts-split-section cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
  <div class="container">
    <?php if (($contentHeading !== '' && $contentShowHeading === 'Yes') || $contentSubheading !== ''): ?>
      <div class="facts-split-heading mb-4 text-center">
        <?php if ($contentHeading !== '' && $contentShowHeading === 'Yes'): ?>
          <h1 class="section-title"><?php echo cms_h($contentHeading); ?></h1>
        <?php endif; ?>
        <?php if ($contentSubheading !== ''): ?>
          <h2 class="section-subtitle h4 text-secondary mb-0"><?php echo cms_h($contentSubheading); ?></h2>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="facts-split-panel">
      <div class="row g-4 align-items-center">
        <div class="col-lg-6">
          <div class="facts-copy">
            <?php if ($subheading1 !== ''): ?>
              <h3 class="section-subtitle"><?php echo cms_h($subheading1); ?></h3>
            <?php endif; ?>
            <?php if ($contentText !== ''): ?>
              <div class="section-copy content-body"><?php echo $contentText; ?></div>
            <?php endif; ?>
            <?php if ($contentText2 !== ''): ?>
              <div class="section-copy content-body mt-3"><?php echo $contentText2; ?></div>
            <?php endif; ?>
            <?php if ($factLinks): ?>
              <div class="mt-3 d-flex flex-wrap gap-2">
                <?php foreach ($factLinks as $link): ?>
                  <a
                    href="<?php echo cms_h($link['url']); ?>"
                    class="btn btn-section-link"
                    <?php if (($link['target'] ?? 'self') === 'blank'): ?>
                      target="_blank" rel="noopener"
                    <?php endif; ?>
                  >
                    <?php if (!empty($link['icon'])): ?>
                      <i class="<?php echo cms_h($link['icon']); ?> me-1"></i>
                    <?php endif; ?>
                    <span><?php echo cms_h($link['label']); ?></span>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-6">
          <?php if ($facts): ?>
            <div class="row g-3">
              <?php foreach ($facts as $fact): ?>
                <?php
                $factHeading = trim((string) ($fact['heading'] ?? ''));
                $factSubheading = trim((string) ($fact['subheading'] ?? ''));
                $factText = trim((string) ($fact['text'] ?? ''));
                $factName = trim((string) ($fact['name'] ?? ''));
                ?>
                <div class="col-sm-6">
                  <div class="facts-card">
                    <?php if ($factHeading !== ''): ?>
                      <span class="facts-card-value"><?php echo cms_h($factHeading); ?></span>
                    <?php endif; ?>
                    <?php if ($factSubheading !== ''): ?>
                      <span class="facts-card-label"><?php echo cms_h($factSubheading); ?></span>
                    <?php elseif ($factName !== ''): ?>
                      <span class="facts-card-label"><?php echo cms_h($factName); ?></span>
                    <?php endif; ?>
                    <?php if ($factText !== ''): ?>
                      <p class="facts-card-text"><?php echo nl2br(cms_h($factText)); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="facts-card facts-card-placeholder">
              <span class="facts-card-label">Facts</span>
              <p class="facts-card-text mb-0">No facts found for this content block yet.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
