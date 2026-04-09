<?php
require_once __DIR__ . '/includes/member/ui.php';

mem_require_login();
$member = mem_current_member();
if (empty($member['is_admin'])) {
  http_response_code(403);
  echo 'Admin access required.';
  exit;
}

$year = (int) ($_GET['year'] ?? (int) date('Y'));
$siteBaseUrl = cms_base_url();
$siteName = trim((string) cms_pref('prefSiteName', 'WCCMS'));
$logoName = trim((string) cms_pref('prefLogoName', $siteName));
$logoFile = trim((string) cms_pref('prefLogo', ''));
if ($logoFile === '') {
  $logoFile = trim((string) cms_pref('prefLogo1', ''));
}
if ($logoFile === '') {
  $logoFile = 'ugpsc-logo.png';
}
if (preg_match('#^https?://#i', $logoFile) || str_starts_with($logoFile, '/')) {
  $logoUrl = $logoFile;
} else {
  $logoUrl = $siteBaseUrl . '/filestore/images/logos/' . ltrim($logoFile, '/');
}

$rows = [];
$error = null;
if (!mem_ready() || !mem_table_exists('mem_membership_year')) {
  $error = 'Membership data is not available.';
} else {
  global $pdo, $DB_OK;
  if (!$DB_OK || !($pdo instanceof PDO)) {
    $error = 'Database unavailable.';
  } else {
    $stmt = $pdo->prepare(
      'SELECT m.membership_number, m.firstname, m.surname, m.email, m.tel1, m.postcode
       FROM mem_membership_year y
       JOIN mem_member m ON m.id = y.member_id
       WHERE y.archived = 0
         AND y.membership_year = :year
       ORDER BY m.membership_number ASC'
    );
    $stmt->execute([':year' => $year]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
$totalMembers = count($rows);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo mem_h($siteName); ?> Member List <?php echo mem_h((string) $year); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <style>
    body { padding: 24px; }
    @media print { .hide-print { display: none !important; } }
  </style>
</head>
<body>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
      <img src="<?php echo mem_h($logoUrl); ?>" alt="<?php echo mem_h($logoName); ?> logo" style="height:48px;width:auto;">
      <div>
        <div class="text-uppercase text-secondary small fw-semibold">Report</div>
        <h1 class="h4 mb-0">Member List — <?php echo mem_h((string) $year); ?></h1>
        <div class="small text-secondary">Total members: <?php echo (int) $totalMembers; ?></div>
      </div>
    </div>
    <div class="hide-print d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print / PDF</button>
      <button class="btn btn-outline-secondary btn-sm" type="button" id="export-csv-btn">Export CSV</button>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo mem_h($error); ?></div>
  <?php elseif (!$rows): ?>
    <div class="alert alert-light border text-secondary" role="alert">No data found for <?php echo mem_h((string) $year); ?>.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm align-middle" id="member-report-table">
        <thead class="table-light">
          <tr>
            <th scope="col">Member #</th>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">Tel</th>
            <th scope="col">Postcode</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?php echo mem_h((string) ($row['membership_number'] ?? '')); ?></td>
              <td><?php echo mem_h(trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['surname'] ?? ''))); ?></td>
              <td><?php echo mem_h((string) ($row['email'] ?? '')); ?></td>
              <td><?php echo mem_h((string) ($row['tel1'] ?? '')); ?></td>
              <td><?php echo mem_h((string) ($row['postcode'] ?? '')); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <script>
  (() => {
    const btn = document.getElementById('export-csv-btn');
    const table = document.getElementById('member-report-table');
    if (!btn || !table) return;
    btn.addEventListener('click', () => {
      const rows = Array.from(table.querySelectorAll('tr'));
      const csv = rows.map(row => Array.from(row.querySelectorAll('th,td')).map(cell => `"${cell.textContent.trim().replace(/"/g, '""')}"`).join(',')).join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'member-list-<?php echo mem_h((string) $year); ?>.csv';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    });
  })();
  </script>
</body>
</html>
