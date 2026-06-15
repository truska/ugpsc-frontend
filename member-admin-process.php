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

$baseUrl = mem_base_url('/member-admin-process.php');
$adminDashboard = mem_base_url('/member-admin-dashboard.php');
$statusFilter = strtolower(trim((string) ($_GET['status'] ?? 'pending')));
$typeFilter = strtolower(trim((string) ($_GET['type'] ?? '')));
$search = trim((string) ($_GET['q'] ?? ''));
$labelId = max(0, (int) ($_GET['label'] ?? 0));
$printList = (string) ($_GET['print'] ?? '') === '1';
$validStatuses = ['pending', 'processed', 'all'];
$validTypes = ['', 'join', 'renewal'];
if (!in_array($statusFilter, $validStatuses, true)) {
  $statusFilter = 'pending';
}
if (!in_array($typeFilter, $validTypes, true)) {
  $typeFilter = '';
}

$queryParams = [
  'status' => $statusFilter,
  'type' => $typeFilter,
  'q' => $search,
];
$returnUrl = $baseUrl . '?' . http_build_query(array_filter(
  $queryParams,
  static fn(string $value): bool => $value !== ''
));
$printUrl = $baseUrl . '?' . http_build_query(array_merge(
  array_filter($queryParams, static fn(string $value): bool => $value !== ''),
  ['print' => '1']
));

$errors = [];
$notices = [];
if (!empty($_SESSION['mem_process_notice'])) {
  $notices[] = (string) $_SESSION['mem_process_notice'];
  unset($_SESSION['mem_process_notice']);
}
$items = [];
$counts = [
  'pending' => 0,
  'processed' => 0,
];

global $pdo, $DB_OK;
$queueReady = $DB_OK && ($pdo instanceof PDO) && mem_table_exists('mem_fulfilment');

if ($labelId > 0) {
  if (!$queueReady) {
    http_response_code(503);
    exit('Membership processing queue is not installed.');
  }

  $labelStmt = $pdo->prepare(
    'SELECT id, membership_number, recipient_name, address1, address2, town, county, country, postcode
     FROM mem_fulfilment
     WHERE id = :id AND archived = 0
     LIMIT 1'
  );
  $labelStmt->execute([':id' => $labelId]);
  $label = $labelStmt->fetch(PDO::FETCH_ASSOC);
  if (!$label) {
    http_response_code(404);
    exit('Label not found.');
  }

  $labelLines = array_values(array_filter([
    trim((string) ($label['recipient_name'] ?? '')),
    trim((string) ($label['address1'] ?? '')),
    trim((string) ($label['address2'] ?? '')),
    trim((string) ($label['town'] ?? '')),
    trim((string) ($label['county'] ?? '')),
    trim((string) ($label['postcode'] ?? '')),
    trim((string) ($label['country'] ?? '')),
  ], static fn(string $line): bool => $line !== ''));
  ?>
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Address Label - Member <?php echo (int) ($label['membership_number'] ?? 0); ?></title>
    <style>
      @page {
        size: A4 portrait;
        margin: 10mm;
      }
      * {
        box-sizing: border-box;
      }
      body {
        margin: 0;
        background: #eef0f1;
        color: #111;
        font-family: Arial, sans-serif;
      }
      .label {
        position: relative;
        width: 89mm;
        height: 36mm;
        padding: 5mm 7mm 8mm;
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow: hidden;
        break-inside: avoid;
        page-break-inside: avoid;
      }
      .recipient {
        font-size: 12pt;
        font-weight: 700;
        margin-bottom: 1mm;
      }
      .address-line {
        font-size: 10.5pt;
        line-height: 1.25;
      }
      .member-number {
        position: absolute;
        right: 4mm;
        bottom: 3mm;
        color: #555;
        font-size: 8pt;
      }
      .print-tools {
        padding: 12px;
      }
      @media print {
        body {
          background: #fff;
        }
        .print-tools {
          display: none;
        }
        .label {
          margin: 0;
        }
      }
    </style>
  </head>
  <body>
    <div class="print-tools">
      <button type="button" onclick="window.print()">Print Label</button>
    </div>
    <div class="label">
      <?php foreach ($labelLines as $index => $line): ?>
        <div class="<?php echo $index === 0 ? 'recipient' : 'address-line'; ?>"><?php echo mem_h($line); ?></div>
      <?php endforeach; ?>
      <div class="member-number">#<?php echo (int) ($label['membership_number'] ?? 0); ?></div>
    </div>
    <script>
      window.addEventListener('load', function () {
        window.print();
      });
    </script>
  </body>
  </html>
  <?php
  exit;
}

if (!$DB_OK || !($pdo instanceof PDO)) {
  $errors[] = 'Database unavailable.';
} elseif (!$queueReady) {
  $errors[] = 'The membership processing queue is not installed yet.';
} else {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    if (!mem_verify_csrf($csrf)) {
      $errors[] = 'Session check failed. Please try again.';
    } else {
      $action = (string) ($_POST['action'] ?? '');
      if ($action === 'set_processed') {
        $fulfilmentId = max(0, (int) ($_POST['fulfilment_id'] ?? 0));
        $processed = (string) ($_POST['processed'] ?? '') === '1';
        if ($fulfilmentId <= 0) {
          $errors[] = 'No processing record selected.';
        } else {
          $updateSql = $processed
            ? 'UPDATE mem_fulfilment
               SET status = "processed", processed_at = NOW(), processed_by_member_id = :admin_id, modified = NOW()
               WHERE id = :id AND archived = 0
               LIMIT 1'
            : 'UPDATE mem_fulfilment
               SET status = "pending", processed_at = NULL, processed_by_member_id = NULL, modified = NOW()
               WHERE id = :id AND archived = 0
               LIMIT 1';
          $params = [
            ':id' => $fulfilmentId,
          ];
          if ($processed) {
            $params[':admin_id'] = (int) ($memberSession['id'] ?? 0);
          }
          $stmt = $pdo->prepare($updateSql);
          $stmt->execute($params);
          if ($stmt->rowCount() > 0) {
            $event = $processed ? 'membership_pack_processed' : 'membership_pack_reopened';
            $summary = $processed ? 'Membership pack marked processed' : 'Membership pack returned to pending';
            mem_log_event($event, $summary . ' (queue #' . $fulfilmentId . ')');
            mem_log_change($event, $summary, 'mem_fulfilment.id=' . $fulfilmentId);
            $_SESSION['mem_process_notice'] = $summary . '.';
            header('Location: ' . $returnUrl);
            exit;
          }
        }
      } elseif ($action === 'bulk_process') {
        $fulfilmentIds = array_values(array_unique(array_filter(
          array_map('intval', (array) ($_POST['fulfilment_ids'] ?? [])),
          static fn(int $id): bool => $id > 0
        )));
        if (!$fulfilmentIds) {
          $errors[] = 'Select at least one pending pack to process.';
        } else {
          $placeholders = [];
          $params = [
            ':admin_id' => (int) ($memberSession['id'] ?? 0),
          ];
          foreach ($fulfilmentIds as $index => $fulfilmentId) {
            $key = ':id_' . $index;
            $placeholders[] = $key;
            $params[$key] = $fulfilmentId;
          }
          $bulkSql = 'UPDATE mem_fulfilment
                      SET status = "processed",
                          processed_at = NOW(),
                          processed_by_member_id = :admin_id,
                          modified = NOW()
                      WHERE archived = 0
                        AND status = "pending"
                        AND id IN (' . implode(', ', $placeholders) . ')';
          $stmt = $pdo->prepare($bulkSql);
          $stmt->execute($params);
          $processedCount = $stmt->rowCount();
          if ($processedCount > 0) {
            $summary = $processedCount . ' membership pack' . ($processedCount === 1 ? '' : 's') . ' marked processed';
            mem_log_event('membership_packs_bulk_processed', $summary);
            mem_log_change('membership_packs_bulk_processed', $summary, 'mem_fulfilment.ids=' . implode(',', $fulfilmentIds));
            $_SESSION['mem_process_notice'] = $summary . '.';
            header('Location: ' . $returnUrl);
            exit;
          }
          $errors[] = 'The selected packs were already processed or unavailable.';
        }
      }
    }
  }

  $countStmt = $pdo->query(
    'SELECT status, COUNT(*) AS total
     FROM mem_fulfilment
     WHERE archived = 0
     GROUP BY status'
  );
  foreach ($countStmt->fetchAll(PDO::FETCH_ASSOC) as $countRow) {
    $countStatus = (string) ($countRow['status'] ?? '');
    if (array_key_exists($countStatus, $counts)) {
      $counts[$countStatus] = (int) ($countRow['total'] ?? 0);
    }
  }

  $where = ['f.archived = 0'];
  $params = [];
  if ($statusFilter !== 'all') {
    $where[] = 'f.status = :status';
    $params[':status'] = $statusFilter;
  }
  if ($typeFilter !== '') {
    $where[] = 'f.fulfilment_type = :type';
    $params[':type'] = $typeFilter;
  }
  if ($search !== '') {
    $where[] = '(f.membership_number = :member_number
                 OR LOWER(f.recipient_name) LIKE :search
                 OR LOWER(f.email) LIKE :search
                 OR LOWER(f.address1) LIKE :search
                 OR LOWER(f.address2) LIKE :search
                 OR LOWER(f.town) LIKE :search
                 OR LOWER(f.county) LIKE :search
                 OR LOWER(f.country) LIKE :search
                 OR LOWER(f.postcode) LIKE :search)';
    $params[':member_number'] = ctype_digit($search) ? (int) $search : 0;
    $params[':search'] = '%' . strtolower($search) . '%';
  }

  $listSql = 'SELECT
                f.*,
                t.paid_at,
                processor.firstname AS processed_by_firstname,
                processor.surname AS processed_by_surname
              FROM mem_fulfilment f
              JOIN mem_transaction t ON t.id = f.transaction_id
              LEFT JOIN mem_member processor ON processor.id = f.processed_by_member_id
              WHERE ' . implode(' AND ', $where) . '
              ORDER BY
                CASE WHEN f.status = "pending" THEN 0 ELSE 1 END,
                f.queued_at ASC,
                f.id ASC
              LIMIT 500';
  $listStmt = $pdo->prepare($listSql);
  $listStmt->execute($params);
  $items = $listStmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($printList && $queueReady) {
  ?>
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Membership Pack Processing List</title>
    <style>
      body {
        margin: 20px;
        color: #111;
        font-family: Arial, sans-serif;
        font-size: 10pt;
      }
      h1 {
        margin: 0 0 4px;
        font-size: 18pt;
      }
      .summary {
        margin-bottom: 16px;
        color: #555;
      }
      table {
        width: 100%;
        border-collapse: collapse;
      }
      th,
      td {
        padding: 7px;
        border: 1px solid #aaa;
        text-align: left;
        vertical-align: top;
      }
      th {
        background: #eee;
      }
      .check {
        width: 28px;
        text-align: center;
      }
      .print-tools {
        margin-bottom: 14px;
      }
      @media print {
        .print-tools {
          display: none;
        }
      }
    </style>
  </head>
  <body>
    <div class="print-tools">
      <button type="button" onclick="window.print()">Print</button>
    </div>
    <h1>Membership Pack Processing List</h1>
    <div class="summary">
      <?php echo mem_h(ucfirst($statusFilter)); ?>
      &middot; <?php echo $typeFilter === 'join' ? 'New members' : ($typeFilter === 'renewal' ? 'Renewals' : 'New and renewals'); ?>
      &middot; <?php echo count($items); ?> record<?php echo count($items) === 1 ? '' : 's'; ?>
      &middot; <?php echo mem_h(date('d/m/Y H:i')); ?>
    </div>
    <?php if ($items): ?>
      <table>
        <thead>
          <tr>
            <th class="check">Done</th>
            <th>Member</th>
            <th>Name</th>
            <th>Email</th>
            <th>Address</th>
            <th>Type</th>
            <th>Queued</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <?php
              $printAddress = array_values(array_filter([
                trim((string) ($item['address1'] ?? '')),
                trim((string) ($item['address2'] ?? '')),
                trim((string) ($item['town'] ?? '')),
                trim((string) ($item['county'] ?? '')),
                trim((string) ($item['postcode'] ?? '')),
                trim((string) ($item['country'] ?? '')),
              ], static fn(string $line): bool => $line !== ''));
            ?>
            <tr>
              <td class="check"><?php echo (string) ($item['status'] ?? '') === 'processed' ? '&#9745;' : '&#9744;'; ?></td>
              <td>#<?php echo (int) ($item['membership_number'] ?? 0); ?></td>
              <td><?php echo mem_h((string) ($item['recipient_name'] ?? '')); ?></td>
              <td><?php echo mem_h((string) ($item['email'] ?? '')); ?></td>
              <td><?php echo mem_h(implode(', ', $printAddress)); ?></td>
              <td><?php echo (string) ($item['fulfilment_type'] ?? '') === 'join' ? 'New' : 'Renew'; ?></td>
              <td><?php echo mem_h(mem_format_date_uk((string) ($item['queued_at'] ?? $item['paid_at'] ?? ''))); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No membership packs match these filters.</p>
    <?php endif; ?>
  </body>
  </html>
  <?php
  exit;
}

mem_page_header('UGPSC Admin | Process Membership Packs', ['active' => 'admin']);
?>
<div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo mem_h($adminDashboard); ?>">Admin Dashboard</a>
    <span class="badge text-bg-light">Admin</span>
  </div>

  <div class="mem-card p-4 p-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div>
        <h1 class="display-font h3 mb-1">Process Membership Packs</h1>
        <p class="text-secondary mb-0">Print an address label, prepare the pack, then mark it processed.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <span class="badge bg-danger-subtle text-danger-emphasis">Pending: <?php echo (int) $counts['pending']; ?></span>
        <span class="badge bg-success-subtle text-success-emphasis">Processed: <?php echo (int) $counts['processed']; ?></span>
      </div>
    </div>

    <?php if ($notices): ?>
      <div class="alert alert-success" role="alert"><?php echo mem_h(implode(' ', $notices)); ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h(implode(' ', $errors)); ?></div>
    <?php endif; ?>

    <?php if ($queueReady): ?>
      <form method="get" action="<?php echo mem_h($baseUrl); ?>" class="row g-3 align-items-end mb-4">
        <div class="col-sm-6 col-lg-3">
          <label class="form-label mem-label mb-1" for="status">Status</label>
          <select class="form-select" id="status" name="status">
            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="processed" <?php echo $statusFilter === 'processed' ? 'selected' : ''; ?>>Processed</option>
            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
          </select>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label mem-label mb-1" for="type">Type</label>
          <select class="form-select" id="type" name="type">
            <option value="" <?php echo $typeFilter === '' ? 'selected' : ''; ?>>New and Renewals</option>
            <option value="join" <?php echo $typeFilter === 'join' ? 'selected' : ''; ?>>New Members</option>
            <option value="renewal" <?php echo $typeFilter === 'renewal' ? 'selected' : ''; ?>>Renewals</option>
          </select>
        </div>
        <div class="col-lg-4">
          <label class="form-label mem-label mb-1" for="q">Member or address</label>
          <input class="form-control" type="search" id="q" name="q" value="<?php echo mem_h($search); ?>" placeholder="Number, name, postcode...">
        </div>
        <div class="col-lg-2 d-flex flex-wrap gap-2">
          <button class="btn btn-card-action flex-grow-1" type="submit">Filter</button>
          <a class="btn btn-outline-secondary" href="<?php echo mem_h($baseUrl); ?>">Reset</a>
          <a class="btn btn-outline-secondary flex-grow-1" target="_blank" rel="noopener" href="<?php echo mem_h($printUrl); ?>">Print</a>
        </div>
      </form>

      <?php if ($items): ?>
        <?php
          $selectableCount = count(array_filter(
            $items,
            static fn(array $item): bool => (string) ($item['status'] ?? '') === 'pending'
          ));
        ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th scope="col">Select</th>
                <th scope="col">Member</th>
                <th scope="col">Name, Email &amp; Address</th>
                <th scope="col">Type</th>
                <th scope="col">Queued</th>
                <th scope="col">Actions</th>
                <th scope="col">Processed</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <?php
                  $isProcessed = (string) ($item['status'] ?? '') === 'processed';
                  $addressLines = array_values(array_filter([
                    trim((string) ($item['address1'] ?? '')),
                    trim((string) ($item['address2'] ?? '')),
                    trim((string) ($item['town'] ?? '')),
                    trim((string) ($item['county'] ?? '')),
                    trim((string) ($item['postcode'] ?? '')),
                    trim((string) ($item['country'] ?? '')),
                  ], static fn(string $line): bool => $line !== ''));
                  $processedBy = trim(
                    (string) ($item['processed_by_firstname'] ?? '')
                    . ' '
                    . (string) ($item['processed_by_surname'] ?? '')
                  );
                ?>
                <tr class="<?php echo $isProcessed ? 'table-light text-secondary' : ''; ?>">
                  <td>
                    <?php if (!$isProcessed): ?>
                      <input
                        class="form-check-input bulk-process-select"
                        type="checkbox"
                        name="fulfilment_ids[]"
                        value="<?php echo (int) $item['id']; ?>"
                        form="bulkProcessForm"
                        aria-label="Select membership pack for <?php echo mem_h((string) ($item['recipient_name'] ?? '')); ?>"
                      >
                    <?php endif; ?>
                  </td>
                  <td class="fw-semibold">#<?php echo (int) ($item['membership_number'] ?? 0); ?></td>
                  <td>
                    <div class="fw-semibold"><?php echo mem_h((string) ($item['recipient_name'] ?? '')); ?></div>
                    <?php if (trim((string) ($item['email'] ?? '')) !== ''): ?>
                      <div class="small">
                        <a href="mailto:<?php echo mem_h((string) $item['email']); ?>"><?php echo mem_h((string) $item['email']); ?></a>
                      </div>
                    <?php endif; ?>
                    <div class="small text-secondary"><?php echo mem_h(implode(', ', $addressLines)); ?></div>
                  </td>
                  <td>
                    <span class="badge <?php echo (string) $item['fulfilment_type'] === 'join' ? 'bg-primary-subtle text-primary-emphasis' : 'bg-warning-subtle text-warning-emphasis'; ?>">
                      <?php echo (string) $item['fulfilment_type'] === 'join' ? 'New' : 'Renew'; ?>
                    </span>
                  </td>
                  <td class="small">
                    <?php echo mem_h(mem_format_date_uk((string) ($item['queued_at'] ?? $item['paid_at'] ?? ''))); ?>
                  </td>
                  <td>
                    <div class="d-flex flex-wrap gap-2">
                      <a
                        class="btn btn-sm btn-card-action"
                        target="_blank"
                        rel="noopener"
                        href="<?php echo mem_h($baseUrl . '?label=' . (int) $item['id']); ?>"
                      >Print Label</a>
                      <a
                        class="btn btn-sm btn-outline-secondary"
                        href="<?php echo mem_h(mem_base_url('/member-admin-member.php?member_id=' . (int) $item['member_id'])); ?>"
                      >Member</a>
                    </div>
                  </td>
                  <td>
                    <form method="post" action="<?php echo mem_h($returnUrl); ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
                      <input type="hidden" name="action" value="set_processed">
                      <input type="hidden" name="fulfilment_id" value="<?php echo (int) $item['id']; ?>">
                      <input type="hidden" name="processed" value="<?php echo $isProcessed ? '0' : '1'; ?>">
                      <div class="form-check">
                        <input
                          class="form-check-input"
                          type="checkbox"
                          aria-label="<?php echo $isProcessed ? 'Return pack to pending' : 'Mark pack processed'; ?>"
                          <?php echo $isProcessed ? 'checked' : ''; ?>
                          onchange="this.form.submit()"
                        >
                      </div>
                    </form>
                    <?php if ($isProcessed && !empty($item['processed_at'])): ?>
                      <div class="small mt-1">
                        <?php echo mem_h(mem_format_date_uk((string) $item['processed_at'])); ?>
                        <?php if ($processedBy !== ''): ?>
                          <br><?php echo mem_h($processedBy); ?>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <form method="post" action="<?php echo mem_h($returnUrl); ?>" id="bulkProcessForm" class="d-flex flex-wrap align-items-center gap-2 mt-3">
          <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
          <input type="hidden" name="action" value="bulk_process">
          <button class="btn btn-sm btn-outline-secondary" type="button" id="selectAllPacks" <?php echo $selectableCount === 0 ? 'disabled' : ''; ?>>Select All</button>
          <button class="btn btn-sm btn-outline-secondary" type="button" id="clearSelectedPacks" <?php echo $selectableCount === 0 ? 'disabled' : ''; ?>>Clear</button>
          <button class="btn btn-sm btn-card-action" type="submit" <?php echo $selectableCount === 0 ? 'disabled' : ''; ?>>Mark Selected Processed</button>
          <span class="small text-secondary" id="selectedPackCount">0 selected</span>
        </form>
      <?php else: ?>
        <div class="alert alert-light border text-secondary mb-0" role="status">
          No membership packs match these filters.
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var checkboxes = Array.from(document.querySelectorAll('.bulk-process-select'));
    var selectAllButton = document.getElementById('selectAllPacks');
    var clearButton = document.getElementById('clearSelectedPacks');
    var count = document.getElementById('selectedPackCount');
    var bulkForm = document.getElementById('bulkProcessForm');

    function updateCount() {
      var selected = checkboxes.filter(function (checkbox) {
        return checkbox.checked;
      }).length;
      if (count) {
        count.textContent = selected + ' selected';
      }
    }

    if (selectAllButton) {
      selectAllButton.addEventListener('click', function () {
        checkboxes.forEach(function (checkbox) {
          checkbox.checked = true;
        });
        updateCount();
      });
    }

    if (clearButton) {
      clearButton.addEventListener('click', function () {
        checkboxes.forEach(function (checkbox) {
          checkbox.checked = false;
        });
        updateCount();
      });
    }

    checkboxes.forEach(function (checkbox) {
      checkbox.addEventListener('change', updateCount);
    });

    if (bulkForm) {
      bulkForm.addEventListener('submit', function (event) {
        var selected = checkboxes.some(function (checkbox) {
          return checkbox.checked;
        });
        if (!selected) {
          event.preventDefault();
          window.alert('Select at least one pending membership pack.');
        }
      });
    }
  });
</script>
<?php mem_page_footer(); ?>
