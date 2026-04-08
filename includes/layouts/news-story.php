<?php
/**
 * Single news story layout (news_story table).
 */

$storyId = 0;
$storySlug = '';
$storyDebugEnabled = (isset($_GET['newsdebug']) && $_GET['newsdebug'] === '1');
$newsListUrl = cms_base_url('/news');
if (!empty($pageSegments[1])) {
  $storyId = ctype_digit((string) $pageSegments[1]) ? (int) $pageSegments[1] : 0;
  if ($storyId === 0) {
    $storySlug = (string) $pageSegments[1];
  }
}
if ($storySlug === '' && !empty($pageSegments[2])) {
  $storySlug = (string) $pageSegments[2];
}
if ($storySlug === '' && !empty($_GET['slug'])) {
  $storySlug = (string) $_GET['slug'];
}
if ($storyId === 0 && !empty($_GET['id']) && ctype_digit((string) $_GET['id'])) {
  $storyId = (int) $_GET['id'];
}

$story = null;
if (function_exists('cms_load_news_story')) {
  if ($storyId > 0) {
    $story = cms_load_news_story($storyId);
  } elseif ($storySlug !== '') {
    $story = cms_load_news_story($storySlug);
  }
}

$storyDebug = $storyDebugEnabled && isset($GLOBALS['cms_news_debug_story']) ? $GLOBALS['cms_news_debug_story'] : null;

$storyTitle = trim((string) ($story['headline'] ?? $story['name'] ?? ''));
if ($storyTitle === '' && $storySlug === '') {
  $storyTitle = 'News story';
}

if (!empty($storyTitle)) {
  $pageTitle = $storyTitle;
}

$storyDateValue = $story['published_on'] ?? $story['publish_on'] ?? $story['date'] ?? $story['created'] ?? '';
$storyDate = '';
if ($storyDateValue !== '') {
  try {
    $dateObj = new DateTime($storyDateValue);
    $storyDate = $dateObj->format('j M Y');
  } catch (Exception $e) {
    $storyDate = '';
  }
}

$summary = trim((string) ($story['summary'] ?? $story['excerpt'] ?? $story['subheading'] ?? ''));
$subheading = trim((string) ($story['subheading'] ?? ''));
$showHeading = strtolower((string) ($story['showheading'] ?? 'Yes')) !== 'no';
$bodyHtml = cms_apply_shortcodes((string) ($story['body'] ?? $story['text'] ?? ''));
$heroImage = [];
if ($story) {
  $bannerFile = trim((string) ($story['image'] ?? ''));
  $bannerFolder = 'news';
  if ($bannerFile !== '' && function_exists('cms_content_pick_image_url')) {
    $heroDisplay = cms_content_pick_image_url('images', $bannerFolder, $bannerFile, ['lg', 'md', 'sm', 'xs', '']);
    $heroSrcset = cms_content_image_srcset('images', $bannerFolder, $bannerFile);
    if ($heroDisplay !== '') {
      $heroImage = [
        'display' => $heroDisplay,
        'srcset' => $heroSrcset,
        'alt' => $storyTitle,
      ];
    }
  }
}
$galleryImages = $story ? cms_news_story_gallery($story) : [];

$downloadLinks = [];
for ($i = 1; $i <= 3; $i++) {
  $url = trim((string) ($story['link_url_' . $i] ?? ''));
  $label = trim((string) ($story['link_label_' . $i] ?? ''));
  $iconId = $story['link_icon_' . $i] ?? null;
  $iconClass = null;
  if ($iconId !== null && function_exists('cms_icon_class') && $pdo instanceof PDO) {
    $iconClass = cms_icon_class($pdo, $iconId);
  }
  if ($url !== '') {
    $downloadLinks[] = [
      'url' => $url,
      'label' => $label !== '' ? $label : 'Open link ' . $i,
      'icon' => $iconClass,
    ];
  }
}
?>
<!-- layout=news-story.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<?php if ($story): ?>
<section class="news-hero-shell cms-content-block">
  <div class="news-hero <?php echo empty($heroImage['display']) ? 'news-hero-empty' : ''; ?>" <?php if (!empty($heroImage['display'])): ?>style="background-image: url('<?php echo cms_h($heroImage['display']); ?>');"<?php endif; ?>>
    <span class="news-hero-overlay"></span>
    <?php if (!empty($heroImage['srcset'])): ?>
      <img src="<?php echo cms_h($heroImage['display']); ?>" srcset="<?php echo cms_h($heroImage['srcset']); ?>" sizes="100vw" alt="<?php echo cms_h($heroImage['alt'] ?? $storyTitle); ?>" class="visually-hidden">
  <?php endif; ?>
</div>
</section>

  <section class="news-hero-heading py-3">
    <div class="container">
      <div class="d-flex justify-content-end mb-2">
        <a class="btn btn-outline-secondary" href="<?php echo cms_h($newsListUrl); ?>">Back to news</a>
      </div>
      <div class="text-center">
        <?php if ($showHeading): ?>
          <h1 class="news-hero-title mb-2"><?php echo cms_h($storyTitle); ?></h1>
        <?php endif; ?>
        <?php if ($subheading !== ''): ?>
          <p class="lead text-secondary mb-0"><?php echo cms_h($subheading); ?></p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <article class="news-article cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-9">
          <div class="news-article-meta d-flex flex-wrap align-items-center gap-3 mb-3">
            <?php if ($storyDate !== ''): ?>
              <div class="d-flex align-items-center gap-2">
                <span class="news-meta-icon text-secondary"><i class="fa-regular fa-calendar"></i></span>
                <span class="fw-semibold"><?php echo cms_h($storyDate); ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($story['author'])): ?>
              <div class="d-flex align-items-center gap-2 text-secondary">
                <span class="news-meta-icon text-secondary"><i class="fa-regular fa-user"></i></span>
                <span><?php echo cms_h($story['author']); ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div class="news-article-body content-body">
            <?php echo $bodyHtml !== '' ? $bodyHtml : '<p>Story content will be added soon.</p>'; ?>
          </div>

          <?php if ($downloadLinks): ?>
            <div class="news-downloads mt-4 d-flex flex-wrap gap-2">
              <?php foreach ($downloadLinks as $link): ?>
                <a class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2" href="<?php echo cms_h($link['url']); ?>" target="_blank" rel="noopener">
                  <?php if (!empty($link['icon'])): ?>
                    <i class="<?php echo cms_h($link['icon']); ?>"></i>
                  <?php else: ?>
                    <i class="fa-solid fa-link"></i>
                  <?php endif; ?>
                  <span><?php echo cms_h($link['label']); ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($galleryImages)): ?>
            <div class="news-gallery mt-5">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h2 class="h4 mb-0">Gallery</h2>
                <span class="text-secondary small"><?php echo (int) count($galleryImages); ?> image<?php echo count($galleryImages) === 1 ? '' : 's'; ?></span>
              </div>
              <div class="row g-3">
                <?php foreach ($galleryImages as $image): ?>
                  <div class="col-6 col-md-4">
                    <a
                      href="<?php echo cms_h($image['zoom'] ?? $image['display'] ?? ''); ?>"
                      class="news-gallery-tile"
                      target="_blank"
                      rel="noopener"
                    >
                      <span class="news-gallery-zoom"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></span>
                      <img
                        src="<?php echo cms_h($image['display'] ?? ''); ?>"
                        <?php if (!empty($image['srcset'])): ?>
                          srcset="<?php echo cms_h($image['srcset']); ?>"
                          sizes="(max-width: 768px) 50vw, 33vw"
                        <?php endif; ?>
                        alt="<?php echo cms_h($image['alt'] ?? $storyTitle); ?>"
                        class="img-fluid"
                      >
                      <?php if (!empty($image['caption'])): ?>
                        <span class="news-gallery-caption"><?php echo cms_h($image['caption']); ?></span>
                      <?php endif; ?>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </article>
<?php else: ?>
  <section class="news-missing cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
    <div class="container">
      <div class="alert alert-warning">
        <strong>News story not found.</strong> The link may be out of date or the story may have been unpublished.
      </div>
      <?php if ($storyDebug): ?>
        <div class="alert alert-info mt-2" style="font-size:0.9rem;">
          <div><strong>News debug:</strong></div>
          <div>SQL: <code><?php echo cms_h($storyDebug['sql'] ?? ''); ?></code></div>
          <div>Params: <code><?php echo cms_h(json_encode($storyDebug['params'] ?? [])); ?></code></div>
          <div>Rows: <?php echo (int) ($storyDebug['rows'] ?? 0); ?></div>
          <?php if (!empty($storyDebug['error'])): ?>
            <div>Error: <?php echo cms_h($storyDebug['error']); ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
