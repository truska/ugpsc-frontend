<?php
/**
 * Left column: heading/subheading/text from content.
 * Right column: fact cards sourced from the facts table by content.id.
 */

$facts = cms_load_facts_for_content((int) ($contentItem['id'] ?? 0));
$sectionLabel = trim((string) ($contentItem['name'] ?? ''));
?>
<!-- layout=1col-text-1col-facts.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<section class="facts-split-section cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
  <div class="container">
    <div class="facts-split-panel">
      <div class="row g-4 align-items-center">
        <div class="col-lg-6">
          <div class="facts-copy">
            <?php if ($sectionLabel !== ''): ?>
              <span class="section-tag"><?php echo cms_h($sectionLabel); ?></span>
            <?php endif; ?>
            <?php if ($contentHeading !== '' && $contentShowHeading === 'Yes'): ?>
              <<?php echo cms_h($contentHeadingTag); ?> class="section-title"><?php echo cms_h($contentHeading); ?></<?php echo cms_h($contentHeadingTag); ?>>
            <?php endif; ?>
            <?php if ($contentText !== ''): ?>
              <div class="section-copy content-body"><?php echo $contentText; ?></div>
            <?php endif; ?>
            <?php if ($contentText2 !== ''): ?>
              <div class="section-copy content-body mt-3"><?php echo $contentText2; ?></div>
            <?php endif; ?>
            <div class="mt-3">
              <a href="#" class="btn btn-section-link">Read More...</a>
            </div>
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
