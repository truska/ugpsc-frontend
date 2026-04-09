<?php
require_once __DIR__ . '/includes/member/ui.php';

mem_require_login();
$memberSession = mem_current_member();
if (empty($memberSession['is_admin'])) {
  http_response_code(403);
  mem_page_header('UGPSC Members | Admin', ['active' => 'admin']);
  ?>
  <div class="container" style="max-width:760px;">
    <div class="mem-card p-4">
      <h1 class="display-font h3 mb-2">Admin Access Required</h1>
      <p class="text-secondary mb-3">This area is reserved for membership administrators.</p>
      <a class="btn btn-outline-secondary" href="<?php echo mem_h(mem_base_url('/member-dashboard.php')); ?>">Back to dashboard</a>
    </div>
  </div>
  <?php
  mem_page_footer();
  exit;
}

$baseUrl = mem_base_url('/member-admin-mailings.php');
$templateKey = (string) ($_GET['template'] ?? 'renewal_email');
$year = (int) ($_GET['year'] ?? (int) date('Y'));
$audience = (string) ($_GET['audience'] ?? 'has_email');
$messageBody = '';

$errors = [];
$notices = [];
$yearOptions = [];
$audienceRows = [];

global $pdo, $DB_OK;
if (!mem_ready() || !$DB_OK || !($pdo instanceof PDO)) {
  $errors[] = 'Database unavailable.';
} else {
  if (mem_table_exists('mem_membership_year')) {
    $yearStmt = $pdo->query('SELECT DISTINCT membership_year FROM mem_membership_year WHERE archived = 0 ORDER BY membership_year DESC');
    $yearOptions = array_map('intval', $yearStmt->fetchAll(PDO::FETCH_COLUMN));
  }
  if (!$yearOptions) {
    $yearOptions = [$year, $year - 1];
  }
}

// Simple template defaults.
$templateMeta = [
  'renewal_email' => [
    'title' => 'Membership Renewal Email',
    'description' => 'Email members with login reminder and quick renew link.',
    'audience_help' => 'Choose members with an email address.',
    'body' => "Hello {{first_name}},\n\nIt's time to renew your membership for {{year}}.\n\nRenew quickly using this link:\n{{quick_renew_link}}\n\nOr log in at {{site_url}} to renew from your dashboard.\n\nThanks,\n{{site_name}}",
  ],
  'renewal_letter' => [
    'title' => 'Membership Renewal Letter',
    'description' => 'Printable letter for members without an email address.',
    'audience_help' => 'Choose members without an email address.',
    'body' => "Dear {{full_name}},\n\nYour membership for {{year}} is due. Please renew using the enclosed instructions or visit {{site_url}}.\n\nMembership No: {{member_number}}\n\nThank you,\n{{site_name}}",
  ],
  'renewal_csv' => [
    'title' => 'Membership Renewal CSV List',
    'description' => 'Export a list for third-party processing.',
    'audience_help' => 'Choose all or segment by email availability.',
    'body' => '',
  ],
];

if (!isset($templateMeta[$templateKey])) {
  $templateKey = 'renewal_email';
}

// Working message body (overridden by POST)
$messageBody = trim((string) ($templateMeta[$templateKey]['body'] ?? ''));

// Sample merge data for preview.
$siteUrl = cms_base_url();
$siteName = trim((string) cms_pref('prefSiteName', 'WCCMS'));
$previewData = [
  '{{first_name}}' => 'Sam',
  '{{full_name}}' => 'Sam Rider',
  '{{member_number}}' => '1024',
  '{{year}}' => (string) $year,
  '{{site_url}}' => $siteUrl,
  '{{site_name}}' => $siteName,
  '{{quick_renew_link}}' => $siteUrl . '/member-renew-quick.php?token=EXAMPLE',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');
  if (!mem_verify_csrf($csrf)) {
    $errors[] = 'Session check failed. Please try again.';
  } else {
    $messageBody = trim((string) ($_POST['message_body'] ?? $messageBody));
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'send_test') {
      $targetEmail = trim((string) ($_POST['test_email'] ?? ''));
      if ($targetEmail === '') {
        $targetEmail = trim((string) cms_pref('prefEmailBCC', '', 'web'));
      }
      if ($targetEmail === '' || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid test email address or set prefEmailBCC.';
      } else {
        $rendered = $messageBody !== '' ? $messageBody : (string) ($templateMeta[$templateKey]['body'] ?? '');
        foreach ($previewData as $token => $value) {
          $rendered = str_replace($token, $value, $rendered);
        }
        $subject = 'Test: ' . ($templateMeta[$templateKey]['title'] ?? 'Mailing') . ' - ' . $year;
        $htmlBody = nl2br(mem_h($rendered));
        $textBody = $rendered;
        $sent = mem_send_mail($targetEmail, $subject, $htmlBody, $textBody);
        if ($sent) {
          $notices[] = 'Test email sent to ' . $targetEmail . '.';
        } else {
          $errors[] = 'Test email failed to send.';
        }
      }
    }
  }
}

$messageBody = $messageBody !== '' ? $messageBody : trim((string) ($templateMeta[$templateKey]['body'] ?? ''));

$audienceFilter = '';
if ($audience === 'has_email') {
  $audienceFilter = "AND m.email IS NOT NULL AND m.email <> ''";
} elseif ($audience === 'no_email') {
  $audienceFilter = "AND (m.email IS NULL OR m.email = '')";
}

if (!$errors && mem_ready() && mem_table_exists('mem_membership_year')) {
  $sql = 'SELECT m.id, m.membership_number, m.firstname, m.surname, m.email, m.tel1, m.address1, m.address2, m.town, m.county, m.postcode
          FROM mem_membership_year y
          JOIN mem_member m ON m.id = y.member_id
          WHERE y.archived = 0
            AND y.membership_year = :year
            AND m.archived = 0
            AND m.showonweb = "Yes" ' . $audienceFilter . '
          ORDER BY m.membership_number ASC';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':year' => $year]);
  $audienceRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$previewBody = $messageBody !== '' ? $messageBody : ($templateMeta[$templateKey]['body'] ?? '');
foreach ($previewData as $token => $value) {
  $previewBody = str_replace($token, $value, $previewBody);
}

mem_page_header('UGPSC Admin | Mailings', ['active' => 'admin']);
?>
<div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo mem_h(mem_base_url('/member-admin-dashboard.php')); ?>">Admin Dashboard</a>
    </div>
    <span class="badge text-bg-light">Admin</span>
  </div>

  <div class="mem-card p-4 p-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="display-font h3 mb-1">Mailings</h1>
        <p class="text-secondary mb-0">Renewal emails, printable letters, and export lists.</p>
      </div>
      <div><span class="badge text-bg-light">Lists &amp; Campaigns</span></div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h(implode(' ', $errors)); ?></div>
    <?php endif; ?>
    <?php if ($notices): ?>
      <div class="alert alert-success" role="alert"><?php echo mem_h(implode(' ', $notices)); ?></div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4 mb-4">
      <?php foreach ($templateMeta as $key => $meta): ?>
        <div class="col-md-6 col-xl-4">
          <div class="p-3 border rounded h-100 bg-light-subtle">
            <div class="mem-label mb-2"><?php echo mem_h($meta['title']); ?></div>
            <p class="text-secondary small mb-3"><?php echo mem_h($meta['description']); ?></p>
            <form method="get" class="d-flex flex-column gap-2">
              <input type="hidden" name="template" value="<?php echo mem_h($key); ?>">
              <div>
                <label class="form-label mem-label mb-1" for="year-<?php echo mem_h($key); ?>">Year</label>
                <select class="form-select form-select-sm" id="year-<?php echo mem_h($key); ?>" name="year">
                  <?php foreach ($yearOptions as $yearOpt): ?>
                    <option value="<?php echo (int) $yearOpt; ?>" <?php echo (int) $yearOpt === $year ? 'selected' : ''; ?>>
                      <?php echo (int) $yearOpt; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php if ($key !== 'renewal_letter'): ?>
                <div>
                  <label class="form-label mem-label mb-1" for="audience-<?php echo mem_h($key); ?>">Audience</label>
                  <select class="form-select form-select-sm" id="audience-<?php echo mem_h($key); ?>" name="audience">
                    <option value="has_email" <?php echo $audience === 'has_email' ? 'selected' : ''; ?>>Has email</option>
                    <option value="no_email" <?php echo $audience === 'no_email' ? 'selected' : ''; ?>>No email</option>
                    <option value="all" <?php echo $audience === 'all' ? 'selected' : ''; ?>>All members</option>
                  </select>
                  <div class="text-secondary small mt-1"><?php echo mem_h($meta['audience_help']); ?></div>
                </div>
              <?php else: ?>
                <input type="hidden" name="audience" value="no_email">
                <div class="text-secondary small"><?php echo mem_h($meta['audience_help']); ?></div>
              <?php endif; ?>
              <button type="submit" class="btn btn-mem-primary btn-sm align-self-start">Generate</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (!$errors && $templateKey): ?>
      <div class="mem-card p-3 p-lg-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <div class="mem-label mb-1">Template</div>
            <h2 class="h5 mb-0"><?php echo mem_h($templateMeta[$templateKey]['title']); ?> — <?php echo mem_h((string) $year); ?></h2>
            <div class="text-secondary small">Audience: <?php echo mem_h($audience); ?></div>
          </div>
          <?php if ($templateKey === 'renewal_csv'): ?>
            <div class="d-flex gap-2">
              <a class="btn btn-outline-secondary btn-sm" href="#" aria-disabled="true">Export CSV (future)</a>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($templateMeta[$templateKey]['body'] !== ''): ?>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
            <input type="hidden" name="template" value="<?php echo mem_h($templateKey); ?>">
            <input type="hidden" name="year" value="<?php echo (int) $year; ?>">
            <input type="hidden" name="audience" value="<?php echo mem_h($audience); ?>">
            <div class="row g-3">
              <div class="col-lg-6">
                <label class="form-label mem-label mb-1" for="message-body">Message</label>
                <textarea id="message-body" name="message_body" class="form-control" rows="12"><?php echo mem_h($messageBody); ?></textarea>
                <div class="text-secondary small mt-1">Merge tags: {{first_name}}, {{full_name}}, {{member_number}}, {{year}}, {{site_url}}, {{site_name}}, {{quick_renew_link}}</div>
                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                  <label class="form-label mem-label mb-1" for="test-email" style="margin:0;">Test email</label>
                  <input type="email" class="form-control form-control-sm" style="max-width:220px;" id="test-email" name="test_email" value="<?php echo mem_h(cms_pref('prefEmailBCC', '', 'web')); ?>" placeholder="you@example.com">
                  <button type="submit" class="btn btn-sm btn-outline-secondary" name="action" value="send_test">Send Test</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" aria-disabled="true" disabled>Send Campaign (coming soon)</button>
                </div>
              </div>
              <div class="col-lg-6">
                <label class="form-label mem-label mb-1">Preview</label>
                <div class="p-3 border rounded bg-light-subtle" style="white-space: pre-wrap;"><?php echo mem_h($previewBody); ?></div>
              </div>
            </div>
          </form>
          <?php if ($audienceRows): ?>
            <div class="mt-4">
              <div class="mem-label mb-2">Recipients (<?php echo count($audienceRows); ?>)</div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th scope="col">Member #</th>
                      <th scope="col">Name</th>
                      <?php if ($templateKey === 'renewal_email'): ?>
                        <th scope="col">Email</th>
                        <th scope="col">Tel</th>
                      <?php elseif ($templateKey === 'renewal_letter'): ?>
                        <th scope="col">Address</th>
                        <th scope="col">Postcode</th>
                      <?php else: ?>
                        <th scope="col">Email</th>
                        <th scope="col">Postcode</th>
                      <?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($audienceRows as $row): ?>
                      <?php
                        $fullName = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['surname'] ?? ''));
                        $addressParts = array_filter([
                          $row['address1'] ?? '',
                          $row['address2'] ?? '',
                          $row['town'] ?? '',
                          $row['county'] ?? '',
                        ], 'strlen');
                        $addressLine = implode(', ', $addressParts);
                      ?>
                      <tr>
                        <td><?php echo mem_h((string) ($row['membership_number'] ?? '')); ?></td>
                        <td><?php echo mem_h($fullName); ?></td>
                        <?php if ($templateKey === 'renewal_email'): ?>
                          <td><?php echo mem_h((string) ($row['email'] ?? '')); ?></td>
                          <td><?php echo mem_h((string) ($row['tel1'] ?? '')); ?></td>
                        <?php elseif ($templateKey === 'renewal_letter'): ?>
                          <td><?php echo mem_h($addressLine); ?></td>
                          <td><?php echo mem_h((string) ($row['postcode'] ?? '')); ?></td>
                        <?php else: ?>
                          <td><?php echo mem_h((string) ($row['email'] ?? '')); ?></td>
                          <td><?php echo mem_h((string) ($row['postcode'] ?? '')); ?></td>
                        <?php endif; ?>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php else: ?>
            <div class="alert alert-light border text-secondary mt-3 mb-0" role="alert">
              No recipients found for this audience/year selection yet.
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="alert alert-light border text-secondary mb-0" role="alert">
            Export CSV generation will be added here. Use the filters above, then click to export.
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php mem_page_footer(); ?>
