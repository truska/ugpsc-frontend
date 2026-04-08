<?php
require_once __DIR__ . '/includes/member/ui.php';

$ack = $_SESSION['mem_last_payment_ack'] ?? null;
if (!is_array($ack) || empty($ack['transaction_id'])) {
  header('Location: ' . mem_base_url('/member-join.php'));
  exit;
}

$flow = (string) ($ack['flow'] ?? '');
$continueUrl = mem_base_url('/member-dashboard.php');
$continueLabel = 'Continue To Dashboard';
$activeNav = 'dashboard';

if ($flow === 'quick' || $flow === 'join_quick') {
  $continueUrl = mem_base_url('/static/home.php');
  $continueLabel = 'Continue To Static Home';
  $activeNav = 'join';
} elseif ($flow === 'normal' || $flow === 'join') {
  $continueUrl = mem_base_url('/member-dashboard.php');
  $continueLabel = 'Continue To Dashboard';
  $activeNav = 'dashboard';
} elseif ($flow === 'renewal_quick' || $flow === 'renew_quick') {
  $continueUrl = mem_base_url('/static/home.php');
  $continueLabel = 'Continue To Static Home';
  $activeNav = 'join';
} elseif ($flow === 'renewal_logged_in' || $flow === 'renew_logged_in') {
  $continueUrl = mem_base_url('/member-dashboard.php');
  $continueLabel = 'Continue To Dashboard';
  $activeNav = 'dashboard';
}

mem_page_header('UGPSC Members | Payment Complete', ['active' => $activeNav]);
?>
<div class="container" style="max-width:900px;">
  <div class="mem-card p-4">
    <h1 class="display-font h3 mb-2">Payment Completed</h1>
    <p class="text-secondary mb-3">Your membership payment has been recorded. A confirmation modal opens automatically.</p>
    <a class="btn btn-mem-primary" href="<?php echo mem_h($continueUrl); ?>"><?php echo mem_h($continueLabel); ?></a>
  </div>
</div>

<div class="modal fade" id="paymentAckModal" tabindex="-1" aria-labelledby="paymentAckLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="paymentAckLabel">Membership Confirmed</h2>
      </div>
      <div class="modal-body">
        <p class="mb-3">Thank you. Your payment has been successfully processed via Stripe.</p>
        <div class="small">
          <div><strong>Transaction ID:</strong> <?php echo (int) $ack['transaction_id']; ?></div>
          <div><strong>Reference:</strong> <?php echo mem_h((string) ($ack['provider_reference'] ?? '')); ?></div>
          <div><strong>Amount:</strong> <?php echo mem_h(mem_money_display((float) ($ack['amount'] ?? 0), (string) ($ack['currency'] ?? 'EUR'))); ?></div>
          <div><strong>Member:</strong> <?php echo mem_h(trim((string) ($ack['member_name'] ?? '')) !== '' ? (string) ($ack['member_name'] ?? '') : 'Member'); ?></div>
          <div><strong>Membership No:</strong> <?php echo (int) ($ack['membership_number'] ?? 0); ?></div>
        </div>
        <p class="small text-secondary mt-3 mb-0">Gateway transaction detail expansion will be added later.</p>
      </div>
      <div class="modal-footer">
        <a class="btn btn-mem-primary" href="<?php echo mem_h($continueUrl); ?>"><?php echo mem_h($continueLabel); ?></a>
      </div>
    </div>
  </div>
</div>

<script>
  window.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('paymentAckModal');
    if (!modalElement || typeof bootstrap === 'undefined') {
      return;
    }
    var modal = new bootstrap.Modal(modalElement, {
      backdrop: 'static',
      keyboard: false
    });
    modal.show();
  });
</script>
<?php
unset($_SESSION['mem_last_payment_ack']);
mem_page_footer();
?>
