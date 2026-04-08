<?php
require_once __DIR__ . '/includes/member/ui.php';
require_once __DIR__ . '/includes/member/stripe.php';

header('Content-Type: application/json');

function mem_stripe_json_response(int $status, array $data): void {
  http_response_code($status);
  echo json_encode($data);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  mem_stripe_json_response(405, ['error' => 'Method not allowed']);
}

if (!mem_stripe_ready()) {
  mem_stripe_json_response(503, ['error' => 'Stripe is not configured yet.']);
}

$action = trim((string) ($_POST['action'] ?? ''));
$flow = trim((string) ($_POST['flow'] ?? ''));
$transactionType = ($_POST['transaction_type'] ?? '') === 'renewal' ? 'renewal' : 'join';
$currency = strtoupper(trim((string) ($_POST['currency'] ?? '')));
$token = trim((string) ($_POST['token'] ?? ''));
$clientMetadata = [];

function mem_stripe_resolve_member(string $flow, string $token, array &$errors): ?array {
  if ($flow === 'join') {
    $pending = $_SESSION['mem_join_payment'] ?? null;
    $createdAt = (int) ($pending['created_at'] ?? 0);
    if (!is_array($pending) || empty($pending['member_id']) || ($createdAt <= 0 || (time() - $createdAt) > 3600)) {
      $errors[] = 'Join session expired.';
      return null;
    }
    $member = mem_load_member((int) $pending['member_id']);
    if (!$member) {
      $errors[] = 'Member not found.';
      return null;
    }
    $member['__flow'] = ($pending['flow'] ?? 'normal') === 'quick' ? 'join_quick' : 'join';
    return $member;
  }

  if ($flow === 'renew_logged_in') {
    if (!mem_is_logged_in()) {
      $errors[] = 'Login required.';
      return null;
    }
    $member = mem_current_member();
    if (!$member) {
      $errors[] = 'Session expired.';
      return null;
    }
    $memberRow = mem_load_member((int) $member['id']);
    if (!$memberRow) {
      $errors[] = 'Member not found.';
      return null;
    }
    $memberRow['__flow'] = 'renew_logged_in';
    return $memberRow;
  }

  if ($flow === 'renew_quick') {
    $linkData = mem_validate_magic_link($token, 'renewal');
    if (!$linkData) {
      $errors[] = 'Renewal link invalid or expired.';
      return null;
    }
    $member = mem_load_member((int) ($linkData['member_id'] ?? 0));
    if (!$member) {
      $errors[] = 'Member not found.';
      return null;
    }
    $member['__flow'] = 'renew_quick';
    $member['__magic_link_id'] = (int) $linkData['id'];
    return $member;
  }

  $errors[] = 'Unknown flow.';
  return null;
}

$errors = [];
$member = mem_stripe_resolve_member($flow, $token, $errors);
if (!$member) {
  mem_stripe_json_response(400, ['error' => implode(' ', $errors)]);
}

$memberId = (int) $member['id'];
$currency = mem_resolve_currency($currency, (string) ($member['country'] ?? ''));
$amount = mem_membership_amount($currency, (string) ($member['country'] ?? ''));
$isQuick = in_array($member['__flow'], ['join_quick', 'renew_quick'], true);
$flowName = $member['__flow'];

if ($action === 'create_intent') {
  $meta = [
    'flow' => $flowName,
    'member_email' => (string) ($member['email'] ?? ''),
    'member_name' => mem_member_full_name($member),
  ];
  $intent = mem_stripe_create_payment_intent($memberId, $transactionType, $currency, $amount, $meta, $error);
  if (!$intent) {
    mem_stripe_json_response(400, ['error' => $error ?: 'Could not create payment.']);
  }

  $providerRef = (string) ($intent['id'] ?? '');
  if ($providerRef !== '') {
    $note = 'Stripe PaymentIntent created for ' . $flowName;
    mem_record_membership_transaction_pending($memberId, $transactionType, $providerRef, $amount, $currency, $note);
  }

  mem_stripe_json_response(200, [
    'client_secret' => (string) ($intent['client_secret'] ?? ''),
    'payment_intent_id' => (string) ($intent['id'] ?? ''),
    'amount' => $amount,
    'currency' => $currency,
    'flow' => $flowName,
    'member' => [
      'name' => mem_member_full_name($member),
      'email' => (string) ($member['email'] ?? ''),
    ],
  ]);
}

if ($action === 'finalize') {
  $intentId = trim((string) ($_POST['payment_intent_id'] ?? ''));
  if ($intentId === '') {
    mem_stripe_json_response(400, ['error' => 'Payment Intent id missing.']);
  }

  $intent = mem_stripe_retrieve_payment_intent($intentId, $error);
  if (!$intent) {
    mem_stripe_json_response(400, ['error' => $error ?: 'Could not retrieve payment.']);
  }

  $status = (string) ($intent['status'] ?? '');
  if ($status !== 'succeeded') {
    mem_stripe_json_response(400, ['error' => 'Payment not completed yet.', 'status' => $status]);
  }

  $charges = $intent['charges']['data'] ?? [];
  $firstCharge = $charges[0] ?? [];
  $paidAt = null;
  if (!empty($firstCharge['created'])) {
    $paidAt = date('Y-m-d H:i:s', (int) $firstCharge['created']);
  }

  $paymentMethod = 'card';
  if (!empty($firstCharge['payment_method_details']['card']['brand'])) {
    $paymentMethod = (string) $firstCharge['payment_method_details']['card']['brand'] . ' card';
  }

  $notes = '';
  if (!empty($firstCharge['payment_method_details']['card']['last4'])) {
    $notes = 'Last4 ' . $firstCharge['payment_method_details']['card']['last4'];
  }

  $amountReceived = isset($intent['amount_received']) ? ((float) $intent['amount_received']) / 100 : $amount;
  $final = mem_finalize_membership_payment(
    $memberId,
    $transactionType,
    $intentId,
    $amountReceived > 0 ? $amountReceived : $amount,
    strtoupper($currency),
    $paymentMethod,
    $paidAt,
    $notes
  );

  if (!$final) {
    mem_stripe_json_response(500, ['error' => 'Could not record payment locally.']);
  }

  if ($flowName === 'join') {
    mem_login_by_member_id($memberId);
    unset($_SESSION['mem_join_payment']);
  }
  if ($flowName === 'renew_quick' && isset($member['__magic_link_id'])) {
    mem_mark_magic_link_used((int) $member['__magic_link_id']);
    mem_log_event('quick_renew_link_used', 'Quick renewal link consumed', null, $memberId);
  }

  $_SESSION['mem_last_payment_ack'] = [
    'flow' => $flowName,
    'member_id' => $memberId,
    'member_email' => (string) ($member['email'] ?? ''),
    'membership_number' => (int) ($member['membership_number'] ?? 0),
    'member_name' => mem_member_full_name($member),
    'transaction_id' => (int) $final['transaction_id'],
    'provider_reference' => (string) $final['provider_reference'],
    'amount' => (float) $final['amount'],
    'currency' => (string) $final['currency'],
    'payment_provider' => (string) $final['payment_provider'],
    'payment_method' => (string) $final['payment_method'],
  ];

  mem_stripe_json_response(200, [
    'ok' => true,
    'redirect' => mem_base_url('/member-payment-ack.php'),
  ]);
}

mem_stripe_json_response(400, ['error' => 'Unsupported action']);
