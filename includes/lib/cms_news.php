<?php
/**
 * News/story helpers for front-end layouts.
 * - Uses a dedicated news table (default: news_story).
 * - Supports publish/expire filtering and gallery lookup.
 */

function cms_news_table_name(): string {
  return 'news_story';
}

function cms_news_table_exists(): bool {
  // Lightweight check; if the query layer fails, consumers will catch it.
  return cms_content_table_exists(cms_news_table_name());
}

function cms_news_table_columns(): array {
  static $cols = null;
  if ($cols !== null) {
    return $cols;
  }

  $cols = cms_content_table_columns(cms_news_table_name());
  return $cols;
}

function cms_news_column_exists(string $column): bool {
  return in_array(strtolower($column), cms_news_table_columns(), true);
}

function cms_news_primary_date_column(): string {
  $cols = cms_news_table_columns();
  foreach (['published_on', 'publish_on', 'date', 'created'] as $candidate) {
    if (in_array($candidate, $cols, true)) {
      return $candidate;
    }
  }
  return '';
}

function cms_news_expiry_column(): string {
  $cols = cms_news_table_columns();
  foreach (['expires_on', 'expire_on', 'expiry_date', 'expiry'] as $candidate) {
    if (in_array($candidate, $cols, true)) {
      return $candidate;
    }
  }
  return '';
}

function cms_news_story_base_slug(): string {
  $slug = trim((string) cms_pref('prefNewsStoryBaseSlug', 'newsstory'));
  return $slug !== '' ? $slug : 'newsstory';
}

function cms_news_story_url(array $story, ?string $baseSlug = null): string {
  $slug = trim((string) ($story['slug'] ?? $story['urlslug'] ?? ''));
  if ($slug === '' && isset($story['id']) && is_numeric($story['id'])) {
    $slug = (string) (int) $story['id'];
  }

  $base = trim((string) ($baseSlug ?? cms_news_story_base_slug()), '/');
  $path = $base !== '' ? '/' . $base : '';
  $path .= $slug !== '' ? '/' . rawurlencode($slug) : '';

  return cms_base_url($path === '' ? '/' : $path);
}

function cms_news_live_clauses(bool $includeExpired = false, ?int $pageId = null): array {
  $where = [];
  $params = [];
  $nowExpr = 'CURRENT_TIMESTAMP';

  if (cms_news_column_exists('archived')) {
    $where[] = 'archived = 0';
  }
  if (cms_news_column_exists('showonweb')) {
    $where[] = "(showonweb = 'Yes' OR showonweb = 'yes' OR showonweb = 1)";
  }

  $dateColumn = cms_news_primary_date_column();
  if ($dateColumn !== '') {
    $where[] = "({$dateColumn} IS NULL OR {$dateColumn} = '0000-00-00 00:00:00' OR {$dateColumn} <= {$nowExpr})";
  }

  $expiryColumn = cms_news_expiry_column();
  if (!$includeExpired && $expiryColumn !== '') {
    $where[] = "({$expiryColumn} IS NULL OR {$expiryColumn} = '0000-00-00 00:00:00' OR {$expiryColumn} >= {$nowExpr})";
  }

  if (empty($where)) {
    $where[] = '1=1';
  }

  return [$where, $params, $dateColumn];
}

function cms_load_news_items(array $options = []): array {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return [];
  }

  $limit = isset($options['limit']) ? (int) $options['limit'] : 0;
  if ($limit <= 0) {
    $limit = 12;
  }
  $includeExpired = !empty($options['include_expired']);

  [$where, $params, $dateColumn] = cms_news_live_clauses($includeExpired, null);

  $orderParts = [];
  if (cms_news_column_exists('sort')) {
    $orderParts[] = 'sort ASC';
  }
  if ($dateColumn !== '') {
    $orderParts[] = $dateColumn . ' DESC';
  }
  if ($dateColumn !== 'created' && cms_news_column_exists('created')) {
    $orderParts[] = 'created DESC';
  }
  $orderParts[] = 'id DESC';

  $sql = 'SELECT * FROM ' . cms_news_table_name()
    . ' WHERE ' . implode(' AND ', $where)
    . ' ORDER BY ' . implode(', ', $orderParts)
    . ' LIMIT ' . $limit;

  $GLOBALS['cms_news_debug'] = [
    'sql' => $sql,
    'params' => $params,
  ];

  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $GLOBALS['cms_news_debug']['rows'] = count($rows);
    return $rows;
  } catch (PDOException $e) {
    $GLOBALS['cms_news_debug']['error'] = $e->getMessage();
    return [];
  }
}

function cms_load_news_story($idOrSlug, array $options = []): ?array {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return null;
  }

  $includeExpired = !empty($options['include_expired']);

  [$where, $params] = cms_news_live_clauses($includeExpired, null);

  if (is_numeric($idOrSlug)) {
    $where[] = 'id = :news_id';
    $params[':news_id'] = (int) $idOrSlug;
  } else {
    $where[] = 'slug = :slug';
    $params[':slug'] = (string) $idOrSlug;
  }

  $sql = 'SELECT * FROM ' . cms_news_table_name()
    . ' WHERE ' . implode(' AND ', $where)
    . ' LIMIT 1';

  $GLOBALS['cms_news_debug_story'] = [
    'sql' => $sql,
    'params' => $params,
  ];

  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $GLOBALS['cms_news_debug_story']['rows'] = $row ? 1 : 0;
    return $row ?: null;
  } catch (PDOException $e) {
    $GLOBALS['cms_news_debug_story']['error'] = $e->getMessage();
    return null;
  }
}

function cms_news_story_gallery(array $story, array $options = []): array {
  $recordId = isset($story['id']) && is_numeric($story['id']) ? (int) $story['id'] : 0;
  if ($recordId <= 0) {
    return [];
  }

  $context = cms_form_context_for_table(cms_news_table_name());
  $heading = trim((string) ($story['headline'] ?? $story['name'] ?? ''));

  return cms_content_gallery_images(
    ['id' => $recordId, 'heading' => $heading],
    [
      'form_id' => $context['form_id'],
      'form_name' => $context['form_name'],
    ] + $options
  );
}

function cms_news_primary_image(array $story, array $options = []): array {
  $preferredSizes = $options['preferred_sizes'] ?? ['lg', 'md', 'sm', 'xs', ''];
  $preferGallery = !array_key_exists('use_gallery', $options) || $options['use_gallery'] !== false;
  $mediatype = 'images';
  $alt = trim((string) ($story['headline'] ?? $story['name'] ?? ''));

  if ($preferGallery) {
    $gallery = cms_news_story_gallery($story);
    if (!empty($gallery)) {
      $first = $gallery[0];
      return [
        'display' => $first['display'] ?? '',
        'srcset' => $first['srcset'] ?? '',
        'alt' => $first['alt'] ?? $alt,
        'folder' => $first['folder'] ?? 'content',
        'mediatype' => $first['mediatype'] ?? 'images',
        'filename' => $first['filename'] ?? '',
        'from' => 'gallery',
      ];
    }
  }

  $candidates = [
    ['field' => 'card_image', 'folder' => $story['card_folder'] ?? $story['cardimagefolder'] ?? 'content'],
    ['field' => 'banner_image', 'folder' => $story['banner_folder'] ?? $story['bannerimagefolder'] ?? 'content'],
    ['field' => 'image', 'folder' => $story['image_folder'] ?? $story['folder'] ?? 'content'],
  ];

  foreach ($candidates as $candidate) {
    $filename = ltrim((string) ($story[$candidate['field']] ?? ''), '/');
    if ($filename === '') {
      continue;
    }
    $folder = trim((string) $candidate['folder'], '/');
    if ($folder === '') {
      $folder = 'content';
    }

    $display = cms_content_pick_image_url($mediatype, $folder, $filename, $preferredSizes);
    if ($display !== '') {
      return [
        'display' => $display,
        'srcset' => cms_content_image_srcset($mediatype, $folder, $filename),
        'alt' => $alt,
        'folder' => $folder,
        'mediatype' => $mediatype,
        'filename' => $filename,
        'from' => $candidate['field'],
      ];
    }
  }

  $fallback = cms_content_no_image_url($mediatype, 'content', 'md');
  if ($fallback !== '') {
    return [
      'display' => $fallback,
      'srcset' => '',
      'alt' => $alt,
      'folder' => 'content',
      'mediatype' => $mediatype,
      'filename' => 'no-image.png',
      'from' => 'placeholder',
    ];
  }

  return [];
}

function cms_news_story_excerpt(array $story, int $length = 180): string {
  $fields = ['summary', 'excerpt', 'intro', 'subheading', 'subheading1', 'text', 'body'];
  foreach ($fields as $field) {
    $value = trim((string) ($story[$field] ?? ''));
    if ($value !== '') {
      $value = strip_tags($value);
      if (mb_strlen($value) > $length) {
        $value = rtrim(mb_substr($value, 0, $length - 1)) . '…';
      }
      return $value;
    }
  }
  return '';
}
