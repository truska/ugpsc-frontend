<?php
require_once __DIR__ . '/bootstrap.php';

function mem_password_min_length(): int {
  return 6;
}

function mem_membership_currency_options(): array {
  return ['GBP'];
}

function mem_currency_symbol(string $currency): string {
  $currency = strtoupper(trim($currency));
  if ($currency === 'GBP') {
    return '£';
  }
  if ($currency === 'EUR') {
    return '€';
  }
  return $currency . ' ';
}

function mem_money_display(float $amount, string $currency): string {
  return mem_currency_symbol($currency) . number_format($amount, 2) . ' ' . strtoupper($currency);
}

function mem_is_overseas_country(?string $country): bool {
  $value = strtoupper(trim((string) $country));
  if ($value === '') {
    return false;
  }

  $domestic = [
    'UK', 'UNITED KINGDOM', 'GB', 'GBR', 'ENGLAND', 'SCOTLAND', 'WALES', 'NORTHERN IRELAND',
    'IRELAND', 'REPUBLIC OF IRELAND', 'EIRE', 'IE', 'IRL',
  ];
  return !in_array($value, $domestic, true);
}

function mem_membership_amount(string $currency, ?string $country = null): float {
  $currency = strtoupper(trim($currency));
  if (!in_array($currency, mem_membership_currency_options(), true)) {
    $currency = 'GBP';
  }

  $isOverseas = mem_is_overseas_country($country);
  $baseGbp = (float) cms_pref('prefMembershipJoinFeeGBP', 25.00);
  $baseEur = (float) cms_pref('prefMembershipJoinFeeEUR', 30.00);
  $overseasGbp = (float) cms_pref('prefMembershipOverseasFeeGBP', $baseGbp);
  $overseasEur = (float) cms_pref('prefMembershipOverseasFeeEUR', $baseEur);

  if ($currency === 'EUR') {
    return $isOverseas ? $overseasEur : $baseEur;
  }
  return $isOverseas ? $overseasGbp : $baseGbp;
}

function mem_default_currency_for_country(?string $country = null): string {
  return 'GBP';
}

function mem_resolve_currency(string $value, ?string $country = null): string {
  $currency = strtoupper(trim($value));
  if (in_array($currency, mem_membership_currency_options(), true)) {
    return $currency;
  }
  return mem_default_currency_for_country($country);
}

function mem_country_options(): array {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return [];
  }

  try {
    $stmt = $pdo->query(
      "SELECT code, name, stripe_code
       FROM country
       WHERE archived = 0
         AND allowbilling = 'Yes'
         AND stripe_code IS NOT NULL
         AND stripe_code <> ''
       ORDER BY sort ASC, name ASC"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $rows ?: [];
  } catch (PDOException $e) {
    return [];
  }
}

function mem_country_lookup_by_code_or_name(string $value): ?array {
  global $pdo, $DB_OK;

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return null;
  }
  $trimmed = trim($value);
  if ($trimmed === '') {
    return null;
  }

  try {
    $sql = 'SELECT code, name, stripe_code
            FROM country
            WHERE archived = 0
              AND allowbilling = "Yes"
              AND (code = :code OR name = :name)
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':code' => strtoupper($trimmed),
      ':name' => $trimmed,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  } catch (PDOException $e) {
    return null;
  }
}

function mem_stripe_country_code(string $value): ?string {
  $lookup = mem_country_lookup_by_code_or_name($value);
  if (is_array($lookup) && !empty($lookup['stripe_code'])) {
    return strtoupper((string) $lookup['stripe_code']);
  }

  $val = strtoupper(trim($value));
  if ($val === '') {
    return null;
  }

  $mapGb = ['UK', 'GB', 'GBR', 'ENGLAND', 'SCOTLAND', 'WALES', 'NORTHERN IRELAND', 'NI', 'NIR', 'NIRL'];
  $mapIe = ['IE', 'IRL', 'IRE', 'IRELAND', 'REPUBLIC OF IRELAND', 'EIRE'];
  if (in_array($val, $mapGb, true)) {
    return 'GB';
  }
  if (in_array($val, $mapIe, true)) {
    return 'IE';
  }

  return null;
}

function mem_format_date_uk(?string $dateValue): string {
  $value = trim((string) $dateValue);
  if ($value === '') {
    return '';
  }
  try {
    $dt = new DateTime($value);
    return $dt->format('d/m/Y');
  } catch (Throwable $e) {
    return $value;
  }
}

function mem_hydrate_session_member(array $member): void {
  $_SESSION['mem_member'] = [
    'id' => (int) $member['id'],
    'membership_number' => (int) ($member['membership_number'] ?? 0),
    'email' => (string) $member['email'],
    'firstname' => (string) ($member['firstname'] ?? ''),
    'surname' => (string) ($member['surname'] ?? ''),
    'membership_status' => (string) ($member['membership_status'] ?? 'pending'),
    'membership_expires_at' => (string) ($member['membership_expires_at'] ?? ''),
    'is_admin' => ((int) ($member['is_admin'] ?? 0) === 1),
  ];
}

function mem_member_full_name(array $member): string {
  return trim((string) ($member['firstname'] ?? '') . ' ' . (string) ($member['surname'] ?? ''));
}

function mem_member_reference(array $member): string {
  $name = mem_member_full_name($member);
  $membershipNumber = (int) ($member['membership_number'] ?? 0);
  if ($membershipNumber > 0 && $name !== '') {
    return $name . ' (Member ' . $membershipNumber . ')';
  }
  if ($membershipNumber > 0) {
    return 'Member ' . $membershipNumber;
  }
  return $name !== '' ? $name : 'Member';
}

function mem_member_has_login_access(array $member): bool {
  return ((int) ($member['login_enabled'] ?? 1) === 1);
}

function mem_current_member(): ?array {
  return $_SESSION['mem_member'] ?? null;
}

function mem_is_logged_in(): bool {
  return isset($_SESSION['mem_member']['id']);
}

function mem_require_login(): void {
  if (!mem_is_logged_in()) {
    header('Location: ' . mem_base_url('/member-login.php'));
    exit;
  }
}

function mem_login(string $email, string $password, string &$error = null): bool {
  global $pdo, $DB_OK;

  if (!mem_ready()) {
    $error = 'Membership tables are not installed yet.';
    return false;
  }

  if (!$DB_OK || !($pdo instanceof PDO)) {
    $error = 'Database unavailable.';
    return false;
  }

  $email = strtolower(trim($email));
  $sql = 'SELECT * FROM mem_member
          WHERE email = :email
            AND login_enabled = 1
            AND archived = 0
            AND showonweb = "Yes"
          LIMIT 1';

  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    $error = 'Unable to log in right now.';
    return false;
  }

  if (!$member || !password_verify($password, (string) ($member['password_hash'] ?? ''))) {
    mem_log_event('login_failed', 'Failed login for ' . $email, $sql, null);
    $error = 'Invalid login details.';
    return false;
  }

  mem_hydrate_session_member($member);

  $updateSql = 'UPDATE mem_member SET last_login_at = NOW(), modified = NOW() WHERE id = :id';
  try {
    $pdo->prepare($updateSql)->execute([':id' => (int) $member['id']]);
  } catch (PDOException $e) {
    // Non-fatal.
  }

  mem_log_event('login_success', 'Member logged in', $updateSql, (int) $member['id']);
  return true;
}

function mem_login_by_member_id(int $memberId): bool {
  global $pdo, $DB_OK;

  if (!mem_ready() || !$DB_OK || !($pdo instanceof PDO) || $memberId <= 0) {
    return false;
  }

  $sql = 'SELECT * FROM mem_member WHERE id = :id AND archived = 0 AND showonweb = "Yes" LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':id' => $memberId]);
  $member = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$member) {
    return false;
  }

  mem_hydrate_session_member($member);
  return true;
}

function mem_logout(): void {
  mem_log_event('logout', 'Member logged out');
  unset($_SESSION['mem_member']);
}

function mem_generate_random_password(int $length = 16): string {
  $raw = bin2hex(random_bytes((int) ceil($length / 2)));
  return substr($raw, 0, $length);
}

function mem_next_membership_number(): int {
  global $pdo, $DB_OK;

  if (!mem_ready() || !$DB_OK || !($pdo instanceof PDO)) {
    return 1;
  }

  try {
    $next = (int) $pdo->query('SELECT COALESCE(MAX(membership_number), 0) + 1 FROM mem_member')->fetchColumn();
    return max(1, $next);
  } catch (PDOException $e) {
    return 1;
  }
}

function mem_create_member(array $data, bool $quickJoin = false): array {
  global $pdo, $DB_OK;

  if (!mem_ready()) {
    return [false, 'Membership tables are not installed yet.', null, null];
  }

  if (!$DB_OK || !($pdo instanceof PDO)) {
    return [false, 'Database unavailable.', null, null];
  }

  $email = strtolower(trim((string) ($data['email'] ?? '')));
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return [false, 'Please enter a valid email address.', null, null];
  }

  $password = (string) ($data['password'] ?? '');
  if ($quickJoin || $password === '') {
    $password = mem_generate_random_password();
  } elseif (strlen($password) < mem_password_min_length()) {
    return [false, 'Password must be at least ' . mem_password_min_length() . ' characters.', null, null];
  }

  $membershipNumber = mem_next_membership_number();

  $sql = 'INSERT INTO mem_member
    (membership_number, email, salutation, firstname, surname, address1, address2, town, county, country, postcode, tel1, tel2, notes,
     password_hash, gdpr_policy_accepted, gdpr_marketing_opt_in, gdpr_accepted_at, membership_status, login_enabled, email_is_placeholder,
     created_via, showonweb, archived)
    VALUES
    (:membership_number, :email, :salutation, :firstname, :surname, :address1, :address2, :town, :county, :country, :postcode, :tel1, :tel2, :notes,
     :password_hash, :gdpr_policy_accepted, :gdpr_marketing_opt_in, :gdpr_accepted_at, :membership_status, 1, 0,
     :created_via, "Yes", 0)';

  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':membership_number' => $membershipNumber,
      ':email' => $email,
      ':salutation' => trim((string) ($data['salutation'] ?? '')),
      ':firstname' => trim((string) ($data['firstname'] ?? '')),
      ':surname' => trim((string) ($data['surname'] ?? '')),
      ':address1' => trim((string) ($data['address1'] ?? '')),
      ':address2' => trim((string) ($data['address2'] ?? '')),
      ':town' => trim((string) ($data['town'] ?? '')),
      ':county' => trim((string) ($data['county'] ?? '')),
      ':country' => trim((string) ($data['country'] ?? '')),
      ':postcode' => trim((string) ($data['postcode'] ?? '')),
      ':tel1' => trim((string) ($data['tel1'] ?? '')),
      ':tel2' => trim((string) ($data['tel2'] ?? '')),
      ':notes' => trim((string) ($data['notes'] ?? '')),
      ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
      ':gdpr_policy_accepted' => !empty($data['gdpr_policy_accepted']) ? 1 : 0,
      ':gdpr_marketing_opt_in' => !empty($data['gdpr_marketing_opt_in']) ? 1 : 0,
      ':gdpr_accepted_at' => !empty($data['gdpr_policy_accepted']) ? date('Y-m-d H:i:s') : null,
      ':membership_status' => 'pending',
      ':created_via' => $quickJoin ? 'quick_join' : 'full_join',
    ]);

    $id = (int) $pdo->lastInsertId();
    mem_log_event('member_created', 'Member created via ' . ($quickJoin ? 'quick join' : 'join form'), $sql, $id);

    return [true, null, $id, $quickJoin ? null : $password];
  } catch (PDOException $e) {
    if ((int) $e->getCode() === 23000) {
      return [false, 'An account with this email already exists.', null, null];
    }
    return [false, 'Unable to create membership right now.', null, null];
  }
}

function mem_request_password_reset(string $email): ?string {
  global $pdo, $DB_OK;

  if (!mem_ready() || !mem_table_exists('mem_password_reset')) {
    return null;
  }
  if (!$DB_OK || !($pdo instanceof PDO)) {
    return null;
  }

  $email = strtolower(trim($email));
  $sqlMember = 'SELECT id
                FROM mem_member
                WHERE email = :email
                  AND login_enabled = 1
                  AND archived = 0
                  AND showonweb = "Yes"
                LIMIT 1';
  $stmt = $pdo->prepare($sqlMember);
  $stmt->execute([':email' => $email]);
  $member = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$member) {
    mem_log_event('password_reset_missing', 'Reset requested for unknown email', $sqlMember, null);
    return null;
  }

  $token = bin2hex(random_bytes(32));
  $tokenHash = hash('sha256', $token);
  // Use server (UTC/GMT) time to avoid DST drift between PHP and MySQL.
  $nowUtc = new DateTime('now', new DateTimeZone('UTC'));
  $expiresAt = $nowUtc->modify('+1 hour')->format('Y-m-d H:i:s');

  $sql = 'INSERT INTO mem_password_reset (member_id, token_hash, expires_at, request_ip) VALUES (:member_id, :token_hash, :expires_at, :request_ip)';
  $pdo->prepare($sql)->execute([
    ':member_id' => (int) $member['id'],
    ':token_hash' => $tokenHash,
    ':expires_at' => $expiresAt,
    ':request_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
  ]);
  mem_log_event('password_reset_requested', 'Password reset token created', $sql, (int) $member['id']);

  return $token;
}

function mem_reset_password(string $token, string $newPassword, string &$error = null): bool {
  global $pdo, $DB_OK;

  if (!mem_ready() || !mem_table_exists('mem_password_reset')) {
    $error = 'Membership reset tables are not installed yet.';
    return false;
  }

  if (!$DB_OK || !($pdo instanceof PDO)) {
    $error = 'Database unavailable.';
    return false;
  }

  $tokenHash = hash('sha256', $token);
  $sqlToken = 'SELECT id, member_id FROM mem_password_reset WHERE token_hash = :token_hash AND expires_at > NOW() AND used_at IS NULL AND archived = 0 LIMIT 1';
  $stmt = $pdo->prepare($sqlToken);
  $stmt->execute([':token_hash' => $tokenHash]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    $error = 'Reset link is invalid or has expired.';
    return false;
  }

  $sqlMember = 'UPDATE mem_member SET password_hash = :password_hash, modified = NOW() WHERE id = :id';
  $pdo->prepare($sqlMember)->execute([
    ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
    ':id' => (int) $row['member_id'],
  ]);

  $sqlReset = 'UPDATE mem_password_reset SET used_at = NOW(), modified = NOW(), showonweb = "No" WHERE id = :id';
  $pdo->prepare($sqlReset)->execute([':id' => (int) $row['id']]);

  mem_log_event('password_reset_completed', 'Password reset complete', $sqlMember . '; ' . $sqlReset, (int) $row['member_id']);
  return true;
}

function mem_load_member(int $memberId): ?array {
  global $pdo, $DB_OK;

  if (!mem_ready() || !$DB_OK || !($pdo instanceof PDO)) {
    return null;
  }

  $sql = 'SELECT * FROM mem_member WHERE id = :id AND archived = 0 LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':id' => $memberId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

function mem_update_profile(int $memberId, array $data, string &$error = null): bool {
  global $pdo, $DB_OK;

  if (!mem_ready() || !$DB_OK || !($pdo instanceof PDO)) {
    $error = 'Database unavailable.';
    return false;
  }

  $sql = 'UPDATE mem_member
    SET salutation = :salutation,
        firstname = :firstname,
        surname = :surname,
        address1 = :address1,
        address2 = :address2,
        town = :town,
        county = :county,
        country = :country,
        postcode = :postcode,
        tel1 = :tel1,
        tel2 = :tel2,
        gdpr_marketing_opt_in = :gdpr_marketing_opt_in,
        modified = NOW()
    WHERE id = :id';

  try {
    $pdo->prepare($sql)->execute([
      ':salutation' => trim((string) ($data['salutation'] ?? '')),
      ':firstname' => trim((string) ($data['firstname'] ?? '')),
      ':surname' => trim((string) ($data['surname'] ?? '')),
      ':address1' => trim((string) ($data['address1'] ?? '')),
      ':address2' => trim((string) ($data['address2'] ?? '')),
      ':town' => trim((string) ($data['town'] ?? '')),
      ':county' => trim((string) ($data['county'] ?? '')),
      ':country' => trim((string) ($data['country'] ?? '')),
      ':postcode' => trim((string) ($data['postcode'] ?? '')),
      ':tel1' => trim((string) ($data['tel1'] ?? '')),
      ':tel2' => trim((string) ($data['tel2'] ?? '')),
      ':gdpr_marketing_opt_in' => !empty($data['gdpr_marketing_opt_in']) ? 1 : 0,
      ':id' => $memberId,
    ]);

    mem_log_event('profile_updated', 'Member profile updated', $sql, $memberId);
    return true;
  } catch (PDOException $e) {
    $error = 'Could not update your profile.';
    return false;
  }
}

function mem_complete_emulated_membership_payment(int $memberId, string $transactionType, array $paymentInput, string &$error = null): ?array {
  global $pdo, $DB_OK;

  if (!mem_ready() || !mem_table_exists('mem_transaction') || !$DB_OK || !($pdo instanceof PDO)) {
    $error = 'Payment services are not configured yet.';
    return null;
  }

  $member = mem_load_member($memberId);
  if (!$member) {
    $error = 'Member account not found.';
    return null;
  }

  $transactionType = ($transactionType === 'renewal') ? 'renewal' : 'join';
  $currency = mem_resolve_currency((string) ($paymentInput['currency'] ?? ''), (string) ($member['country'] ?? ''));
  $amount = mem_membership_amount($currency, (string) ($member['country'] ?? ''));
  $providerRef = 'EMU-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
  $paymentProvider = 'stripe_emulator';
  $paymentMethod = 'card';
  $successEvent = $transactionType === 'renewal' ? 'renewal_payment_success' : 'join_payment_success';
  $failureEvent = $transactionType === 'renewal' ? 'renewal_payment_failed' : 'join_payment_failed';

  try {
    $pdo->beginTransaction();

    $sqlTx = 'INSERT INTO mem_transaction
      (member_id, transaction_type, payment_provider, payment_method, provider_reference, amount, currency, status, paid_at, notes, showonweb, archived)
      VALUES
      (:member_id, :transaction_type, :payment_provider, :payment_method, :provider_reference, :amount, :currency, "paid", NOW(), :notes, "Yes", 0)';

    $note = 'Emulated Stripe payment. Name=' . trim((string) ($paymentInput['card_name'] ?? ''));
    $stmt = $pdo->prepare($sqlTx);
    $stmt->execute([
      ':member_id' => $memberId,
      ':transaction_type' => $transactionType,
      ':payment_provider' => $paymentProvider,
      ':payment_method' => $paymentMethod,
      ':provider_reference' => $providerRef,
      ':amount' => $amount,
      ':currency' => $currency,
      ':notes' => $note,
    ]);

    $transactionId = (int) $pdo->lastInsertId();

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
    mem_log_event($successEvent, 'Emulated payment completed', $sqlTx . '; ' . $sqlMember, $memberId);

    return [
      'transaction_id' => $transactionId,
      'provider_reference' => $providerRef,
      'amount' => $amount,
      'currency' => $currency,
      'payment_provider' => $paymentProvider,
      'payment_method' => $paymentMethod,
      'member_id' => $memberId,
      'member_name' => mem_member_full_name($member),
      'membership_number' => (int) ($member['membership_number'] ?? 0),
    ];
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $error = 'Payment could not be completed.';
    mem_log_event($failureEvent, 'Emulated payment failed: ' . $e->getMessage(), null, $memberId);
    return null;
  }
}

function mem_complete_emulated_join_payment(int $memberId, array $paymentInput, string &$error = null): ?array {
  return mem_complete_emulated_membership_payment($memberId, 'join', $paymentInput, $error);
}

function mem_complete_emulated_renewal_payment(int $memberId, array $paymentInput, string &$error = null): ?array {
  return mem_complete_emulated_membership_payment($memberId, 'renewal', $paymentInput, $error);
}

function mem_create_magic_link(int $memberId, string $linkType = 'renewal', int $ttlHours = 72): ?string {
  global $pdo, $DB_OK;

  if (!mem_ready() || !mem_table_exists('mem_magic_link') || !$DB_OK || !($pdo instanceof PDO) || $memberId <= 0) {
    return null;
  }

  $linkType = ($linkType === 'quick_login') ? 'quick_login' : 'renewal';
  $token = bin2hex(random_bytes(32));
  $tokenHash = hash('sha256', $token);
  $expiresAt = (new DateTime('+' . max(1, $ttlHours) . ' hours'))->format('Y-m-d H:i:s');

  $sql = 'INSERT INTO mem_magic_link (member_id, link_type, token_hash, expires_at, request_ip, showonweb, archived)
          VALUES (:member_id, :link_type, :token_hash, :expires_at, :request_ip, "Yes", 0)';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':member_id' => $memberId,
    ':link_type' => $linkType,
    ':token_hash' => $tokenHash,
    ':expires_at' => $expiresAt,
    ':request_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
  ]);
  mem_log_event('magic_link_created', 'Magic link created: ' . $linkType, $sql, $memberId);

  return $token;
}

function mem_validate_magic_link(string $token, string $linkType = 'renewal'): ?array {
  global $pdo, $DB_OK;

  if (!mem_ready() || !mem_table_exists('mem_magic_link') || !$DB_OK || !($pdo instanceof PDO) || trim($token) === '') {
    return null;
  }

  $linkType = ($linkType === 'quick_login') ? 'quick_login' : 'renewal';
  $sql = 'SELECT id, member_id, expires_at, used_at
          FROM mem_magic_link
          WHERE token_hash = :token_hash
            AND link_type = :link_type
            AND archived = 0
            AND showonweb = "Yes"
            AND expires_at > NOW()
            AND used_at IS NULL
          LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':token_hash' => hash('sha256', $token),
    ':link_type' => $linkType,
  ]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

function mem_mark_magic_link_used(int $linkId): bool {
  global $pdo, $DB_OK;

  if (!mem_ready() || !mem_table_exists('mem_magic_link') || !$DB_OK || !($pdo instanceof PDO) || $linkId <= 0) {
    return false;
  }

  $sql = 'UPDATE mem_magic_link SET used_at = NOW(), modified = NOW(), showonweb = "No" WHERE id = :id LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':id' => $linkId]);
  return $stmt->rowCount() > 0;
}
