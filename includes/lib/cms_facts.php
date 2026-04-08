<?php

function cms_facts_table_exists(): bool {
  global $pdo, $DB_OK;

  static $exists = null;
  if ($exists !== null) {
    return $exists;
  }

  if (!$DB_OK || !($pdo instanceof PDO)) {
    $exists = false;
    return $exists;
  }

  try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'facts'");
    $exists = (bool) ($stmt && $stmt->fetchColumn());
  } catch (PDOException $e) {
    $exists = false;
  }

  return $exists;
}

function cms_load_facts_for_content(int $contentId): array {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO) || !cms_facts_table_exists()) {
    return [];
  }

  try {
    if ($contentId > 0) {
      $sql = "SELECT * FROM facts
        WHERE contentid = :contentid
          AND archived = 0
          AND LOWER(COALESCE(showonweb, 'Yes')) = 'yes'
        ORDER BY sort ASC, id ASC";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([':contentid' => $contentId]);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
      if ($rows) {
        return $rows;
      }
    }

    // Setup-friendly fallback: if contentid links are not aligned yet,
    // still show the first visible facts so the layout can be previewed.
    $fallbackSql = "SELECT * FROM facts
      WHERE archived = 0
        AND LOWER(COALESCE(showonweb, 'Yes')) = 'yes'
      ORDER BY sort ASC, id ASC
      LIMIT 4";
    $fallbackStmt = $pdo->query($fallbackSql);
    return $fallbackStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (PDOException $e) {
    return [];
  }
}
