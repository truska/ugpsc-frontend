<?php
/**
 * Sam Glass Trophy layout.
 * - Left: trophy winner images (from sam_glass_trophy.image).
 * - Middle: CMS content (heading/text).
 * - Right: compact winners table (year/name).
 */

$trophyRows = [];
if (function_exists('cms_content_table_exists') && cms_content_table_exists('sam_glass_trophy') && $pdo instanceof PDO) {
  try {
    $stmt = $pdo->query("SELECT * FROM sam_glass_trophy WHERE archived = 0 AND (showonweb = 'Yes' OR showonweb = 1) ORDER BY year DESC, sort ASC, id DESC");
    $trophyRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (PDOException $e) {
    $trophyRows = [];
  }
}

$imageItems = [];
foreach ($trophyRows as $row) {
  $filename = trim((string) ($row['image'] ?? ''));
  if ($filename === '') {
    continue;
  }
  $display = cms_content_pick_image_url('images', 'content', $filename, ['lg', 'md', 'sm', 'xs', '']);
  $zoom = cms_content_pick_image_url('images', 'content', $filename, ['xl', 'master', 'lg', 'md', '']);
  $thumb = cms_content_pick_image_url('images', 'content', $filename, ['sm', 'xs', 'md', '']);
  if ($display === '') {
    continue;
  }
  $srcset = cms_content_image_srcset('images', 'content', $filename);
  $alt = trim((string) ($row['name'] ?? ''));
  $year = trim((string) ($row['year'] ?? ''));
  $imageItems[] = [
    'display' => $display,
    'zoom' => $zoom !== '' ? $zoom : $display,
    'thumb' => $thumb !== '' ? $thumb : $display,
    'srcset' => $srcset,
    'alt' => $alt !== '' ? $alt : 'Sam Glass Trophy winner',
    'caption' => ($year !== '' ? $year . ' - ' : '') . $alt,
    'year' => $year,
  ];
}
usort($imageItems, static function ($a, $b) {
  $ay = (int) ($a['year'] ?? 0);
  $by = (int) ($b['year'] ?? 0);
  if ($ay === $by) {
    return 0;
  }
  return $ay > $by ? -1 : 1;
});
?>
<!-- layout=3col-sam-glass.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<section class="cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
  <div class="container">
    <?php if (function_exists('cms_magictoolbox_assets_html')): ?>
      <?php echo cms_magictoolbox_assets_html('magiczoomplus'); ?>
    <?php endif; ?>
    <div class="row g-4 align-items-start">
      <div class="col-12 col-lg-5">
        <?php if (!empty($imageItems)): ?>
          <?php
          $viewerId = 'samglass-viewer-' . (int) ($contentItem['id'] ?? 0);
          $main = $imageItems[0];
          $mainAlt = $main['alt'] ?? '';
          $mainCaption = $main['caption'] ?? $mainAlt;
          $mainSrcset = $main['srcset'] !== '' ? ' srcset="' . cms_h($main['srcset']) . '" sizes="(max-width: 992px) 100vw, 50vw"' : '';
          ?>
          <div class="trophy-image-viewer mb-3">
            <a id="<?php echo cms_h($viewerId); ?>" class="MagicZoomPlus" href="<?php echo cms_h($main['zoom']); ?>" title="<?php echo cms_h($mainCaption); ?>">
              <img
                src="<?php echo cms_h($main['display']); ?>"
                alt="<?php echo cms_h($mainAlt); ?>"
                class="img-fluid rounded"
                <?php echo $mainSrcset; ?>
              >
            </a>
          </div>
          <?php if (count($imageItems) > 1): ?>
            <div class="row g-2 trophy-thumb-strip">
              <?php foreach ($imageItems as $item): ?>
                <?php
                $thumbAlt = $item['alt'] ?? $mainAlt;
                $thumbCaption = $item['caption'] ?? $thumbAlt;
                ?>
                <div class="col-4">
                  <a
                    data-zoom-id="<?php echo cms_h($viewerId); ?>"
                    href="<?php echo cms_h($item['zoom']); ?>"
                    data-image="<?php echo cms_h($item['display']); ?>"
                    class="trophy-thumb w-100 d-block"
                    title="<?php echo cms_h($thumbCaption); ?>"
                  >
                    <img
                      src="<?php echo cms_h($item['thumb']); ?>"
                      alt="<?php echo cms_h($thumbAlt); ?>"
                      class="img-thumbnail w-100"
                    >
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="alert alert-info">Winner images will appear here.</div>
        <?php endif; ?>
      </div>

      <div class="col-12 col-lg-5">
        <?php if ($contentHeading !== '' && $contentShowHeading === 'Yes'): ?>
          <<?php echo cms_h($contentHeadingTag); ?>><?php echo cms_h($contentHeading); ?></<?php echo cms_h($contentHeadingTag); ?>>
        <?php endif; ?>
        <?php if ($contentSubheading !== ''): ?>
          <p class="lead text-secondary"><?php echo cms_h($contentSubheading); ?></p>
        <?php endif; ?>
        <?php if ($contentText !== ''): ?>
          <div class="content-body"><?php echo $contentText; ?></div>
        <?php endif; ?>
        <?php if ($contentText2 !== ''): ?>
          <div class="content-body mt-3"><?php echo $contentText2; ?></div>
        <?php endif; ?>
      </div>

      <div class="col-12 col-lg-2">
        <?php if (!empty($trophyRows)): ?>
          <div class="trophy-table card shadow-sm border-0">
            <div class="card-header bg-white border-0 pb-2">
              <h3 class="h6 mb-0">Winners</h3>
            </div>
            <div class="card-body pt-0">
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <tbody>
                    <?php foreach ($trophyRows as $row): ?>
                      <tr>
                        <td class="fw-bold"><?php echo cms_h($row['year'] ?? ''); ?></td>
                        <td><?php echo cms_h($row['name'] ?? ''); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="alert alert-light border">Winners will be listed here.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
