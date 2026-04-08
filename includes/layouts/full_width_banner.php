<?php
/**
 * Full browser-width banner carousel.
 * Uses all attached images for the content block as slides.
 */

$bannerItems = function_exists('cms_content_images')
  ? cms_content_images($contentItem, [
    'form_id' => $contentSourceFormId ?? null,
    'form_name' => $contentSourceFormName ?? null,
  ])
  : [];
$carouselId = 'contentBanner' . (int) ($contentItem['id'] ?? 0);
?>
<!-- layout=full_width_banner.php layout_url=<?php echo cms_h((string) ($contentItem['layout_url'] ?? '')); ?> content_id=<?php echo cms_h((string) ($contentItem['id'] ?? '')); ?> -->

<section class="full-width-banner hero-shell cms-content-block <?php echo cms_h($contentPaddingClass); ?>">
  <?php if (!empty($bannerItems)): ?>
    <div
      id="<?php echo cms_h($carouselId); ?>"
      class="carousel slide carousel-fade"
      data-bs-ride="carousel"
      data-bs-interval="4300"
    >
      <?php if (count($bannerItems) > 1): ?>
        <div class="carousel-indicators">
          <?php foreach ($bannerItems as $index => $bannerImage): ?>
            <button
              type="button"
              data-bs-target="#<?php echo cms_h($carouselId); ?>"
              data-bs-slide-to="<?php echo (int) $index; ?>"
              class="<?php echo $index === 0 ? 'active' : ''; ?>"
              <?php if ($index === 0): ?>
                aria-current="true"
              <?php endif; ?>
              aria-label="Slide <?php echo (int) ($index + 1); ?>"
            ></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="carousel-inner">
        <?php foreach ($bannerItems as $index => $bannerImage): ?>
          <?php
          $bannerSrc = (string) ($bannerImage['display'] ?? '');
          $bannerAlt = trim((string) ($bannerImage['alt'] ?? $contentHeading ?? ''));
          $bannerSrcset = (string) ($bannerImage['srcset'] ?? '');
          if ($bannerSrc === '') {
            continue;
          }
          ?>
          <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
            <div
              class="hero-slide"
              style="background-image: url('<?php echo cms_h($bannerSrc); ?>');"
              role="img"
              aria-label="<?php echo cms_h($bannerAlt); ?>"
            ></div>
            <?php if ($bannerSrcset !== ''): ?>
              <img
                src="<?php echo cms_h($bannerSrc); ?>"
                srcset="<?php echo cms_h($bannerSrcset); ?>"
                sizes="100vw"
                alt=""
                class="visually-hidden"
              >
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (count($bannerItems) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo cms_h($carouselId); ?>" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#<?php echo cms_h($carouselId); ?>" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="full-width-banner-placeholder">No Image</div>
  <?php endif; ?>
</section>
