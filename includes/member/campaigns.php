<?php
require_once __DIR__ . '/auth.php';

function mem_campaign_test_mode(): bool {
  global $IS_LOCAL;
  return !empty($IS_LOCAL);
}

function mem_campaign_test_emails(): array {
  global $pdo, $DB_OK;
  if (!$DB_OK || !($pdo instanceof PDO)) {
    return [];
  }
  $stmt = $pdo->query(
    'SELECT LOWER(TRIM(email))
     FROM mem_member
     WHERE is_admin = 1
       AND archived = 0
       AND showonweb = "Yes"
       AND email IS NOT NULL
       AND email <> ""'
  );
  return array_values(array_unique(array_filter(
    $stmt->fetchAll(PDO::FETCH_COLUMN),
    static fn(string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false
  )));
}

function mem_campaign_test_email_allowed(string $email): bool {
  return in_array(strtolower(trim($email)), mem_campaign_test_emails(), true);
}

function mem_campaign_preference(string $name, string $default = ''): string {
  global $pdo, $DB_OK;

  $value = trim((string) cms_pref($name, ''));
  if ($value !== '' || !$DB_OK || !($pdo instanceof PDO)) {
    return $value !== '' ? $value : $default;
  }

  $table = cms_preferences_table();
  if (!$table) {
    return $default;
  }

  $stmt = $pdo->prepare("SELECT value FROM {$table} WHERE name = :name AND archived = 0 LIMIT 1");
  $stmt->execute([':name' => $name]);
  $value = trim((string) $stmt->fetchColumn());
  return $value !== '' ? $value : $default;
}

function mem_campaign_letter_stationery(): array {
  $siteName = mem_campaign_preference('prefSiteName', 'UGPSC');
  $company = mem_campaign_preference('prefCompanyName', $siteName);
  $logoFile = mem_campaign_preference('prefLogoEmail');
  if ($logoFile === '') {
    $logoFile = mem_campaign_preference('prefLogo', 'ugpsc-logo.png');
  }
  if ($logoFile !== '' && !preg_match('#^https?://#i', $logoFile) && !str_starts_with($logoFile, '/')) {
    $logoUrl = mem_base_url('/filestore/images/logos/' . ltrim($logoFile, '/'));
  } else {
    $logoUrl = $logoFile;
  }

  return [
    'company' => $company,
    'logo_url' => $logoUrl,
    'address' => array_values(array_filter([
      mem_campaign_preference('prefAddress1'),
      mem_campaign_preference('prefAddress2'),
      mem_campaign_preference('prefTown'),
      mem_campaign_preference('prefCounty'),
      mem_campaign_preference('prefPostcode'),
      mem_campaign_preference('prefCountry'),
    ], static fn(string $line): bool => $line !== '')),
    'telephone' => mem_campaign_preference('prefTel1'),
    'email' => mem_campaign_preference('prefEmail'),
    'website' => mem_base_url(),
  ];
}

function mem_campaign_clean_html(string $html): string {
  $allowed = '<p><br><strong><b><em><i><u><h1><h2><h3><ul><ol><li><a><blockquote>';
  $clean = strip_tags(trim($html), $allowed);
  $clean = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/iu', '', $clean) ?? $clean;
  $clean = preg_replace('/\sstyle\s*=\s*(["\']).*?\1/iu', '', $clean) ?? $clean;
  $clean = preg_replace('/href\s*=\s*(["\'])\s*javascript:.*?\1/iu', 'href="#"', $clean) ?? $clean;
  return $clean;
}

function mem_campaign_absolute_url(string $url): string {
  $url = trim($url);
  if ($url !== '' && str_starts_with($url, '/')) {
    return mem_base_url($url);
  }
  return $url;
}

function mem_campaign_render(string $template, array $recipient, array $campaign, string $channel = 'email'): string {
  $siteUrl = cms_base_url();
  $siteName = trim((string) cms_pref('prefSiteName', 'UGPSC'));
  $quickRenewLink = $channel === 'letter'
    ? mem_base_url('/member-renew-code.php')
    : mem_campaign_absolute_url((string) ($recipient['quick_renew_url'] ?? ''));
  $address = implode(', ', array_values(array_filter([
    trim((string) ($recipient['address1'] ?? '')),
    trim((string) ($recipient['address2'] ?? '')),
    trim((string) ($recipient['town'] ?? '')),
    trim((string) ($recipient['county'] ?? '')),
    trim((string) ($recipient['postcode'] ?? '')),
    trim((string) ($recipient['country'] ?? '')),
  ], static fn(string $line): bool => $line !== '')));
  $replacements = [
    '{{first_name}}' => (string) ($recipient['firstname'] ?? ''),
    '{{full_name}}' => (string) ($recipient['full_name'] ?? ''),
    '{{member_number}}' => (string) ($recipient['membership_number'] ?? ''),
    '{{address}}' => $address,
    '{{year}}' => (string) ($campaign['membership_year'] ?? date('Y')),
    '{{site_url}}' => $siteUrl,
    '{{site_name}}' => $siteName,
    '{{quick_renew_link}}' => $quickRenewLink,
    '{{quick_renew_code}}' => (string) ($recipient['quick_renew_code'] ?? ''),
  ];
  return strtr($template, $replacements);
}

function mem_campaign_html_to_text(string $html): string {
  $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
  $html = preg_replace('/<\/p>|<\/li>|<\/h[1-3]>/i', "\n\n", $html) ?? $html;
  return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function mem_campaign_log(int $campaignId, string $eventType, string $message = '', ?int $recipientId = null): void {
  global $pdo, $DB_OK;
  if (!$DB_OK || !($pdo instanceof PDO) || !mem_table_exists('mem_campaign_activity')) {
    return;
  }
  $actorId = isset($_SESSION['mem_member']['id']) ? (int) $_SESSION['mem_member']['id'] : null;
  $stmt = $pdo->prepare(
    'INSERT INTO mem_campaign_activity (campaign_id, recipient_id, actor_member_id, event_type, message)
     VALUES (:campaign_id, :recipient_id, :actor_member_id, :event_type, :message)'
  );
  $stmt->execute([
    ':campaign_id' => $campaignId,
    ':recipient_id' => $recipientId,
    ':actor_member_id' => $actorId,
    ':event_type' => $eventType,
    ':message' => $message,
  ]);
}

function mem_campaign_build_recipients(int $campaignId): int {
  global $pdo, $DB_OK;
  if (!$DB_OK || !($pdo instanceof PDO) || !mem_table_exists('mem_campaign_recipient')) {
    return 0;
  }
  $campaignStmt = $pdo->prepare('SELECT * FROM mem_campaign WHERE id = :id LIMIT 1');
  $campaignStmt->execute([':id' => $campaignId]);
  $campaign = $campaignStmt->fetch(PDO::FETCH_ASSOC);
  if (!$campaign) {
    return 0;
  }

  $where = ['m.archived = 0', 'm.showonweb = "Yes"'];
  if (mem_campaign_test_mode()) {
    $where[] = 'm.is_admin = 1';
  }
  $params = [];
  $year = (int) ($campaign['membership_year'] ?? 0);
  $join = '';
  if ($year > 0 && mem_table_exists('mem_membership_year')) {
    $join = 'JOIN mem_membership_year y ON y.member_id = m.id AND y.archived = 0 AND y.membership_year = :year';
    $params[':year'] = $year;
  }
  $sql = 'SELECT DISTINCT m.* FROM mem_member m ' . $join . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY m.membership_number';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $insert = $pdo->prepare(
    'INSERT INTO mem_campaign_recipient (
       campaign_id, member_id, membership_number, firstname, surname, full_name, email,
       address1, address2, town, county, country, postcode,
       channel, status, exclusion_reason, quick_renew_url, quick_renew_code
     ) VALUES (
       :campaign_id, :member_id, :membership_number, :firstname, :surname, :full_name, :email,
       :address1, :address2, :town, :county, :country, :postcode,
       :channel, :status, :exclusion_reason, :quick_renew_url, :quick_renew_code
     )'
  );
  $count = 0;
  foreach ($members as $member) {
    $email = strtolower(trim((string) ($member['email'] ?? '')));
    $hasEmail = $email !== ''
      && filter_var($email, FILTER_VALIDATE_EMAIL)
      && empty($member['email_is_placeholder']);
    $hasAddress = trim(implode('', [
      (string) ($member['address1'] ?? ''),
      (string) ($member['town'] ?? ''),
      (string) ($member['postcode'] ?? ''),
    ])) !== '';
    $channel = $hasEmail ? 'email' : ($hasAddress ? 'letter' : 'excluded');
    $status = $channel === 'excluded' ? 'excluded' : 'pending';
    $reason = $channel === 'excluded' ? 'No usable email or postal address' : null;
    if ((string) $campaign['campaign_type'] === 'marketing' && empty($member['gdpr_marketing_opt_in'])) {
      $channel = 'excluded';
      $status = 'excluded';
      $reason = 'Marketing consent not given';
    }

    $renewUrl = null;
    $renewCode = null;
    if ((string) $campaign['campaign_type'] === 'renewal' && $channel !== 'excluded') {
      $token = mem_create_magic_link((int) $member['id'], 'renewal', 24 * 30, $renewCode);
      if ($token) {
        $renewUrl = mem_base_url('/member-renew-quick.php?token=' . urlencode($token));
      }
    }

    $insert->execute([
      ':campaign_id' => $campaignId,
      ':member_id' => (int) $member['id'],
      ':membership_number' => (int) ($member['membership_number'] ?? 0),
      ':firstname' => (string) ($member['firstname'] ?? ''),
      ':surname' => (string) ($member['surname'] ?? ''),
      ':full_name' => mem_member_full_name($member),
      ':email' => $email !== '' ? $email : null,
      ':address1' => (string) ($member['address1'] ?? ''),
      ':address2' => (string) ($member['address2'] ?? ''),
      ':town' => (string) ($member['town'] ?? ''),
      ':county' => (string) ($member['county'] ?? ''),
      ':country' => (string) ($member['country'] ?? ''),
      ':postcode' => (string) ($member['postcode'] ?? ''),
      ':channel' => $channel,
      ':status' => $status,
      ':exclusion_reason' => $reason,
      ':quick_renew_url' => $renewUrl,
      ':quick_renew_code' => $renewCode,
    ]);
    $count++;
  }
  mem_campaign_log($campaignId, 'audience_created', $count . ' recipient snapshots created');
  return $count;
}
