<?php
// Public gallery page with lightbox.
global $pdo, $DB_OK;
$baseURL = cms_base_url();
$images = [];

if ($DB_OK && ($pdo instanceof PDO) && function_exists('cms_table_exists_local') && cms_table_exists_local('gallery_image')) {
  try {
    $stmt = $pdo->query(
      'SELECT id, title, summary, credit, taken_at, image, slug
       FROM gallery_image
       WHERE showonweb = "Yes" AND archived = 0
       ORDER BY is_featured DESC, sort ASC, id DESC'
    );
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (PDOException $e) {
    $images = [];
  }
}

$gridBase = $baseURL . '/filestore/images/gallery/lg/';
$zoomBase = $baseURL . '/filestore/images/gallery/lg/';
?>
<style>
  .gallery-page {
    padding: 2rem 0 3rem;
  }
  .gallery-grid {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }
  .gallery-card {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    background: #0f3326;
    min-height: 160px;
    cursor: pointer;
  }
  .gallery-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.3s ease;
  }
  .gallery-card:hover img {
    transform: scale(1.03);
  }
  .gallery-card .meta {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 8px 10px;
    background: linear-gradient(180deg, transparent, rgba(0,0,0,0.6));
    color: #f2f7f4;
  }
  .gallery-card .meta .title {
    font-size: 0.95rem;
    margin: 0;
  }
  .gallery-card .meta .credit {
    font-size: 0.78rem;
    color: #c9d5ce;
    margin: 0;
  }
  .gallery-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 1rem;
  }
  .gallery-lightbox.show {
    display: flex;
  }
  .gallery-lightbox .lightbox-inner {
    max-width: 1100px;
    width: 100%;
    color: #f2f7f4;
  }
  .gallery-lightbox img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 10px;
  }
  .gallery-lightbox .lightbox-meta {
    margin-top: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .gallery-lightbox .controls {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    pointer-events: none;
  }
  .gallery-lightbox .control-btn {
    pointer-events: auto;
    background: rgba(255,255,255,0.1);
    color: #f2f7f4;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: background 0.2s ease;
  }
  .gallery-lightbox .control-btn:hover {
    background: rgba(255,255,255,0.2);
  }
  .gallery-lightbox .close-btn {
    position: absolute;
    top: 14px;
    right: 14px;
    background: rgba(0,0,0,0.6);
    border: 1px solid rgba(255,255,255,0.2);
  }
</style>

<section class="gallery-page">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <p class="text-uppercase small mb-1" style="letter-spacing:0.08em;color:#51656d;">UGPSC Gallery</p>
        <h1 class="h3 display-font mb-1">Gallery</h1>
        <p class="text-secondary mb-0">Images from recent events and the Ulster Grand Prix community.</p>
      </div>
    </div>

    <?php if (!empty($images)): ?>
      <div class="gallery-grid">
        <?php foreach ($images as $idx => $img): ?>
          <?php
            $filename = trim((string) ($img['image'] ?? ''));
            if ($filename === '') {
              continue;
            }
            $display = $gridBase . ltrim($filename, '/');
            $zoom = $zoomBase . ltrim($filename, '/');
            $title = trim((string) ($img['title'] ?? ''));
            $credit = trim((string) ($img['credit'] ?? ''));
            $summary = trim((string) ($img['summary'] ?? ''));
            $takenAt = trim((string) ($img['taken_at'] ?? ''));
          ?>
          <div class="gallery-card"
               data-idx="<?php echo (int) $idx; ?>"
               data-zoom="<?php echo cms_h($zoom); ?>"
               data-title="<?php echo cms_h($title); ?>"
               data-credit="<?php echo cms_h($credit); ?>"
               data-summary="<?php echo cms_h($summary); ?>"
               data-date="<?php echo cms_h($takenAt); ?>">
            <img src="<?php echo cms_h($display); ?>" alt="<?php echo cms_h($title); ?>">
            <div class="meta">
              <p class="title"><?php echo cms_h($title !== '' ? $title : ''); ?></p>
              <?php if ($credit !== ''): ?>
                <p class="credit"><?php echo cms_h($credit); ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="alert alert-light border text-secondary" role="alert">
        No gallery images available yet.
      </div>
    <?php endif; ?>
  </div>
</section>

<div class="gallery-lightbox" id="galleryLightbox" aria-hidden="true">
  <div class="lightbox-inner position-relative">
    <button type="button" class="control-btn close-btn" id="lbClose" aria-label="Close">&times;</button>
    <div class="controls">
      <button type="button" class="control-btn" id="lbPrev" aria-label="Previous">&#8249;</button>
      <button type="button" class="control-btn" id="lbNext" aria-label="Next">&#8250;</button>
    </div>
    <img id="lbImage" src="" alt="">
    <div class="lightbox-meta">
      <div class="fw-semibold" id="lbTitle"></div>
      <div class="text-secondary small" id="lbCredit"></div>
      <div class="text-secondary small" id="lbDate"></div>
      <div class="small mt-1" id="lbSummary"></div>
    </div>
  </div>
</div>

<script>
  (function() {
    const cards = Array.from(document.querySelectorAll('.gallery-card'));
    const lightbox = document.getElementById('galleryLightbox');
    if (!cards.length || !lightbox) return;

    const imgEl = document.getElementById('lbImage');
    const titleEl = document.getElementById('lbTitle');
    const creditEl = document.getElementById('lbCredit');
    const dateEl = document.getElementById('lbDate');
    const summaryEl = document.getElementById('lbSummary');
    const closeBtn = document.getElementById('lbClose');
    const prevBtn = document.getElementById('lbPrev');
    const nextBtn = document.getElementById('lbNext');

    function show(idx) {
      const card = cards[idx];
      if (!card) return;
      const zoom = card.getAttribute('data-zoom');
      const title = card.getAttribute('data-title') || '';
      const credit = card.getAttribute('data-credit') || '';
      const summary = card.getAttribute('data-summary') || '';
      const date = card.getAttribute('data-date') || '';

      imgEl.src = zoom;
      imgEl.alt = title;
      titleEl.textContent = title;
      creditEl.textContent = credit ? '© ' + credit : '';
      dateEl.textContent = date ? date : '';
      summaryEl.textContent = summary;

      lightbox.setAttribute('data-idx', idx);
      lightbox.classList.add('show');
      lightbox.setAttribute('aria-hidden', 'false');
    }

    function hide() {
      lightbox.classList.remove('show');
      lightbox.setAttribute('aria-hidden', 'true');
    }

    function next() {
      const idx = parseInt(lightbox.getAttribute('data-idx') || '0', 10);
      const nextIdx = (idx + 1) % cards.length;
      show(nextIdx);
    }

    function prev() {
      const idx = parseInt(lightbox.getAttribute('data-idx') || '0', 10);
      const prevIdx = (idx - 1 + cards.length) % cards.length;
      show(prevIdx);
    }

    cards.forEach((card) => {
      card.addEventListener('click', () => {
        show(parseInt(card.getAttribute('data-idx') || '0', 10));
      });
    });

    closeBtn.addEventListener('click', hide);
    nextBtn.addEventListener('click', next);
    prevBtn.addEventListener('click', prev);
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox) hide();
    });
    document.addEventListener('keydown', (e) => {
      if (!lightbox.classList.contains('show')) return;
      if (e.key === 'Escape') hide();
      if (e.key === 'ArrowRight') next();
      if (e.key === 'ArrowLeft') prev();
    });
  })();
</script>
