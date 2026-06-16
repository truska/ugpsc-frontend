<?php
require_once __DIR__ . '/includes/member/ui.php';
require_once __DIR__ . '/includes/member/campaigns.php';

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
$adminDashboard = mem_base_url('/member-admin-dashboard.php');
$view = (string) ($_GET['view'] ?? 'list');
$campaignId = max(0, (int) ($_GET['id'] ?? 0));
$output = (string) ($_GET['output'] ?? '');
$errors = [];
$notices = [];
if (!empty($_SESSION['mem_campaign_notice'])) {
  $notices[] = (string) $_SESSION['mem_campaign_notice'];
  unset($_SESSION['mem_campaign_notice']);
}

global $pdo, $DB_OK;
$campaignReady = $DB_OK
  && ($pdo instanceof PDO)
  && mem_table_exists('mem_campaign')
  && mem_table_exists('mem_campaign_recipient')
  && mem_table_exists('mem_campaign_activity');
$campaignTestMode = mem_campaign_test_mode();
$campaignTestEmails = $campaignTestMode ? mem_campaign_test_emails() : [];

if (!$DB_OK || !($pdo instanceof PDO)) {
  $errors[] = 'Database unavailable.';
} elseif (!$campaignReady) {
  $errors[] = 'The campaign tables are not installed yet.';
}

$defaultRenewalEmail = '<h2>Membership renewal</h2>'
  . '<p>Hello {{first_name}},</p>'
  . '<p>It is time to renew your membership for {{year}}.</p>'
  . '<p><strong><a href="{{quick_renew_link}}">Renew your membership online</a></strong></p>'
  . '<p>Your membership number is <strong>{{member_number}}</strong>.</p>';
$defaultRenewalLetter = '<p>Dear {{full_name}},</p>'
  . '<p>It is time to renew your membership for {{year}}.</p>'
  . '<p>Your membership number is <strong>{{member_number}}</strong>.</p>'
  . '<p>Renew online using this personal link:</p>'
  . '<p>{{quick_renew_link}}</p>';

if ($campaignReady && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');
  if (!mem_verify_csrf($csrf)) {
    $errors[] = 'Session check failed. Please try again.';
  } else {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create_campaign') {
      $type = (string) ($_POST['campaign_type'] ?? 'information');
      if (!in_array($type, ['renewal', 'information', 'marketing'], true)) {
        $type = 'information';
      }
      $name = trim((string) ($_POST['name'] ?? ''));
      $subject = trim((string) ($_POST['subject'] ?? ''));
      $year = max(0, (int) ($_POST['membership_year'] ?? 0));
      $emailHtml = mem_campaign_clean_html((string) ($_POST['email_html'] ?? ''));
      $letterHtml = mem_campaign_clean_html((string) ($_POST['letter_html'] ?? ''));
      $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
      if ($name === '' || $subject === '') {
        $errors[] = 'Campaign name and email subject are required.';
      } elseif ($type === 'renewal' && $year <= 0) {
        $errors[] = 'Choose the membership year for a renewal campaign.';
      } else {
        $stmt = $pdo->prepare(
          'INSERT INTO mem_campaign
           (campaign_type, name, subject, membership_year, email_html, letter_html, image_url, status, created_by_member_id)
           VALUES
           (:campaign_type, :name, :subject, :membership_year, :email_html, :letter_html, :image_url, "draft", :created_by)'
        );
        $stmt->execute([
          ':campaign_type' => $type,
          ':name' => $name,
          ':subject' => $subject,
          ':membership_year' => $year > 0 ? $year : null,
          ':email_html' => $emailHtml,
          ':letter_html' => $letterHtml,
          ':image_url' => $imageUrl !== '' ? $imageUrl : null,
          ':created_by' => (int) ($memberSession['id'] ?? 0),
        ]);
        $newId = (int) $pdo->lastInsertId();
        mem_campaign_log($newId, 'campaign_created', 'Campaign created');
        header('Location: ' . $baseUrl . '?view=campaign&id=' . $newId);
        exit;
      }
    } elseif ($campaignId > 0) {
      if ($action === 'save_campaign') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $emailHtml = mem_campaign_clean_html((string) ($_POST['email_html'] ?? ''));
        $letterHtml = mem_campaign_clean_html((string) ($_POST['letter_html'] ?? ''));
        $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
        if ($name === '' || $subject === '') {
          $errors[] = 'Campaign name and email subject are required.';
        } else {
          $stmt = $pdo->prepare(
            'UPDATE mem_campaign
             SET name = :name, subject = :subject, email_html = :email_html,
                 letter_html = :letter_html, image_url = :image_url, modified = NOW()
             WHERE id = :id LIMIT 1'
          );
          $stmt->execute([
            ':name' => $name,
            ':subject' => $subject,
            ':email_html' => $emailHtml,
            ':letter_html' => $letterHtml,
            ':image_url' => $imageUrl !== '' ? $imageUrl : null,
            ':id' => $campaignId,
          ]);
          mem_campaign_log($campaignId, 'campaign_updated', 'Campaign content updated');
          $_SESSION['mem_campaign_notice'] = 'Campaign saved.';
          header('Location: ' . $baseUrl . '?view=campaign&id=' . $campaignId);
          exit;
        }
      } elseif ($action === 'build_audience') {
        $existingStmt = $pdo->prepare('SELECT COUNT(*) FROM mem_campaign_recipient WHERE campaign_id = :id');
        $existingStmt->execute([':id' => $campaignId]);
        if ((int) $existingStmt->fetchColumn() > 0) {
          $errors[] = 'This campaign already has a frozen recipient list.';
        } else {
          $count = mem_campaign_build_recipients($campaignId);
          $pdo->prepare('UPDATE mem_campaign SET status = "ready", modified = NOW() WHERE id = :id')->execute([':id' => $campaignId]);
          $_SESSION['mem_campaign_notice'] = $count . ' recipient records created.';
          header('Location: ' . $baseUrl . '?view=campaign&id=' . $campaignId);
          exit;
        }
      } elseif ($action === 'send_test') {
        $testEmail = trim((string) ($_POST['test_email'] ?? ''));
        $campaignStmt = $pdo->prepare('SELECT * FROM mem_campaign WHERE id = :id LIMIT 1');
        $campaignStmt->execute([':id' => $campaignId]);
        $campaign = $campaignStmt->fetch(PDO::FETCH_ASSOC);
        if (!$campaign || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
          $errors[] = 'Enter a valid test email address.';
        } elseif ($campaignTestMode && !mem_campaign_test_email_allowed($testEmail)) {
          $errors[] = 'Development test emails can only be sent to an administrator email address.';
        } else {
          $sample = [
            'firstname' => 'Sam',
            'full_name' => 'Sam Rider',
            'membership_number' => 1024,
            'address1' => '1 Sample Road',
            'town' => 'Belfast',
            'postcode' => 'BT1 1AA',
            'quick_renew_url' => mem_base_url('/member-renew-quick.php?token=EXAMPLE'),
          ];
          $html = mem_campaign_render((string) $campaign['email_html'], $sample, $campaign);
          if (!empty($campaign['image_url'])) {
            $html = '<p><img src="' . mem_h((string) $campaign['image_url']) . '" alt="" style="max-width:100%;height:auto;"></p>' . $html;
          }
          if (mem_send_mail($testEmail, 'Test: ' . (string) $campaign['subject'], $html, mem_campaign_html_to_text($html))) {
            mem_campaign_log($campaignId, 'test_email_sent', 'Test sent to ' . $testEmail);
            $notices[] = 'Test email sent to ' . $testEmail . '.';
          } else {
            $errors[] = 'Test email failed to send.';
          }
        }
      } elseif ($action === 'send_email_batch') {
        $campaignStmt = $pdo->prepare('SELECT * FROM mem_campaign WHERE id = :id LIMIT 1');
        $campaignStmt->execute([':id' => $campaignId]);
        $campaign = $campaignStmt->fetch(PDO::FETCH_ASSOC);
        $recipientStmt = $pdo->prepare(
          'SELECT * FROM mem_campaign_recipient
           WHERE campaign_id = :id AND channel = "email" AND status IN ("pending", "failed")
             ' . ($campaignTestMode ? 'AND member_id IN (SELECT id FROM mem_member WHERE is_admin = 1)' : '') . '
           ORDER BY id LIMIT 25'
        );
        $recipientStmt->execute([':id' => $campaignId]);
        $recipients = $recipientStmt->fetchAll(PDO::FETCH_ASSOC);
        $sent = 0;
        $failed = 0;
        foreach ($recipients as $recipient) {
          if ($campaignTestMode && !mem_campaign_test_email_allowed((string) $recipient['email'])) {
            continue;
          }
          $html = mem_campaign_render((string) $campaign['email_html'], $recipient, $campaign);
          if (!empty($campaign['image_url'])) {
            $html = '<p><img src="' . mem_h((string) $campaign['image_url']) . '" alt="" style="max-width:100%;height:auto;"></p>' . $html;
          }
          $ok = mem_send_mail(
            (string) $recipient['email'],
            mem_campaign_render((string) $campaign['subject'], $recipient, $campaign),
            $html,
            mem_campaign_html_to_text($html)
          );
          $update = $pdo->prepare(
            'UPDATE mem_campaign_recipient
             SET status = :status, sent_at = :sent_at, last_error = :last_error, modified = NOW()
             WHERE id = :id'
          );
          $update->execute([
            ':status' => $ok ? 'sent' : 'failed',
            ':sent_at' => $ok ? date('Y-m-d H:i:s') : null,
            ':last_error' => $ok ? null : 'Mailer returned failure',
            ':id' => (int) $recipient['id'],
          ]);
          mem_campaign_log(
            $campaignId,
            $ok ? 'email_sent' : 'email_failed',
            $ok ? 'Email sent to ' . $recipient['email'] : 'Email failed for ' . $recipient['email'],
            (int) $recipient['id']
          );
          $ok ? $sent++ : $failed++;
        }
        $_SESSION['mem_campaign_notice'] = 'Email batch complete: ' . $sent . ' sent, ' . $failed . ' failed.';
        header('Location: ' . $baseUrl . '?view=campaign&id=' . $campaignId);
        exit;
      }
    }
  }
}

$campaign = null;
$recipients = [];
$activities = [];
$counts = [];
if ($campaignReady && $campaignId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM mem_campaign WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $campaignId]);
  $campaign = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  if (!$campaign) {
    $errors[] = 'Campaign not found.';
  } else {
    $recipientStmt = $pdo->prepare(
      'SELECT * FROM mem_campaign_recipient WHERE campaign_id = :id ORDER BY channel, membership_number'
    );
    $recipientStmt->execute([':id' => $campaignId]);
    $recipients = $recipientStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recipients as $recipient) {
      $key = (string) $recipient['channel'] . '_' . (string) $recipient['status'];
      $counts[$key] = ($counts[$key] ?? 0) + 1;
      $counts[(string) $recipient['channel']] = ($counts[(string) $recipient['channel']] ?? 0) + 1;
    }
    $activityStmt = $pdo->prepare(
      'SELECT a.*, CONCAT_WS(" ", m.firstname, m.surname) AS actor_name
       FROM mem_campaign_activity a
       LEFT JOIN mem_member m ON m.id = a.actor_member_id
       WHERE a.campaign_id = :id ORDER BY a.id DESC LIMIT 50'
    );
    $activityStmt->execute([':id' => $campaignId]);
    $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

if ($campaign && $output === 'csv') {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="campaign-' . $campaignId . '-recipients.csv"');
  $out = fopen('php://output', 'wb');
  fputcsv($out, ['Member Number', 'First Name', 'Surname', 'Email', 'Address 1', 'Address 2', 'Town', 'County', 'Country', 'Postcode', 'Channel', 'Status', 'Quick Renew URL', 'Quick Renew Code'], ',', '"', '');
  foreach ($recipients as $recipient) {
    fputcsv($out, [
      $recipient['membership_number'],
      $recipient['firstname'],
      $recipient['surname'],
      $recipient['email'],
      $recipient['address1'],
      $recipient['address2'],
      $recipient['town'],
      $recipient['county'],
      $recipient['country'],
      $recipient['postcode'],
      $recipient['channel'],
      $recipient['status'],
      $recipient['quick_renew_url'],
      $recipient['quick_renew_code'],
    ], ',', '"', '');
  }
  fclose($out);
  $pdo->prepare(
    'UPDATE mem_campaign_recipient SET status = "exported", exported_at = NOW(), modified = NOW()
     WHERE campaign_id = :id AND channel = "letter" AND status = "pending"'
  )->execute([':id' => $campaignId]);
  mem_campaign_log($campaignId, 'csv_exported', 'Recipient CSV exported');
  exit;
}

if ($campaign && $output === 'letters') {
  $stationery = mem_campaign_letter_stationery();
  $letterRecipients = array_values(array_filter(
    $recipients,
    static fn(array $recipient): bool => (string) $recipient['channel'] === 'letter'
  ));
  $testLetterPreview = false;
  if (!$letterRecipients && $campaignTestMode) {
    $letterRecipients = array_values(array_filter(
      $recipients,
      static fn(array $recipient): bool => (string) $recipient['channel'] !== 'excluded'
    ));
    $testLetterPreview = (bool) $letterRecipients;
  }
  $pdo->prepare(
    'UPDATE mem_campaign_recipient SET status = "printed", printed_at = NOW(), modified = NOW()
     WHERE campaign_id = :id AND channel = "letter" AND status IN ("pending", "exported")'
  )->execute([':id' => $campaignId]);
  mem_campaign_log(
    $campaignId,
    $testLetterPreview ? 'letter_test_preview_opened' : 'letters_opened',
    count($letterRecipients) . ($testLetterPreview ? ' test letters opened for preview' : ' letters opened for printing')
  );
  ?>
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?php echo mem_h((string) $campaign['name']); ?> Letters</title>
    <style>
      @page { size: A4 portrait; margin: 20mm; }
      body { margin: 0; color: #111; font: 11pt Arial, sans-serif; }
      .letter { break-after: page; page-break-after: always; }
      .letter:last-child { break-after: auto; page-break-after: auto; }
      .letterhead { display: flex; align-items: flex-start; justify-content: space-between; gap: 10mm; padding-bottom: 4mm; margin-bottom: 9mm; border-bottom: 1px solid #bbb; }
      .letterhead-logo { width: 30mm; max-height: 25mm; height: auto; object-fit: contain; object-position: left top; }
      .organisation { flex: 1; text-align: right; font-size: 8.5pt; line-height: 1.35; }
      .organisation-name { margin-bottom: 1mm; font-size: 12pt; font-weight: 700; }
      .organisation-address { white-space: normal; }
      .organisation-contact { margin-top: 1mm; }
      .address { margin: 0 0 14mm; line-height: 1.35; }
      .content { line-height: 1.5; }
      .renewal-access { display: flex; align-items: center; justify-content: space-between; gap: 8mm; margin-top: 8mm; padding: 4mm; border: 1px solid #999; background: #f7f7f7; break-inside: avoid; page-break-inside: avoid; }
      .renewal-details { flex: 1; }
      .renewal-code { margin-top: 2mm; font-size: 16pt; font-weight: 700; letter-spacing: 0.18em; }
      .letter-close { margin-top: 10mm; }
      .sign-off { line-height: 1.5; }
      .qr-code { width: 27mm; min-width: 27mm; height: 27mm; }
      .qr-code img, .qr-code canvas { width: 27mm !important; height: 27mm !important; }
      .tools { margin: 12px; }
      @media print { .tools { display: none; } }
    </style>
  </head>
  <body>
    <div class="tools"><button type="button" onclick="window.print()">Print Letters</button></div>
    <?php if ($testLetterPreview): ?>
      <div class="tools" style="padding:10px;background:#fff3cd;border:1px solid #ffe69c;">
        Development test preview using administrator recipients. No recipient status has been changed.
      </div>
    <?php endif; ?>
    <?php if (!$letterRecipients): ?>
      <div class="tools" style="padding:16px;border:1px solid #ccc;">
        No letter recipients are available. Build the campaign audience first, or check that members without email have postal addresses.
      </div>
    <?php endif; ?>
    <?php foreach ($letterRecipients as $recipient): ?>
      <section class="letter">
        <header class="letterhead">
          <div>
            <?php if ((string) ($stationery['logo_url'] ?? '') !== ''): ?>
              <img class="letterhead-logo" src="<?php echo mem_h((string) $stationery['logo_url']); ?>" alt="<?php echo mem_h((string) $stationery['company']); ?> logo">
            <?php endif; ?>
          </div>
          <div class="organisation">
            <div class="organisation-name"><?php echo mem_h((string) $stationery['company']); ?></div>
            <div class="organisation-address"><?php echo mem_h(implode(', ', (array) $stationery['address'])); ?></div>
            <div class="organisation-contact">
              <?php if ((string) ($stationery['email'] ?? '') !== ''): ?>
                <?php echo mem_h((string) $stationery['email']); ?>
              <?php endif; ?>
              <?php if ((string) ($stationery['email'] ?? '') !== '' && (string) ($stationery['website'] ?? '') !== ''): ?>
                &nbsp; | &nbsp;
              <?php endif; ?>
              <?php echo mem_h((string) $stationery['website']); ?>
            </div>
          </div>
        </header>
        <div class="address">
          <?php foreach (['full_name', 'address1', 'address2', 'town', 'county', 'postcode', 'country'] as $field): ?>
            <?php if (trim((string) ($recipient[$field] ?? '')) !== ''): ?>
              <div><?php echo mem_h((string) $recipient[$field]); ?></div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <?php if (!empty($campaign['image_url'])): ?>
          <p><img src="<?php echo mem_h((string) $campaign['image_url']); ?>" alt="" style="max-width:100%;max-height:55mm;width:auto;"></p>
        <?php endif; ?>
        <div class="content"><?php echo mem_campaign_render((string) $campaign['letter_html'], $recipient, $campaign, 'letter'); ?></div>
        <?php if ((string) $campaign['campaign_type'] === 'renewal' && (string) ($recipient['quick_renew_code'] ?? '') !== ''): ?>
          <div class="renewal-access">
            <div class="renewal-details">
              <strong>Renew online:</strong>
              Visit <?php echo mem_h(mem_base_url('/member-renew-code.php')); ?> and enter this code:
              <div class="renewal-code"><?php echo mem_h((string) $recipient['quick_renew_code']); ?></div>
            </div>
            <?php if ((string) ($recipient['quick_renew_url'] ?? '') !== ''): ?>
              <div
                class="qr-code"
                data-qr-url="<?php echo mem_h(mem_campaign_absolute_url((string) $recipient['quick_renew_url'])); ?>"
                aria-label="QR code for online membership renewal"
              ></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <div class="letter-close">
          <div class="sign-off">
            Kind regards<br>
            <strong><?php echo mem_h((string) $stationery['company']); ?></strong>
          </div>
        </div>
      </section>
    <?php endforeach; ?>
    <?php if ($letterRecipients): ?>
      <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
      <script>
        window.addEventListener('load', function () {
          document.querySelectorAll('[data-qr-url]').forEach(function (element) {
            if (typeof QRCode !== 'undefined') {
              new QRCode(element, {
                text: element.getAttribute('data-qr-url'),
                width: 128,
                height: 128,
                correctLevel: QRCode.CorrectLevel.M
              });
            }
          });
          window.setTimeout(function () {
            window.print();
          }, 350);
        });
      </script>
    <?php endif; ?>
  </body>
  </html>
  <?php
  exit;
}

$campaigns = [];
$yearOptions = [];
if ($campaignReady) {
  $campaigns = $pdo->query(
    'SELECT c.*,
       (SELECT COUNT(*) FROM mem_campaign_recipient r WHERE r.campaign_id = c.id) AS recipient_count
     FROM mem_campaign c
     WHERE c.status <> "archived"
     ORDER BY c.id DESC'
  )->fetchAll(PDO::FETCH_ASSOC);
  if (mem_table_exists('mem_membership_year')) {
    $yearOptions = array_map('intval', $pdo->query(
      'SELECT DISTINCT membership_year FROM mem_membership_year WHERE archived = 0 ORDER BY membership_year DESC'
    )->fetchAll(PDO::FETCH_COLUMN));
  }
}
if (!$yearOptions) {
  $yearOptions = [(int) date('Y'), (int) date('Y') - 1];
}

mem_page_header('UGPSC Admin | Lists & Campaigns', ['active' => 'admin']);
?>
<div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo mem_h($adminDashboard); ?>">Admin Dashboard</a>
    <span class="badge text-bg-light">Admin</span>
  </div>

  <div class="mem-card p-4 p-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div>
        <h1 class="display-font h3 mb-1">Lists &amp; Campaigns</h1>
        <p class="text-secondary mb-0">Simple personalised email and printed-letter campaigns for members.</p>
      </div>
      <?php if ($view !== 'new'): ?>
        <a class="btn btn-card-action" href="<?php echo mem_h($baseUrl . '?view=new'); ?>">New Campaign</a>
      <?php endif; ?>
    </div>

    <?php if ($errors): ?><div class="alert alert-danger"><?php echo mem_h(implode(' ', $errors)); ?></div><?php endif; ?>
    <?php if ($notices): ?><div class="alert alert-success"><?php echo mem_h(implode(' ', $notices)); ?></div><?php endif; ?>
    <?php if ($campaignTestMode): ?>
      <div class="alert alert-warning" role="status">
        <strong>Development test mode:</strong> campaign audiences and all email sends are restricted to administrator accounts.
        <?php if ($campaignTestEmails): ?>
          Allowed email<?php echo count($campaignTestEmails) === 1 ? '' : 's'; ?>:
          <?php echo mem_h(implode(', ', $campaignTestEmails)); ?>.
        <?php else: ?>
          No administrator email address is currently available, so campaign email sending is blocked.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($campaignReady && $view === 'new'): ?>
      <form method="post" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
        <input type="hidden" name="action" value="create_campaign">
        <div class="col-md-4">
          <label class="form-label mem-label" for="campaign_type">Campaign Type</label>
          <select class="form-select" id="campaign_type" name="campaign_type">
            <option value="renewal">Membership Renewal</option>
            <option value="information">Member Information</option>
            <option value="marketing">Marketing (consent required)</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label mem-label" for="name">Campaign Name</label>
          <input class="form-control" id="name" name="name" required placeholder="e.g. 2027 Membership Renewal">
        </div>
        <div class="col-md-3">
          <label class="form-label mem-label" for="membership_year">Audience Year</label>
          <select class="form-select" id="membership_year" name="membership_year">
            <?php foreach ($yearOptions as $year): ?>
              <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label mem-label" for="subject">Email Subject</label>
          <input class="form-control" id="subject" name="subject" required value="Membership update from {{site_name}}">
        </div>
        <div class="col-lg-6">
          <label class="form-label mem-label" for="email_html">Email Content</label>
          <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Email formatting">
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="email-editor" data-command="bold"><strong>B</strong></button>
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="email-editor" data-command="italic"><em>I</em></button>
            <button class="btn btn-outline-secondary rich-block" type="button" data-editor="email-editor" data-block="h2">Heading</button>
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="email-editor" data-command="insertUnorderedList">Bullets</button>
            <button class="btn btn-outline-secondary rich-link" type="button" data-editor="email-editor">Link</button>
          </div>
          <div class="form-control rich-editor" id="email-editor" contenteditable="true" data-output="email_html" style="min-height:260px;"><?php echo $defaultRenewalEmail; ?></div>
          <textarea class="d-none" id="email_html" name="email_html"><?php echo mem_h($defaultRenewalEmail); ?></textarea>
        </div>
        <div class="col-lg-6">
          <label class="form-label mem-label" for="letter_html">Printed Letter Content</label>
          <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Letter formatting">
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="letter-editor" data-command="bold"><strong>B</strong></button>
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="letter-editor" data-command="italic"><em>I</em></button>
            <button class="btn btn-outline-secondary rich-block" type="button" data-editor="letter-editor" data-block="h2">Heading</button>
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="letter-editor" data-command="insertUnorderedList">Bullets</button>
            <button class="btn btn-outline-secondary rich-link" type="button" data-editor="letter-editor">Link</button>
          </div>
          <div class="form-control rich-editor" id="letter-editor" contenteditable="true" data-output="letter_html" style="min-height:260px;"><?php echo $defaultRenewalLetter; ?></div>
          <textarea class="d-none" id="letter_html" name="letter_html"><?php echo mem_h($defaultRenewalLetter); ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label mem-label" for="image_url">Optional Image URL</label>
          <input class="form-control" type="url" id="image_url" name="image_url" placeholder="https://...">
          <div class="small text-secondary mt-1">Allowed formatting: headings, paragraphs, bold, italic, links and lists.</div>
          <div class="small text-secondary">Merge tags: {{first_name}}, {{full_name}}, {{member_number}}, {{address}}, {{year}}, {{site_url}}, {{site_name}}, {{quick_renew_link}}, {{quick_renew_code}}</div>
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-card-action" type="submit">Create Draft</button>
          <a class="btn btn-outline-secondary" href="<?php echo mem_h($baseUrl); ?>">Cancel</a>
        </div>
      </form>
    <?php elseif ($campaignReady && $view === 'campaign' && $campaign): ?>
      <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div>
          <div class="mem-label"><?php echo mem_h(ucfirst((string) $campaign['campaign_type'])); ?> Campaign</div>
          <h2 class="h4 mb-1"><?php echo mem_h((string) $campaign['name']); ?></h2>
          <div class="small text-secondary">Status: <?php echo mem_h((string) $campaign['status']); ?> · Audience year: <?php echo (int) ($campaign['membership_year'] ?? 0); ?></div>
        </div>
        <a class="btn btn-outline-secondary btn-sm align-self-start" href="<?php echo mem_h($baseUrl); ?>">All Campaigns</a>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="p-3 border rounded"><div class="mem-label">Email</div><div class="fs-4 fw-bold"><?php echo (int) ($counts['email'] ?? 0); ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="p-3 border rounded"><div class="mem-label">Letters</div><div class="fs-4 fw-bold"><?php echo (int) ($counts['letter'] ?? 0); ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="p-3 border rounded"><div class="mem-label">Sent</div><div class="fs-4 fw-bold"><?php echo (int) ($counts['email_sent'] ?? 0); ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="p-3 border rounded"><div class="mem-label">Excluded</div><div class="fs-4 fw-bold"><?php echo (int) ($counts['excluded'] ?? 0); ?></div></div></div>
      </div>

      <form method="post" class="row g-3 mb-4">
        <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
        <input type="hidden" name="action" value="save_campaign">
        <div class="col-md-6">
          <label class="form-label mem-label" for="name">Campaign Name</label>
          <input class="form-control" id="name" name="name" value="<?php echo mem_h((string) $campaign['name']); ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label mem-label" for="subject">Email Subject</label>
          <input class="form-control" id="subject" name="subject" value="<?php echo mem_h((string) $campaign['subject']); ?>" required>
        </div>
        <div class="col-lg-6">
          <label class="form-label mem-label" for="email_html">Email HTML</label>
          <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Email formatting">
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="email-editor" data-command="bold"><strong>B</strong></button>
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="email-editor" data-command="italic"><em>I</em></button>
            <button class="btn btn-outline-secondary rich-block" type="button" data-editor="email-editor" data-block="h2">Heading</button>
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="email-editor" data-command="insertUnorderedList">Bullets</button>
            <button class="btn btn-outline-secondary rich-link" type="button" data-editor="email-editor">Link</button>
          </div>
          <div class="form-control rich-editor" id="email-editor" contenteditable="true" data-output="email_html" style="min-height:240px;"><?php echo (string) $campaign['email_html']; ?></div>
          <textarea class="d-none" id="email_html" name="email_html"><?php echo mem_h((string) $campaign['email_html']); ?></textarea>
        </div>
        <div class="col-lg-6">
          <label class="form-label mem-label" for="letter_html">Letter HTML</label>
          <div class="btn-group btn-group-sm mb-2" role="group" aria-label="Letter formatting">
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="letter-editor" data-command="bold"><strong>B</strong></button>
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="letter-editor" data-command="italic"><em>I</em></button>
            <button class="btn btn-outline-secondary rich-block" type="button" data-editor="letter-editor" data-block="h2">Heading</button>
            <button class="btn btn-outline-secondary rich-command" type="button" data-editor="letter-editor" data-command="insertUnorderedList">Bullets</button>
            <button class="btn btn-outline-secondary rich-link" type="button" data-editor="letter-editor">Link</button>
          </div>
          <div class="form-control rich-editor" id="letter-editor" contenteditable="true" data-output="letter_html" style="min-height:240px;"><?php echo (string) $campaign['letter_html']; ?></div>
          <textarea class="d-none" id="letter_html" name="letter_html"><?php echo mem_h((string) $campaign['letter_html']); ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label mem-label" for="image_url">Optional Image URL</label>
          <input class="form-control" type="url" id="image_url" name="image_url" value="<?php echo mem_h((string) ($campaign['image_url'] ?? '')); ?>">
        </div>
        <div class="col-12"><button class="btn btn-card-action" type="submit">Save Campaign</button></div>
      </form>

      <?php if (!$recipients): ?>
        <div class="p-3 border rounded bg-light-subtle mb-4">
          <h3 class="h6">Create Recipient List</h3>
          <p class="small text-secondary">This freezes member names, addresses, email channels and renewal links for a clear audit trail.</p>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
            <input type="hidden" name="action" value="build_audience">
            <button class="btn btn-card-action" type="submit">Build Audience</button>
          </form>
        </div>
      <?php else: ?>
        <div class="d-flex flex-wrap gap-2 mb-4">
          <form method="post" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
            <input type="hidden" name="action" value="send_test">
            <select class="form-select form-select-sm" name="test_email" required <?php echo $campaignTestMode && !$campaignTestEmails ? 'disabled' : ''; ?>>
              <option value="">Test email...</option>
              <?php if ($campaignTestMode): ?>
                <?php foreach ($campaignTestEmails as $testEmail): ?>
                  <option value="<?php echo mem_h($testEmail); ?>"><?php echo mem_h($testEmail); ?></option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="<?php echo mem_h((string) ($memberSession['email'] ?? '')); ?>"><?php echo mem_h((string) ($memberSession['email'] ?? 'Administrator')); ?></option>
              <?php endif; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Send Test</button>
          </form>
          <form method="post" onsubmit="return confirm('Send the next batch of up to 25 pending emails?');">
            <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
            <input type="hidden" name="action" value="send_email_batch">
            <button class="btn btn-sm btn-card-action" type="submit" <?php echo (int) (($counts['email_pending'] ?? 0) + ($counts['email_failed'] ?? 0)) === 0 || ($campaignTestMode && !$campaignTestEmails) ? 'disabled' : ''; ?>>
              <?php echo $campaignTestMode ? 'Send Admin Test Batch' : 'Send Next Email Batch'; ?>
            </button>
          </form>
          <a class="btn btn-sm btn-card-action" target="_blank" href="<?php echo mem_h($baseUrl . '?view=campaign&id=' . $campaignId . '&output=letters'); ?>">Print Letters</a>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo mem_h($baseUrl . '?view=campaign&id=' . $campaignId . '&output=csv'); ?>">Export CSV</a>
        </div>

        <ul class="nav nav-tabs mb-3" role="tablist">
          <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#recipients" type="button">Recipients</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#preview" type="button">Preview</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#activity" type="button">Activity</button></li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="recipients">
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead><tr><th>Member</th><th>Name</th><th>Channel</th><th>Email / Postcode</th><th>Status</th><th>Reason / Error</th></tr></thead>
                <tbody>
                  <?php foreach ($recipients as $recipient): ?>
                    <tr>
                      <td>#<?php echo (int) $recipient['membership_number']; ?></td>
                      <td><?php echo mem_h((string) $recipient['full_name']); ?></td>
                      <td class="text-capitalize"><?php echo mem_h((string) $recipient['channel']); ?></td>
                      <td><?php echo mem_h((string) ($recipient['channel'] === 'email' ? $recipient['email'] : $recipient['postcode'])); ?></td>
                      <td class="text-capitalize"><?php echo mem_h((string) $recipient['status']); ?></td>
                      <td class="small text-secondary"><?php echo mem_h((string) ($recipient['exclusion_reason'] ?? $recipient['last_error'] ?? '')); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="tab-pane fade" id="preview">
            <?php
              $sample = $recipients[0] ?? [
                'firstname' => 'Sam', 'full_name' => 'Sam Rider', 'membership_number' => 1024,
                'quick_renew_url' => mem_base_url('/member-renew-quick.php?token=EXAMPLE'),
              ];
            ?>
            <div class="row g-3">
              <div class="col-lg-6"><div class="mem-label mb-2">Email</div><div class="p-4 border rounded"><?php echo mem_campaign_render((string) $campaign['email_html'], $sample, $campaign); ?></div></div>
              <div class="col-lg-6"><div class="mem-label mb-2">Letter</div><div class="p-4 border rounded"><?php echo mem_campaign_render((string) $campaign['letter_html'], $sample, $campaign, 'letter'); ?></div></div>
            </div>
          </div>
          <div class="tab-pane fade" id="activity">
            <?php if ($activities): ?>
              <table class="table table-sm"><thead><tr><th>Date</th><th>Event</th><th>Message</th><th>Administrator</th></tr></thead><tbody>
                <?php foreach ($activities as $activity): ?><tr>
                  <td><?php echo mem_h(mem_format_date_uk((string) $activity['created'])); ?></td>
                  <td><?php echo mem_h((string) $activity['event_type']); ?></td>
                  <td><?php echo mem_h((string) $activity['message']); ?></td>
                  <td><?php echo mem_h((string) $activity['actor_name']); ?></td>
                </tr><?php endforeach; ?>
              </tbody></table>
            <?php else: ?><p class="text-secondary">No activity yet.</p><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php elseif ($campaignReady): ?>
      <?php if ($campaigns): ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Campaign</th><th>Type</th><th>Year</th><th>Status</th><th>Recipients</th><th>Created</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($campaigns as $row): ?><tr>
                <td class="fw-semibold"><?php echo mem_h((string) $row['name']); ?></td>
                <td class="text-capitalize"><?php echo mem_h((string) $row['campaign_type']); ?></td>
                <td><?php echo (int) ($row['membership_year'] ?? 0); ?></td>
                <td class="text-capitalize"><?php echo mem_h((string) $row['status']); ?></td>
                <td><?php echo (int) $row['recipient_count']; ?></td>
                <td><?php echo mem_h(mem_format_date_uk((string) $row['created'])); ?></td>
                <td><a class="btn btn-sm btn-card-action" href="<?php echo mem_h($baseUrl . '?view=campaign&id=' . (int) $row['id']); ?>">Open</a></td>
              </tr><?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-light border text-secondary">No campaigns yet. Create the first draft to begin.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    function editorFor(button) {
      return document.getElementById(button.getAttribute('data-editor'));
    }

    document.querySelectorAll('.rich-command').forEach(function (button) {
      button.addEventListener('click', function () {
        var editor = editorFor(button);
        if (!editor) return;
        editor.focus();
        document.execCommand(button.getAttribute('data-command'), false, null);
      });
    });

    document.querySelectorAll('.rich-block').forEach(function (button) {
      button.addEventListener('click', function () {
        var editor = editorFor(button);
        if (!editor) return;
        editor.focus();
        document.execCommand('formatBlock', false, button.getAttribute('data-block'));
      });
    });

    document.querySelectorAll('.rich-link').forEach(function (button) {
      button.addEventListener('click', function () {
        var editor = editorFor(button);
        if (!editor) return;
        var url = window.prompt('Enter the link address, including https://');
        if (!url) return;
        editor.focus();
        document.execCommand('createLink', false, url);
      });
    });

    document.querySelectorAll('form').forEach(function (form) {
      form.addEventListener('submit', function () {
        form.querySelectorAll('.rich-editor').forEach(function (editor) {
          var output = document.getElementById(editor.getAttribute('data-output'));
          if (output) {
            output.value = editor.innerHTML;
          }
        });
      });
    });
  });
</script>
<?php mem_page_footer(); ?>
