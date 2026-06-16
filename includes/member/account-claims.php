<?php
require_once __DIR__ . '/auth.php';

function mem_account_claim_ready(): bool {
  return mem_ready() && mem_table_exists('mem_account_claim');
}

function mem_account_claim_generate_code(): string {
  global $pdo;

  $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
  do {
    $code = '';
    for ($i = 0; $i < 8; $i++) {
      $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM mem_account_claim WHERE access_code_hash = :hash');
    $stmt->execute([':hash' => hash('sha256', $code)]);
  } while ((int) $stmt->fetchColumn() > 0);

  return $code;
}

function mem_account_claim_request(int $membershipNumber, bool $adminRequest = false): bool {
  global $pdo, $DB_OK;

  if (!mem_account_claim_ready() || !$DB_OK || !($pdo instanceof PDO) || $membershipNumber <= 0) {
    return false;
  }

  $requestIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
  if (!$adminRequest && $requestIp !== '') {
    $rateStmt = $pdo->prepare(
      'SELECT COUNT(*)
       FROM mem_account_claim
       WHERE request_ip = :request_ip
         AND requested_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)'
    );
    $rateStmt->execute([':request_ip' => $requestIp]);
    if ((int) $rateStmt->fetchColumn() >= 5) {
      mem_log_event('account_claim_request_limited', 'Account claim request rate limit reached');
      return false;
    }
  }

  $stmt = $pdo->prepare(
    'SELECT *
     FROM mem_member
     WHERE membership_number = :membership_number
       AND archived = 0
       AND showonweb = "Yes"
     LIMIT 1'
  );
  $stmt->execute([':membership_number' => $membershipNumber]);
  $member = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$member) {
    mem_log_event('account_claim_request_unknown', 'Account claim requested for an unknown membership number');
    return false;
  }

  $hasUsableEmail = filter_var((string) ($member['email'] ?? ''), FILTER_VALIDATE_EMAIL)
    && (int) ($member['email_is_placeholder'] ?? 0) !== 1;
  if ($hasUsableEmail && (int) ($member['login_enabled'] ?? 0) === 1) {
    mem_log_event('account_claim_request_existing_login', 'Account claim requested for a login-enabled member', null, (int) $member['id']);
    return false;
  }

  $cooldown = $pdo->prepare(
    'SELECT id
     FROM mem_account_claim
     WHERE member_id = :member_id
       AND archived = 0
       AND status NOT IN ("completed", "cancelled")
       AND requested_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY id DESC
     LIMIT 1'
  );
  $cooldown->execute([':member_id' => (int) $member['id']]);
  if ($cooldown->fetchColumn()) {
    mem_log_event('account_claim_request_repeated', 'Account claim request suppressed during cooldown', null, (int) $member['id']);
    return true;
  }

  $pdo->prepare(
    'UPDATE mem_account_claim
     SET status = "cancelled", modified = NOW()
     WHERE member_id = :member_id
       AND archived = 0
       AND status NOT IN ("completed", "cancelled")'
  )->execute([':member_id' => (int) $member['id']]);

  $code = mem_account_claim_generate_code();
  $token = bin2hex(random_bytes(32));
  $expiresAt = (new DateTime('+30 days'))->format('Y-m-d H:i:s');
  $claimUrl = mem_base_url('/member-claim-account.php?token=' . urlencode($token));
  $name = mem_member_full_name($member);

  $sql = 'INSERT INTO mem_account_claim (
            member_id, membership_number, recipient_name,
            address1, address2, town, county, country, postcode,
            access_code_hash, access_code, claim_token_hash, claim_url,
            status, requested_at, expires_at, request_ip
          ) VALUES (
            :member_id, :membership_number, :recipient_name,
            :address1, :address2, :town, :county, :country, :postcode,
            :access_code_hash, :access_code, :claim_token_hash, :claim_url,
            "pending_letter", NOW(), :expires_at, :request_ip
          )';
  $pdo->prepare($sql)->execute([
    ':member_id' => (int) $member['id'],
    ':membership_number' => (int) $member['membership_number'],
    ':recipient_name' => $name !== '' ? $name : 'UGPSC Member',
    ':address1' => $member['address1'] ?? null,
    ':address2' => $member['address2'] ?? null,
    ':town' => $member['town'] ?? null,
    ':county' => $member['county'] ?? null,
    ':country' => $member['country'] ?? null,
    ':postcode' => $member['postcode'] ?? null,
    ':access_code_hash' => hash('sha256', $code),
    ':access_code' => $code,
    ':claim_token_hash' => hash('sha256', $token),
    ':claim_url' => $claimUrl,
    ':expires_at' => $expiresAt,
    ':request_ip' => $requestIp !== '' ? $requestIp : null,
  ]);

  mem_log_event('account_claim_requested', 'Account claim letter queued', $sql, (int) $member['id']);
  return true;
}

function mem_account_claim_by_token(string $token): ?array {
  global $pdo, $DB_OK;

  if (!mem_account_claim_ready() || !$DB_OK || !($pdo instanceof PDO) || trim($token) === '') {
    return null;
  }

  $stmt = $pdo->prepare(
    'SELECT *
     FROM mem_account_claim
     WHERE claim_token_hash = :token_hash
       AND archived = 0
       AND expires_at > NOW()
       AND status NOT IN ("completed", "cancelled")
     LIMIT 1'
  );
  $stmt->execute([':token_hash' => hash('sha256', trim($token))]);
  $claim = $stmt->fetch(PDO::FETCH_ASSOC);
  return $claim ?: null;
}

function mem_account_claim_by_code(int $membershipNumber, string $code): ?array {
  global $pdo, $DB_OK;

  $code = mem_normalize_magic_code($code);
  if (
    !mem_account_claim_ready()
    || !$DB_OK
    || !($pdo instanceof PDO)
    || $membershipNumber <= 0
    || strlen($code) !== 8
  ) {
    return null;
  }

  $stmt = $pdo->prepare(
    'SELECT *
     FROM mem_account_claim
     WHERE membership_number = :membership_number
       AND access_code_hash = :code_hash
       AND archived = 0
       AND expires_at > NOW()
       AND status NOT IN ("completed", "cancelled")
       AND failed_attempts < 10
     LIMIT 1'
  );
  $stmt->execute([
    ':membership_number' => $membershipNumber,
    ':code_hash' => hash('sha256', $code),
  ]);
  $claim = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($claim) {
    return $claim;
  }

  $pdo->prepare(
    'UPDATE mem_account_claim
     SET failed_attempts = failed_attempts + 1, last_attempt_at = NOW(), modified = NOW()
     WHERE membership_number = :membership_number
       AND archived = 0
       AND expires_at > NOW()
       AND status NOT IN ("completed", "cancelled")'
  )->execute([':membership_number' => $membershipNumber]);
  mem_log_event('account_claim_code_failed', 'Invalid account claim code submitted');
  return null;
}

function mem_account_claim_authorize(array $claim): void {
  global $pdo;

  $claimId = (int) ($claim['id'] ?? 0);
  if ($claimId <= 0) {
    return;
  }

  $_SESSION['mem_account_claim_id'] = $claimId;
  $pdo->prepare(
    'UPDATE mem_account_claim
     SET status = CASE
           WHEN status IN ("pending_letter", "letter_printed") THEN "identity_verified"
           ELSE status
         END,
         identity_verified_at = COALESCE(identity_verified_at, NOW()),
         modified = NOW()
     WHERE id = :id
     LIMIT 1'
  )->execute([':id' => $claimId]);
  mem_log_event('account_claim_identity_verified', 'Posted account claim credential accepted', null, (int) $claim['member_id']);
}

function mem_account_claim_current(): ?array {
  global $pdo, $DB_OK;

  $claimId = (int) ($_SESSION['mem_account_claim_id'] ?? 0);
  if (!mem_account_claim_ready() || !$DB_OK || !($pdo instanceof PDO) || $claimId <= 0) {
    return null;
  }

  $stmt = $pdo->prepare(
    'SELECT *
     FROM mem_account_claim
     WHERE id = :id
       AND archived = 0
       AND expires_at > NOW()
       AND status NOT IN ("completed", "cancelled")
     LIMIT 1'
  );
  $stmt->execute([':id' => $claimId]);
  $claim = $stmt->fetch(PDO::FETCH_ASSOC);
  return $claim ?: null;
}

function mem_account_claim_email_available(string $email, int $memberId): bool {
  global $pdo;

  $stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM mem_member
     WHERE LOWER(email) = LOWER(:email)
       AND id <> :member_id
       AND archived = 0'
  );
  $stmt->execute([
    ':email' => $email,
    ':member_id' => $memberId,
  ]);
  return (int) $stmt->fetchColumn() === 0;
}

function mem_account_claim_is_dev(): bool {
  $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
  return str_starts_with($host, 'dev-') || str_contains($host, '.test') || str_contains($host, 'localhost');
}

function mem_account_claim_start_email_verification(array $claim, string $email, ?string &$verificationUrl = null): bool {
  global $pdo, $DB_OK;

  $email = strtolower(trim($email));
  $memberId = (int) ($claim['member_id'] ?? 0);
  if (
    !mem_account_claim_ready()
    || !$DB_OK
    || !($pdo instanceof PDO)
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !mem_account_claim_email_available($email, $memberId)
  ) {
    return false;
  }

  $token = bin2hex(random_bytes(32));
  $verificationUrl = mem_base_url('/member-claim-account.php?verify=' . urlencode($token));
  $expiresAt = (new DateTime('+2 hours'))->format('Y-m-d H:i:s');
  $sql = 'UPDATE mem_account_claim
          SET pending_email = :email,
              email_token_hash = :token_hash,
              email_expires_at = :expires_at,
              email_sent_at = NULL,
              status = "email_pending",
              modified = NOW()
          WHERE id = :id
            AND archived = 0
            AND expires_at > NOW()
            AND status IN ("identity_verified", "email_pending")
          LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':email' => $email,
    ':token_hash' => hash('sha256', $token),
    ':expires_at' => $expiresAt,
    ':id' => (int) $claim['id'],
  ]);
  if ($stmt->rowCount() !== 1) {
    return false;
  }

  mem_log_event('account_claim_email_requested', 'Account claim email verification requested', $sql, $memberId);
  if (mem_account_claim_is_dev()) {
    return true;
  }

  $sent = mem_send_mail(
    $email,
    'Verify your UGPSC member email',
    '<p>Please verify this email address to finish claiming your UGPSC member account.</p>'
      . '<p><a href="' . mem_h($verificationUrl) . '">Verify email and set password</a></p>'
      . '<p>This link expires in 2 hours.</p>',
    "Verify your email and set your password:\n{$verificationUrl}\n\nThis link expires in 2 hours."
  );
  if ($sent) {
    $pdo->prepare(
      'UPDATE mem_account_claim
       SET email_sent_at = NOW(), modified = NOW()
       WHERE id = :id
       LIMIT 1'
    )->execute([':id' => (int) $claim['id']]);
  }
  return $sent;
}

function mem_account_claim_verify_email(string $token): ?array {
  global $pdo, $DB_OK;

  if (!mem_account_claim_ready() || !$DB_OK || !($pdo instanceof PDO) || trim($token) === '') {
    return null;
  }

  $stmt = $pdo->prepare(
    'SELECT *
     FROM mem_account_claim
     WHERE email_token_hash = :token_hash
       AND email_expires_at > NOW()
       AND pending_email IS NOT NULL
       AND archived = 0
       AND status = "email_pending"
     LIMIT 1'
  );
  $stmt->execute([':token_hash' => hash('sha256', trim($token))]);
  $claim = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$claim || !mem_account_claim_email_available((string) $claim['pending_email'], (int) $claim['member_id'])) {
    return null;
  }

  $pdo->prepare(
    'UPDATE mem_account_claim
     SET status = "email_verified", email_verified_at = NOW(), modified = NOW()
     WHERE id = :id
     LIMIT 1'
  )->execute([':id' => (int) $claim['id']]);
  $_SESSION['mem_account_claim_verified_id'] = (int) $claim['id'];
  mem_log_event('account_claim_email_verified', 'Account claim email verified', null, (int) $claim['member_id']);
  $claim['status'] = 'email_verified';
  return $claim;
}

function mem_account_claim_verified(): ?array {
  global $pdo, $DB_OK;

  $claimId = (int) ($_SESSION['mem_account_claim_verified_id'] ?? 0);
  if (!mem_account_claim_ready() || !$DB_OK || !($pdo instanceof PDO) || $claimId <= 0) {
    return null;
  }

  $stmt = $pdo->prepare(
    'SELECT *
     FROM mem_account_claim
     WHERE id = :id
       AND status = "email_verified"
       AND email_verified_at IS NOT NULL
       AND archived = 0
     LIMIT 1'
  );
  $stmt->execute([':id' => $claimId]);
  $claim = $stmt->fetch(PDO::FETCH_ASSOC);
  return $claim ?: null;
}

function mem_account_claim_complete(array $claim, string $password, ?string &$error = null): bool {
  global $pdo, $DB_OK;

  if (!mem_account_claim_ready() || !$DB_OK || !($pdo instanceof PDO)) {
    $error = 'Account claiming is unavailable right now.';
    return false;
  }
  if (strlen($password) < mem_password_min_length()) {
    $error = 'Use at least ' . mem_password_min_length() . ' characters for your password.';
    return false;
  }

  $email = strtolower(trim((string) ($claim['pending_email'] ?? '')));
  $memberId = (int) ($claim['member_id'] ?? 0);
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !mem_account_claim_email_available($email, $memberId)) {
    $error = 'That email address can no longer be used.';
    return false;
  }

  try {
    $pdo->beginTransaction();
    $memberSql = 'UPDATE mem_member
                  SET email = :email,
                      password_hash = :password_hash,
                      login_enabled = 1,
                      email_is_placeholder = 0,
                      modified = NOW()
                  WHERE id = :id
                    AND archived = 0
                  LIMIT 1';
    $memberStmt = $pdo->prepare($memberSql);
    $memberStmt->execute([
      ':email' => $email,
      ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
      ':id' => $memberId,
    ]);
    if ($memberStmt->rowCount() !== 1) {
      throw new RuntimeException('Member record not updated');
    }

    $claimSql = 'UPDATE mem_account_claim
                 SET status = "completed",
                     completed_at = NOW(),
                     email_token_hash = NULL,
                     modified = NOW()
                 WHERE id = :id
                   AND status = "email_verified"
                 LIMIT 1';
    $claimStmt = $pdo->prepare($claimSql);
    $claimStmt->execute([':id' => (int) $claim['id']]);
    if ($claimStmt->rowCount() !== 1) {
      throw new RuntimeException('Claim record not completed');
    }

    $pdo->prepare(
      'UPDATE mem_account_claim
       SET status = "cancelled", modified = NOW()
       WHERE member_id = :member_id
         AND id <> :id
         AND status NOT IN ("completed", "cancelled")'
    )->execute([
      ':member_id' => $memberId,
      ':id' => (int) $claim['id'],
    ]);
    $pdo->commit();
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $error = 'Your account could not be completed right now.';
    return false;
  }

  unset($_SESSION['mem_account_claim_id'], $_SESSION['mem_account_claim_verified_id']);
  mem_log_event('account_claim_completed', 'Imported member account claimed', $memberSql, $memberId);
  mem_log_change('account_claim_completed', 'Member added a verified email and password', $memberSql, $memberId, $memberId);
  return mem_login_by_member_id($memberId);
}
