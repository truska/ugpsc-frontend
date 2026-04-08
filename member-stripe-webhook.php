<?php
require_once __DIR__ . '/includes/member/ui.php';
require_once __DIR__ . '/includes/member/stripe.php';

header('Content-Type: application/json');

function mem_stripe_response(int $status, array $data = []): void {
  http_response_code($status);
  echo json_encode($data);
  exit;
}

function mem_stripe_verify_signature(string $payload, string $sigHeader, string $secret, string &$error = null): bool {
  $parts = [];
  foreach (explode(',', $sigHeader) as $item) {
    [$k, $v] = array_pad(explode('=', trim($item), 2), 2, null);
    if ($k !== null && $v !== null) {
      $parts[$k] = $v;
    }
  }

  $timestamp = $parts['t'] ?? null;
  $v1 = $parts['v1'] ?? null;
  if (!$timestamp || !$v1) {
    $error = 'Missing signature fields.';
    return false;
  }

  $tolerance = 300; // 5 minutes
  if (abs(time() - (int) $timestamp) > $tolerance) {
    $error = 'Signature timestamp outside tolerance.';
    return false;
  }

  $signedPayload = $timestamp . '.' . $payload;
  $computed = hash_hmac('sha256', $signedPayload, $secret);
  if (!hash_equals($computed, $v1)) {
    $error = 'Invalid signature.';
    return false;
  }

  return true;
}

function mem_stripe_mark_transaction(string $providerRef, string $status, string $notes = ''): bool {
  global $pdo, $DB_OK;

  if (!mem_table_exists('mem_transaction') || !$DB_OK || !($pdo instanceof PDO) || trim($providerRef) === '') {
    return false;
  }

  $status = in_array($status, ['paid', 'pending', 'failed', 'refunded', 'cancelled'], true) ? $status : 'pending';
  $sql = 'UPDATE mem_transaction
          SET status = :status,
              notes = CASE WHEN :notes <> "" THEN :notes ELSE notes END,
              modified = NOW()
          WHERE provider_reference = :ref
          LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':status' => $status,
    ':notes' => $notes,
    ':ref' => $providerRef,
  ]);
  return $stmt->rowCount() > 0;
}

$cfg = mem_stripe_config();
if ($cfg['webhook_secret'] === '') {
  mem_stripe_response(503, ['error' => 'Webhook secret not configured.']);
}

$raw = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
if ($sigHeader === '') {
  mem_stripe_response(400, ['error' => 'Missing Stripe-Signature header.']);
}

$sigError = null;
if (!mem_stripe_verify_signature($raw, $sigHeader, $cfg['webhook_secret'], $sigError)) {
  mem_stripe_response(400, ['error' => $sigError ?? 'Invalid signature']);
}

$event = json_decode($raw, true);
if (!is_array($event) || empty($event['type']) || empty($event['data']['object'])) {
  mem_stripe_response(400, ['error' => 'Invalid payload']);
}

$type = (string) $event['type'];
$object = $event['data']['object'];
$ok = true;
$note = '';

try {
  if ($type === 'payment_intent.succeeded') {
    $memberId = (int) ($object['metadata']['member_id'] ?? 0);
    $transactionType = ((string) ($object['metadata']['transaction_type'] ?? '') === 'renewal') ? 'renewal' : 'join';
    $providerRef = (string) ($object['id'] ?? '');
    $currency = strtoupper((string) ($object['currency'] ?? 'GBP'));
    $amount = isset($object['amount_received']) ? ((float) $object['amount_received']) / 100 : 0.0;
    $charges = $object['charges']['data'] ?? [];
    $firstCharge = $charges[0] ?? [];
    $paidAt = !empty($firstCharge['created']) ? date('Y-m-d H:i:s', (int) $firstCharge['created']) : null;
    $paymentMethod = 'card';
    if (!empty($firstCharge['payment_method_details']['card']['brand'])) {
      $paymentMethod = (string) $firstCharge['payment_method_details']['card']['brand'] . ' card';
    }
    if (!empty($firstCharge['payment_method_details']['card']['last4'])) {
      $note = 'Last4 ' . $firstCharge['payment_method_details']['card']['last4'];
    }

    if ($memberId > 0) {
      $final = mem_finalize_membership_payment(
        $memberId,
        $transactionType,
        $providerRef,
        $amount,
        $currency,
        $paymentMethod,
        $paidAt,
        $note
      );
      if (!$final) {
        $ok = false;
      }
    } else {
      $ok = mem_stripe_mark_transaction($providerRef, 'paid', $note);
    }
  } elseif ($type === 'payment_intent.payment_failed') {
    $providerRef = (string) ($object['id'] ?? '');
    $errMsg = (string) ($object['last_payment_error']['message'] ?? '');
    $ok = mem_stripe_mark_transaction($providerRef, 'failed', $errMsg);
  } elseif ($type === 'charge.refunded') {
    $providerRef = (string) ($object['payment_intent'] ?? ($object['id'] ?? ''));
    $ok = mem_stripe_mark_transaction($providerRef, 'refunded', 'Charge refunded');
  } else {
    // Ignore unhandled event types.
    mem_stripe_response(200, ['received' => true, 'ignored' => $type]);
  }
} catch (Throwable $e) {
  $ok = false;
}

if ($ok) {
  mem_stripe_response(200, ['received' => true]);
}

mem_stripe_response(500, ['error' => 'Could not process event']);
