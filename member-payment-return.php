<?php
require_once __DIR__ . '/includes/member/ui.php';

// This page handles the return/cancel redirect from the payment provider (SumUp placeholder).
$statusParam = strtolower(trim((string) ($_GET['status'] ?? '')));
$providerRef = trim((string) ($_GET['ref'] ?? ''));
$memberId = (int) ($_GET['member_id'] ?? 0);

$statusMap = [
  'success' => 'paid',
  'paid' => 'paid',
  'cancel' => 'cancelled',
  'cancelled' => 'cancelled',
  'failed' => 'failed',
];
$resolvedStatus = $statusMap[$statusParam] ?? '';
$message = '';
$errors = [];

if ($resolvedStatus === 'paid') {
  $message = 'Payment successful. Thank you.';
} elseif ($resolvedStatus === 'cancelled') {
  $message = 'Payment was cancelled. No charge was made.';
} elseif ($resolvedStatus === 'failed') {
  $message = 'Payment failed. Please try again or use a different card.';
} else {
  $message = 'Payment status not confirmed. Please try again.';
}

global $pdo, $DB_OK;
if ($resolvedStatus !== '' && $providerRef !== '' && mem_ready() && $DB_OK && ($pdo instanceof PDO) && mem_table_exists('mem_transaction')) {
  $updateSql = 'UPDATE mem_transaction
    SET status = :status,
        paid_at = CASE WHEN :status = "paid" THEN COALESCE(paid_at, NOW()) ELSE paid_at END,
        modified = NOW()
    WHERE provider_reference = :ref
    LIMIT 1';
  try {
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([
      ':status' => $resolvedStatus,
      ':ref' => $providerRef,
    ]);
  } catch (Throwable $e) {
    $errors[] = 'Could not update transaction status locally.';
  }
} elseif ($providerRef === '') {
  $errors[] = 'No payment reference returned.';
}

mem_page_header('UGPSC Members | Payment Return', ['active' => 'dashboard']);
?>
<div class="container" style="max-width:760px;">
  <div class="mem-card p-4 p-lg-5">
    <h1 class="display-font h4 mb-3">Payment Status</h1>
    <?php if ($errors): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h(implode(' ', $errors)); ?></div>
    <?php else: ?>
      <div class="alert alert-info" role="alert">
        <?php echo mem_h($message); ?>
        <?php if ($providerRef !== ''): ?>
          <div class="small text-secondary mt-1">Reference: <?php echo mem_h($providerRef); ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-mem-primary" href="<?php echo mem_h(mem_base_url('/member-dashboard.php')); ?>">Back to dashboard</a>
      <?php if (!empty($_SESSION['mem_member']['id'])): ?>
        <a class="btn btn-outline-secondary" href="<?php echo mem_h(mem_base_url('/member-admin-transactions.php')); ?>">View transactions</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php mem_page_footer(); ?>
