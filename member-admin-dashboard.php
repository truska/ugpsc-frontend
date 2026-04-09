<?php
require_once __DIR__ . '/includes/member/ui.php';

mem_require_login();
$member = mem_current_member();
if (empty($member['is_admin'])) {
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

mem_page_header('UGPSC Admin | Dashboard', ['active' => 'admin']);

$adminBase = mem_base_url('/member-admin.php');
$adminTransactions = mem_base_url('/member-admin-transactions.php');
$financialsUrl = '#';
$mailingsUrl = '#';
$reportsUrl = '#';

$currentYear = (int) date('Y');
$lastYear = $currentYear - 1;
$yearCounts = [
  $currentYear => 0,
  $lastYear => 0,
];

if (mem_ready() && mem_table_exists('mem_membership_year')) {
  global $pdo, $DB_OK;
  if ($DB_OK && $pdo instanceof PDO) {
    $stmt = $pdo->prepare(
      'SELECT membership_year, COUNT(*) AS total
       FROM mem_membership_year
       WHERE archived = 0
         AND membership_year IN (:this_year, :last_year)
       GROUP BY membership_year'
    );
    $stmt->execute([
      ':this_year' => $currentYear,
      ':last_year' => $lastYear,
    ]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $year = (int) ($row['membership_year'] ?? 0);
      $count = (int) ($row['total'] ?? 0);
      if (array_key_exists($year, $yearCounts)) {
        $yearCounts[$year] = $count;
      }
    }
  }
}

$statCards = [
  [
    'label' => 'Members this year (' . $currentYear . ')',
    'value' => $yearCounts[$currentYear],
    'bg' => '#1f5a3f',
  ],
  [
    'label' => 'Members last year (' . $lastYear . ')',
    'value' => $yearCounts[$lastYear],
    'bg' => '#bf3b2b',
  ],
];
?>
<div class="container">
  <div class="mem-card p-4 p-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="display-font h3 mb-1">Admin Dashboard</h1>
        <p class="text-secondary mb-0">Jump to membership tools, finance, mailings, and reports.</p>
      </div>
      <div><span class="badge text-bg-light">Admin</span></div>
    </div>

    <div class="row g-3 mb-3">
      <?php foreach ($statCards as $card): ?>
        <div class="col-12 col-sm-6 col-lg-4 col-xxl-2">
          <div class="p-3 rounded h-100 text-white text-center d-flex flex-column justify-content-center align-items-center" style="background: <?php echo mem_h($card['bg']); ?>;">
            <div class="fs-2 fw-bold mb-1"><?php echo (int) $card['value']; ?></div>
            <div class="small fw-semibold"><?php echo mem_h($card['label']); ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="row g-3 g-lg-4">
      <div class="col-md-6 col-xl-3">
        <div class="p-3 border rounded h-100 bg-light-subtle">
          <div class="mem-label mb-2">Membership</div>
          <div class="fw-semibold mb-2">Find and manage members</div>
          <p class="text-secondary small mb-3">Search, select, and act on member records.</p>
          <a class="btn btn-sm btn-mem-primary" href="<?php echo mem_h($adminBase); ?>">Go to Find Member</a>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="p-3 border rounded h-100 bg-light-subtle">
          <div class="mem-label mb-2">Financial</div>
          <div class="fw-semibold mb-2">Payments &amp; ledger</div>
          <p class="text-secondary small mb-3">View card payments, references, and status.</p>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo mem_h($adminTransactions); ?>">Financial</a>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="p-3 border rounded h-100 bg-light-subtle">
          <div class="mem-label mb-2">Mailings</div>
          <div class="fw-semibold mb-2">Lists &amp; campaigns</div>
          <p class="text-secondary small mb-3">Renewal emails, printable letters, and export lists.</p>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo mem_h(mem_base_url('/member-admin-mailings.php')); ?>">Open mailings</a>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="p-3 border rounded h-100 bg-light-subtle">
          <div class="mem-label mb-2">Reports</div>
          <div class="fw-semibold mb-2">Snapshots &amp; exports</div>
          <p class="text-secondary small mb-3">Slot in KPI tiles and CSV exports.</p>
          <a class="btn btn-sm btn-outline-secondary disabled" href="<?php echo mem_h($reportsUrl); ?>" aria-disabled="true">Coming soon</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php mem_page_footer(); ?>
