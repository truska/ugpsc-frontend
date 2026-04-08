<?php
/**
 * News listing grid (uses news_story table).
 */

$newsLimit = isset($contentQtyRecords) && is_numeric($contentQtyRecords) ? (int) $contentQtyRecords : 0;
if ($newsLimit <= 0) {
  $newsLimit = 9;
}

$pageScopeId = isset($contentPageId) && is_numeric($contentPageId) ? (int) $contentPageId : null;
$newsItems = function_exists('cms_load_news_items')
  ? cms_load_news_items(['limit' => $newsLimit, 'page_id' => $pageScopeId])
  : [];
$storyBaseSlug = function_exists('cms_news_story_base_slug') ? cms_news_story_base_slug() : 'newsstory';

$newsDebugEnabled = (isset($_GET['newsdebug']) && $_GET['newsdebug'] === '1');
$newsDebug = $newsDebugEnabled && isset($GLOBALS['cms_news_debug']) ? $GLOBALS['cms_news_debug'] : null;

$sectionLabel = trim((string) ($contentItem['name'] ?? ''));
$introCopy = $contentText ?? '';

$gridCols = isset($contentAcrossGrid) && is_numeric($contentAcrossGrid) ? (int) $contentAcrossGrid : 3;
if ($gridCols < 1) {
  $gridCols = 3;
} elseif ($gridCols > 4) {
  $gridCols = 4;
}

$colClass = 'col-12';
if ($gridCols === 2) {
  $colClass = 'col-12 col-md-6';
} elseif ($gridCols === 3) {
  $colClass = 'col-12 col-md-6 col-lg-4';
} elseif ($gridCols === 4) {
  $colClass = 'col-12 col-md-6 col-lg-3';
}
?>
<!-- layout=news-listing.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<section class="news-listing-section cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
  <div class="container">
    <?php if (($contentHeading !== '' && $contentShowHeading === 'Yes') || $contentSubheading !== '' || $sectionLabel !== ''): ?>
      <div class="section-heading text-center mb-4">
        <?php if ($sectionLabel !== ''): ?>
          <span class="section-tag"><?php echo cms_h($sectionLabel); ?></span>
        <?php endif; ?>
        <?php if ($contentHeading !== '' && $contentShowHeading === 'Yes'): ?>
          <<?php echo cms_h($contentHeadingTag); ?> class="mb-2"><?php echo cms_h($contentHeading); ?></<?php echo cms_h($contentHeadingTag); ?>>
        <?php endif; ?>
        <?php if ($contentSubheading !== ''): ?>
          <p class="lead text-secondary mb-0"><?php echo cms_h($contentSubheading); ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($introCopy !== ''): ?>
      <div class="row justify-content-center mb-4">
        <div class="col-lg-9">
          <div class="content-body text-center"><?php echo $introCopy; ?></div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($newsItems)): ?>
      <div class="row g-4 news-card-grid">
        <?php foreach ($newsItems as $story): ?>
          <?php
          $storyTitle = trim((string) ($story['headline'] ?? $story['name'] ?? ''));
          $storySlug = trim((string) ($story['slug'] ?? ''));
          $storyId = isset($story['id']) ? (int) $story['id'] : 0;
          $baseSlug = trim((string) $storyBaseSlug, '/');
          $storyUrl = $storyId > 0
            ? cms_base_url('/' . $baseSlug . '/' . $storyId . ($storySlug !== '' ? '/' . rawurlencode($storySlug) : ''))
            : '#';
          $storyDateValue = $story['published_on'] ?? $story['publish_on'] ?? $story['date'] ?? $story['created'] ?? '';
          $storyDate = '';
          if ($storyDateValue !== '') {
            try {
              $dateObj = new DateTime($storyDateValue);
              $storyDate = $dateObj->format('d/m/y');
            } catch (Exception $e) {
              $storyDate = '';
            }
          }
          $image = function_exists('cms_news_primary_image') ? cms_news_primary_image($story, ['use_gallery' => true]) : [];
          $excerpt = function_exists('cms_news_story_excerpt') ? cms_news_story_excerpt($story, 160) : '';
          ?>
          <div class="<?php echo cms_h($colClass); ?>">
            <article class="card news-card h-100 border-0 shadow-sm">
              <div class="news-card-media position-relative">
                <div class="ratio ratio-4x3 news-card-image-shell">
                  <a href="<?php echo cms_h($storyUrl); ?>" class="news-card-image-link">
                    <?php if (!empty($image['display'])): ?>
                      <img
                        src="<?php echo cms_h($image['display']); ?>"
                        <?php if (!empty($image['srcset'])): ?>
                          srcset="<?php echo cms_h($image['srcset']); ?>"
                          sizes="(max-width: 768px) 100vw, 33vw"
                        <?php endif; ?>
                        alt="<?php echo cms_h($image['alt'] ?? $storyTitle); ?>"
                        class="news-card-img"
                      >
                    <?php else: ?>
                      <div class="news-card-placeholder d-flex align-items-center justify-content-center">
                        <span>No image</span>
                      </div>
                    <?php endif; ?>
                  </a>
                </div>
              </div>
              <div class="card-body">
                <h3 class="h5 card-title mb-2">
                  <a href="<?php echo cms_h($storyUrl); ?>"><?php echo cms_h($storyTitle); ?></a>
                </h3>
                <?php if ($excerpt !== ''): ?>
                  <p class="card-text text-secondary mb-0"><?php echo cms_h($excerpt); ?></p>
                <?php endif; ?>
              </div>
              <div class="card-footer bg-transparent border-0">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                  <a class="btn btn-outline-primary btn-sm" href="<?php echo cms_h($storyUrl); ?>">Read story</a>
                  <?php if ($storyDate !== ''): ?>
                    <span class="text-secondary small fw-semibold"><?php echo cms_h($storyDate); ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="news-empty text-center">
        <p class="mb-2 fw-bold">No news stories published yet.</p>
        <p class="text-secondary mb-0">Add items to the news_story table and attach gallery images for richer cards.</p>
      </div>
    <?php endif; ?>

    <?php if ($newsDebug): ?>
      <div class="alert alert-warning mt-3" style="font-size:0.9rem;">
        <div><strong>News debug:</strong></div>
        <div>SQL: <code><?php echo cms_h($newsDebug['sql'] ?? ''); ?></code></div>
        <div>Params: <code><?php echo cms_h(json_encode($newsDebug['params'] ?? [])); ?></code></div>
        <div>Rows: <?php echo (int) ($newsDebug['rows'] ?? 0); ?></div>
        <?php if (!empty($newsDebug['error'])): ?>
          <div>Error: <?php echo cms_h($newsDebug['error']); ?></div>
        <?php endif; ?>
        <div>Server now: <?php echo cms_h(date('Y-m-d H:i:s')); ?></div>
      </div>
    <?php endif; ?>
  </div>
</section>
