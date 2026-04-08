<?php
// Webhook endpoint placeholder for SumUp. Validates minimal fields and updates mem_transaction.
require_once __DIR__ . '/includes/member/bootstrap.php';

header('Content-Type: application/json');

if (!mem_ready() || !mem_table_exists('mem_transaction')) {
  http_response_code(503);
  echo json_encode(['error' => 'Membership tables not ready']);
  exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON']);
  exit;
}

// TODO: Verify webhook signature when SumUp signing secret is available.

$providerRef = trim((string) ($payload['provider_reference'] ?? ''));
$status = strtolower(trim((string) ($payload['status'] ?? '')));
$allowedStatuses = ['paid', 'pending', 'failed', 'refunded', 'cancelled'];
if ($providerRef === '' || !in_array($status, $allowedStatuses, true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing or invalid data']);
  exit;
}

$notes = trim((string) ($payload['notes'] ?? ''));
$paidAt = (string) ($payload['paid_at'] ?? '');

global $pdo, $DB_OK;
if (!$DB_OK || !($pdo instanceof PDO)) {
  http_response_code(503);
  echo json_encode(['error' => 'Database unavailable']);
  exit;
}

$sql = 'UPDATE mem_transaction
  SET status = :status,
      paid_at = CASE WHEN :status = "paid" THEN COALESCE(:paid_at_val, paid_at) ELSE paid_at END,
      notes = CASE WHEN :notes <> "" THEN :notes ELSE notes END,
      modified = NOW()
  WHERE provider_reference = :ref
  LIMIT 1';

$paidAtVal = null;
if ($paidAt !== '') {
  $paidAtVal = date('Y-m-d H:i:s', strtotime($paidAt));
}

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':status' => $status,
    ':paid_at_val' => $paidAtVal,
    ':notes' => $notes,
    ':ref' => $providerRef,
  ]);
  http_response_code(200);
  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Update failed']);
}
