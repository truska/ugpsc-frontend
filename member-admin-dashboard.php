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
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo mem_h($adminTransactions); ?>">View transactions</a>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="p-3 border rounded h-100 bg-light-subtle">
          <div class="mem-label mb-2">Mailings</div>
          <div class="fw-semibold mb-2">Lists &amp; campaigns</div>
          <p class="text-secondary small mb-3">Plan for GDPR-friendly email/SMS tools.</p>
          <a class="btn btn-sm btn-outline-secondary disabled" href="<?php echo mem_h($mailingsUrl); ?>" aria-disabled="true">Coming soon</a>
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
