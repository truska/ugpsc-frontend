<?php
require_once __DIR__ . '/auth.php';

function mem_stripe_ready(): bool {
  $cfg = mem_stripe_config();
  return $cfg['publishable_key'] !== '' && $cfg['secret_key'] !== '';
}

function mem_stripe_currency(string $currency): string {
  $value = strtolower(trim($currency));
  return in_array($value, ['gbp', 'eur'], true) ? $value : 'gbp';
}

function mem_stripe_api_request(string $method, string $path, array $params = [], string &$error = null): ?array {
  $cfg = mem_stripe_config();
  if ($cfg['secret_key'] === '') {
    $error = 'Stripe secret key missing.';
    return null;
  }

  $url = 'https://api.stripe.com/v1/' . ltrim($path, '/');
  $ch = curl_init();
  $method = strtoupper($method);

  $options = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . $cfg['secret_key'],
    ],
  ];

  if (!empty($cfg['api_version'])) {
    $options[CURLOPT_HTTPHEADER][] = 'Stripe-Version: ' . $cfg['api_version'];
  }

  $body = http_build_query($params);
  if ($method === 'GET') {
    $url .= (strpos($url, '?') === false ? '?' : '&') . $body;
  } else {
    $options[CURLOPT_POSTFIELDS] = $body;
    if ($method === 'POST') {
      $options[CURLOPT_POST] = true;
    } else {
      $options[CURLOPT_CUSTOMREQUEST] = $method;
    }
  }

  $options[CURLOPT_URL] = $url;
  curl_setopt_array($ch, $options);
  $response = curl_exec($ch);
  if ($response === false) {
    $error = 'Stripe request failed: ' . curl_error($ch);
    curl_close($ch);
    return null;
  }

  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  curl_close($ch);

  $body = substr($response, $headerSize);
  $data = json_decode($body, true);

  if ($status >= 400 || !is_array($data)) {
    $message = is_array($data) ? ($data['error']['message'] ?? 'Stripe error') : 'Stripe error';
    $error = $message;
    return null;
  }

  return $data;
}

function mem_stripe_create_payment_intent(int $memberId, string $transactionType, string $currency, float $amount, array $metadata = [], string &$error = null): ?array {
  $currency = mem_stripe_currency($currency);
  $amountCents = (int) round($amount * 100);
  if ($amountCents <= 0) {
    $error = 'Invalid amount.';
    return null;
  }

  $params = [
    'amount' => $amountCents,
    'currency' => $currency,
    'description' => ucfirst($transactionType) . ' membership payment',
    'metadata' => array_merge([
      'member_id' => (string) $memberId,
      'transaction_type' => $transactionType,
      'site' => $_SERVER['HTTP_HOST'] ?? 'site',
    ], $metadata),
    'payment_method_types[]' => 'card',
  ];

  return mem_stripe_api_request('POST', 'payment_intents', $params, $error);
}

function mem_stripe_retrieve_payment_intent(string $intentId, string &$error = null): ?array {
  $intentId = trim($intentId);
  if ($intentId === '') {
    $error = 'Missing payment intent id.';
    return null;
  }
  return mem_stripe_api_request('GET', 'payment_intents/' . urlencode($intentId), [], $error);
}

function mem_record_membership_transaction_pending(int $memberId, string $transactionType, string $providerRef, float $amount, string $currency, string $notes = ''): ?int {
  global $pdo, $DB_OK;

  if (!mem_table_exists('mem_transaction') || !$DB_OK || !($pdo instanceof PDO)) {
    return null;
  }

  $sql = 'INSERT INTO mem_transaction
    (member_id, transaction_type, payment_provider, payment_method, provider_reference, amount, currency, status, notes, showonweb, archived)
    VALUES
    (:member_id, :transaction_type, "stripe", "card", :provider_reference, :amount, :currency, "pending", :notes, "Yes", 0)
    ON DUPLICATE KEY UPDATE
      amount = VALUES(amount),
      currency = VALUES(currency),
      status = CASE WHEN status = "paid" THEN status ELSE "pending" END,
      notes = CASE WHEN VALUES(notes) <> "" THEN VALUES(notes) ELSE notes END,
      modified = NOW()';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':member_id' => $memberId,
    ':transaction_type' => $transactionType === 'renewal' ? 'renewal' : 'join',
    ':provider_reference' => $providerRef,
    ':amount' => $amount,
    ':currency' => strtoupper($currency),
    ':notes' => $notes,
  ]);
  return (int) $pdo->lastInsertId();
}

function mem_finalize_membership_payment(
  int $memberId,
  string $transactionType,
  string $providerRef,
  float $amount,
  string $currency,
  string $paymentMethod,
  ?string $paidAt,
  string $notes = ''
): ?array {
  global $pdo, $DB_OK;

  if (!mem_ready() || !mem_table_exists('mem_transaction') || !$DB_OK || !($pdo instanceof PDO)) {
    return null;
  }

  $member = mem_load_member($memberId);
  if (!$member) {
    return null;
  }

  $transactionType = ($transactionType === 'renewal') ? 'renewal' : 'join';
  $currency = strtoupper(trim($currency));
  $amount = (float) $amount;
  $paymentMethod = $paymentMethod !== '' ? $paymentMethod : 'card';
  $successEvent = $transactionType === 'renewal' ? 'renewal_payment_success' : 'join_payment_success';
  $failureEvent = $transactionType === 'renewal' ? 'renewal_payment_failed' : 'join_payment_failed';

  try {
    $pdo->beginTransaction();

    $selectStmt = $pdo->prepare('SELECT id, status FROM mem_transaction WHERE provider_reference = :ref LIMIT 1');
    $selectStmt->execute([':ref' => $providerRef]);
    $existing = $selectStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing && ($existing['status'] ?? '') === 'paid') {
      $pdo->commit();
      return [
        'transaction_id' => (int) $existing['id'],
        'provider_reference' => $providerRef,
        'amount' => $amount,
        'currency' => $currency,
        'payment_provider' => 'stripe',
        'payment_method' => $paymentMethod,
        'member_id' => $memberId,
        'member_name' => mem_member_full_name($member),
        'membership_number' => (int) ($member['membership_number'] ?? 0),
      ];
    }

    if ($existing) {
      $updateSql = 'UPDATE mem_transaction
        SET amount = :amount,
            currency = :currency,
            status = "paid",
            paid_at = COALESCE(:paid_at, paid_at, NOW()),
            notes = CASE WHEN :notes <> "" THEN :notes ELSE notes END,
            modified = NOW()
        WHERE id = :id
        LIMIT 1';
      $pdo->prepare($updateSql)->execute([
        ':amount' => $amount,
        ':currency' => $currency,
        ':paid_at' => $paidAt,
        ':notes' => $notes,
        ':id' => (int) $existing['id'],
      ]);
      $transactionId = (int) $existing['id'];
    } else {
      $insertSql = 'INSERT INTO mem_transaction
        (member_id, transaction_type, payment_provider, payment_method, provider_reference, amount, currency, status, paid_at, notes, showonweb, archived)
        VALUES
        (:member_id, :transaction_type, "stripe", :payment_method, :provider_reference, :amount, :currency, "paid", COALESCE(:paid_at, NOW()), :notes, "Yes", 0)';
      $pdo->prepare($insertSql)->execute([
        ':member_id' => $memberId,
        ':transaction_type' => $transactionType,
        ':payment_method' => $paymentMethod,
        ':provider_reference' => $providerRef,
        ':amount' => $amount,
        ':currency' => $currency,
        ':paid_at' => $paidAt,
        ':notes' => $notes,
      ]);
      $transactionId = (int) $pdo->lastInsertId();
    }

    $sqlMember = 'UPDATE mem_member
      SET membership_status = "active",
          membership_expires_at = DATE_ADD(CASE WHEN membership_expires_at IS NOT NULL AND membership_expires_at > CURDATE() THEN membership_expires_at ELSE CURDATE() END, INTERVAL 1 YEAR),
          years_paid_count = years_paid_count + 1,
          payment_method = :payment_method,
          modified = NOW()
      WHERE id = :id';
    $pdo->prepare($sqlMember)->execute([
      ':payment_method' => $paymentMethod,
      ':id' => $memberId,
    ]);

    if (mem_table_exists('mem_membership_year')) {
      $yearSql = 'INSERT INTO mem_membership_year
        (member_id, membership_year, source, transaction_id, notes, showonweb, archived)
        VALUES
        (:member_id, :membership_year, "transaction", :transaction_id, :notes, "Yes", 0)
        ON DUPLICATE KEY UPDATE
          transaction_id = COALESCE(transaction_id, VALUES(transaction_id)),
          source = "transaction",
          notes = VALUES(notes),
          modified = NOW()';
      $renewedUntil = $pdo->prepare('SELECT membership_expires_at FROM mem_member WHERE id = :id LIMIT 1');
      $renewedUntil->execute([':id' => $memberId]);
      $expiry = (string) $renewedUntil->fetchColumn();
      $membershipYear = (int) substr($expiry, 0, 4);
      if ($membershipYear > 0) {
        $pdo->prepare($yearSql)->execute([
          ':member_id' => $memberId,
          ':membership_year' => $membershipYear,
          ':transaction_id' => $transactionId,
          ':notes' => ucfirst($transactionType) . ' payment recorded',
        ]);
      }
    }

    $pdo->commit();
    mem_log_event($successEvent, 'Stripe payment completed', null, $memberId);

    return [
      'transaction_id' => $transactionId,
      'provider_reference' => $providerRef,
      'amount' => $amount,
      'currency' => $currency,
      'payment_provider' => 'stripe',
      'payment_method' => $paymentMethod,
      'member_id' => $memberId,
      'member_name' => mem_member_full_name($member),
      'membership_number' => (int) ($member['membership_number'] ?? 0),
    ];
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    mem_log_event($failureEvent, 'Stripe payment failed: ' . $e->getMessage(), null, $memberId);
    return null;
  }
}
