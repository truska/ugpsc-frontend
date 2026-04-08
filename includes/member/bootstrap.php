<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../../../private/dbcon.php';
require_once __DIR__ . '/../lib/cms_prefs.php';
require_once __DIR__ . '/../lib/cms_log.php';

function mem_h(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function mem_table_exists(string $table): bool {
  global $pdo, $DB_OK;

  static $cache = [];
  if (isset($cache[$table])) {
    return $cache[$table];
  }

  if (!$DB_OK || !($pdo instanceof PDO)) {
    $cache[$table] = false;
    return false;
  }

  try {
    $stmt = $pdo->prepare('SHOW TABLES LIKE :table');
    $stmt->execute([':table' => $table]);
    $cache[$table] = (bool) $stmt->fetchColumn();
    return $cache[$table];
  } catch (PDOException $e) {
    $cache[$table] = false;
    return false;
  }
}

function mem_ready(): bool {
  return mem_table_exists('mem_member');
}

function mem_base_url(string $path = ''): string {
  return cms_base_url($path);
}

function mem_stripe_config(): array {
  static $config = null;
  if ($config !== null) {
    return $config;
  }

  $config = [
    'publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
    'secret_key' => getenv('STRIPE_SECRET_KEY') ?: '',
    'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
    'mode' => getenv('STRIPE_MODE') ?: 'test',
    'api_version' => getenv('STRIPE_API_VERSION') ?: '',
  ];

  $file = __DIR__ . '/../../../private/stripe.php';
  $fallback = __DIR__ . '/../../../private/stripe-sample.php';
  $configFile = file_exists($file) ? $file : (file_exists($fallback) ? $fallback : null);

  if ($configFile) {
    $fileConfig = require $configFile;
    if (is_array($fileConfig)) {
      foreach ($fileConfig as $key => $value) {
        if (array_key_exists($key, $config) && $value !== '' && $value !== null) {
          $config[$key] = (string) $value;
        }
      }
    }
  }

  $config['mode'] = in_array($config['mode'], ['live', 'test'], true) ? $config['mode'] : 'test';
  $config['api_version'] = (string) $config['api_version'];
  return $config;
}

function mem_csrf_token(): string {
  if (empty($_SESSION['mem_csrf'])) {
    $_SESSION['mem_csrf'] = bin2hex(random_bytes(32));
  }
  return (string) $_SESSION['mem_csrf'];
}

function mem_verify_csrf(string $token): bool {
  $sessionToken = $_SESSION['mem_csrf'] ?? '';
  return is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function mem_log_event(string $eventType, string $message = '', string $sqlText = null, ?int $memberId = null): void {
  global $pdo, $DB_OK;

  if (!mem_table_exists('mem_activity_log') || !$DB_OK || !($pdo instanceof PDO)) {
    return;
  }

  $memberId = $memberId ?? (isset($_SESSION['mem_member']['id']) ? (int) $_SESSION['mem_member']['id'] : null);

  try {
    $stmt = $pdo->prepare(
      'INSERT INTO mem_activity_log
      (member_id, event_type, message, sql_text, ip_address, user_agent)
      VALUES
      (:member_id, :event_type, :message, :sql_text, :ip_address, :user_agent)'
    );
    $stmt->execute([
      ':member_id' => $memberId,
      ':event_type' => $eventType,
      ':message' => $message,
      ':sql_text' => $sqlText,
      ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
      ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
  } catch (PDOException $e) {
    return;
  }
}

function mem_log_change(string $action, string $summary = '', ?string $sqlText = null, ?int $targetMemberId = null, ?int $actorMemberId = null): void {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return;
  }

  $actor = $actorMemberId ?? (isset($_SESSION['mem_member']['id']) ? (int) $_SESSION['mem_member']['id'] : null);
  $ip = $_SERVER['REMOTE_ADDR'] ?? null;
  $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

  // Pack metadata into sql_text for traceability.
  $meta = sprintf(
    'actor_member_id=%s; target_member_id=%s; ip=%s; agent=%s',
    $actor === null ? 'null' : (string) $actor,
    $targetMemberId === null ? 'null' : (string) $targetMemberId,
    $ip ?? 'null',
    $agent ?? 'null'
  );
  $combinedSql = $sqlText ? ($sqlText . ' | ' . $meta) : $meta;

  // Use cms_log_action as the unified audit sink.
  cms_log_action(
    $action,
    'mem_member',
    $targetMemberId,
    $combinedSql,
    'member-admin',
    'web',
    $summary !== '' ? $summary : $action
  );
}

function mem_send_mail(string $to, string $subject, string $htmlBody, string $textBody = ''): bool {
  $headers = [];
  $headers[] = 'MIME-Version: 1.0';
  $headers[] = 'Content-Type: text/html; charset=UTF-8';
  $headers[] = 'From: UGPSC Members <no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>';

  if ($textBody !== '') {
    $htmlBody .= '<hr><pre style="font-family:monospace;white-space:pre-wrap">' . mem_h($textBody) . '</pre>';
  }

  return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
}
