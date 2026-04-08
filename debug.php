<?php
// Simple site debug panel (add tests as needed).
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../private/dbcon.php';

$tests = [];

// Basic PHP runtime info.
$tests['Runtime'] = function () {
  return [
    'php_sapi_name' => php_sapi_name(),
    'get_current_user' => get_current_user(),
    'euid' => function_exists('posix_geteuid') ? posix_geteuid() : 'n/a',
    'egid' => function_exists('posix_getegid') ? posix_getegid() : 'n/a',
    'open_basedir' => ini_get('open_basedir') ?: 'none',
  ];
};

// Database connectivity (no secrets).
$tests['Database'] = function () {
  global $DB_HOST, $DB_NAME, $DB_USER, $DB_OK, $DB_ERROR, $pdo;
  $result = [
    'host' => $DB_HOST ?? '',
    'database' => $DB_NAME ?? '',
    'user' => $DB_USER ?? '',
    'db_ok' => $DB_OK ? 'true' : 'false',
  ];
  if (!$DB_OK) {
    $result['db_error'] = $DB_ERROR ?? 'Unknown error';
    return $result;
  }
  try {
    $stmt = $pdo->query('SELECT 1 AS ok');
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    $result['query'] = $row['ok'] ?? 'no result';
  } catch (PDOException $e) {
    $result['query_error'] = $e->getMessage();
  }
  return $result;
};

// Filesystem permissions for uploads.
$tests['Filestore Permissions'] = function () {
  $paths = [
    '/var/www/clients/client2/web5/web/filestore',
    '/var/www/clients/client2/web5/web/filestore/images',
    '/var/www/clients/client2/web5/web/filestore/images/content',
    '/var/www/clients/client2/web5/web/filestore/images/content/original',
  ];
  $out = [];
  foreach ($paths as $path) {
    $out[$path] = [
      'exists' => file_exists($path) ? 'yes' : 'no',
      'is_dir' => is_dir($path) ? 'yes' : 'no',
      'writable' => is_writable($path) ? 'yes' : 'no',
    ];
  }
  return $out;
};

function render_value($value): void {
  if (is_array($value)) {
    echo "<ul>\n";
    foreach ($value as $key => $val) {
      echo "<li><strong>" . htmlspecialchars((string) $key) . ":</strong> ";
      if (is_array($val)) {
        echo "</li>\n";
        render_value($val);
      } else {
        echo htmlspecialchars((string) $val) . "</li>\n";
      }
    }
    echo "</ul>\n";
    return;
  }
  echo htmlspecialchars((string) $value);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Site Debug Panel</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; color: #111; }
    h1 { margin-bottom: 8px; }
    .note { color: #555; margin-bottom: 24px; }
    .panel { border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
    .panel h2 { margin: 0 0 12px; font-size: 18px; }
    ul { margin: 8px 0 0 18px; }
    li { margin: 4px 0; }
  </style>
</head>
<body>
  <h1>Site Debug Panel</h1>
  <div class="note">Temporary diagnostics. Do not leave publicly accessible in production.</div>
  <?php foreach ($tests as $name => $testFn): ?>
    <div class="panel">
      <h2><?php echo htmlspecialchars($name); ?></h2>
      <?php
        $data = $testFn();
        render_value($data);
      ?>
    </div>
  <?php endforeach; ?>
</body>
</html>
