<?php
// Gallery snapshot block (CMS layout).
global $pdo;
$baseURL = cms_base_url();
$galleryBase = $baseURL . '/filestore/images/gallery/lg/';

$headingText = ($contentShowHeading ?? 'Yes') === 'Yes' ? trim((string) ($contentHeading ?? 'Gallery Snapshot')) : '';
$subheadingText = trim((string) ($contentSubheading ?? 'Scenes from the Ulster Grand Prix community.'));

$images = [];
if ($pdo instanceof PDO) {
  try {
    $stmt = $pdo->query(
      'SELECT image, title
       FROM gallery_image
       WHERE showonweb = "Yes" AND archived = 0
       ORDER BY RAND()
       LIMIT 7'
    );
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Throwable $e) {
    $images = [];
  }
}
?>
<style>
  .gallery-snapshot {
    background: linear-gradient(135deg, #0f3326, #173f2e);
    color: #f2f7f4;
    padding: 2.5rem 0;
    margin-top: 2rem;
  }
  .gallery-snapshot .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 10px;
  }
  .gallery-snapshot .tile {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
    min-height: 120px;
  }
  .gallery-snapshot .tile.large {
    grid-column: span 2;
    min-height: 180px;
  }
  .gallery-snapshot .tile img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
  .gallery-snapshot .tile::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.18), transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.08), transparent 45%);
  }
  @media (min-width: 992px) {
    .gallery-snapshot .grid {
      grid-template-columns: repeat(5, 1fr);
    }
    .gallery-snapshot .tile.large {
      grid-column: span 2;
      grid-row: span 2;
    }
  }
</style>
<?php $imageCount = is_array($images) ? count($images) : 0; ?>
<!-- gallery-snapshot debug: count=<?php echo (int) $imageCount; ?> -->
<?php if (!empty($images)): ?>
  <!-- first filenames: <?php echo cms_h(implode(',', array_slice(array_map(static fn($i) => $i['image'] ?? '', $images), 0, 3))); ?> -->
<?php endif; ?>
  <section class="gallery-snapshot">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <?php if ($headingText !== ''): ?>
            <h2 class="mb-1"><?php echo cms_h($headingText); ?></h2>
          <?php endif; ?>
          <?php if ($subheadingText !== ''): ?>
            <p class="text-secondary mb-0" style="color:#c8d7cf;"><?php echo cms_h($subheadingText); ?></p>
          <?php endif; ?>
        </div>
        <a class="btn btn-outline-light btn-sm" href="<?php echo cms_h($baseURL . '/gallery'); ?>">View full gallery</a>
      </div>
      <div class="grid">
        <?php if ($imageCount > 0): ?>
          <?php foreach ($images as $idx => $img): ?>
            <?php
              $filename = trim((string) ($img['image'] ?? ''));
              if ($filename === '') {
                continue;
              }
              $isLarge = $idx === 0;
              $url = $galleryBase . ltrim($filename, '/');
              $title = trim((string) ($img['title'] ?? ''));
            ?>
            <div class="tile <?php echo $isLarge ? 'large' : ''; ?>">
              <img src="<?php echo cms_h($url); ?>" alt="<?php echo cms_h($title); ?>">
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="tile large d-flex align-items-center justify-content-center text-secondary">No gallery images found.</div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<!-- gallery snapshot count: <?php echo (int) $imageCount; ?> -->

