<?php
require_once __DIR__ . '/includes/member/ui.php';

$pending = $_SESSION['mem_join_payment'] ?? null;
if (!is_array($pending) || empty($pending['member_id']) || empty($pending['flow'])) {
  header('Location: ' . mem_base_url('/member-join.php'));
  exit;
}

$memberId = (int) $pending['member_id'];
$flow = ($pending['flow'] === 'quick') ? 'quick' : 'normal';
$createdAt = (int) ($pending['created_at'] ?? 0);
if ($createdAt <= 0 || (time() - $createdAt) > 3600) {
  unset($_SESSION['mem_join_payment']);
  header('Location: ' . mem_base_url('/member-join.php'));
  exit;
}

$member = mem_load_member($memberId);
if (!$member) {
  unset($_SESSION['mem_join_payment']);
  header('Location: ' . mem_base_url('/member-join.php'));
  exit;
}

$selectedCurrency = mem_resolve_currency((string) ($_POST['currency'] ?? ''), (string) ($member['country'] ?? ''));
$amount = mem_membership_amount($selectedCurrency, (string) ($member['country'] ?? ''));
$isOverseas = mem_is_overseas_country((string) ($member['country'] ?? ''));
$currencyOptions = mem_membership_currency_options();
$stripeConfig = mem_stripe_config();
$stripeReady = mem_stripe_ready();

mem_page_header('UGPSC Members | Payment', ['active' => 'join']);
?>
<div class="container" style="max-width:860px;">
  <div class="mem-card p-4 p-lg-5">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h1 class="display-font h3 mb-1">Membership Payment</h1>
        <p class="text-secondary mb-0">Secure card payment powered by Stripe.</p>
      </div>
      <span class="badge text-bg-secondary text-uppercase"><?php echo mem_h($flow); ?> Join</span>
    </div>

    <?php if (!$stripeReady): ?>
      <div class="alert alert-danger" role="alert">Stripe is not configured yet. Please add your API keys.</div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="border rounded p-3 p-lg-4 bg-light-subtle">
          <div class="mem-label mb-2">Card Details</div>
          <form id="stripe-form" novalidate>
            <div class="mb-3">
              <label class="mem-label" for="card_name">Name on card</label>
              <input type="text" id="card_name" name="card_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="mem-label" for="currency">Currency</label>
              <select id="currency" name="currency" class="form-select" required>
                <?php foreach ($currencyOptions as $currencyCode): ?>
                  <option value="<?php echo mem_h($currencyCode); ?>" <?php echo $selectedCurrency === $currencyCode ? 'selected' : ''; ?>>
                    <?php echo mem_h($currencyCode . ' (' . mem_currency_symbol($currencyCode) . ')'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="mem-label" for="card-element">Card</label>
              <div id="card-element" class="form-control"></div>
            </div>
            <div class="text-danger small mb-2" id="card-errors" role="alert"></div>
            <button type="submit" class="btn btn-mem-primary mt-3" id="pay-button" <?php echo $stripeReady ? '' : 'disabled'; ?>>
              Pay <span id="pay-amount"><?php echo mem_h(mem_money_display($amount, $selectedCurrency)); ?></span>
            </button>
          </form>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="border rounded p-3 p-lg-4 h-100">
          <div class="mem-label mb-2">Payment Summary</div>
          <div class="mb-2"><strong>Member:</strong> <?php echo mem_h(mem_member_reference($member)); ?></div>
          <?php if (mem_member_has_login_access($member) && (string) ($member['email'] ?? '') !== ''): ?>
            <div class="mb-2"><strong>Email:</strong> <?php echo mem_h((string) ($member['email'] ?? '')); ?></div>
          <?php endif; ?>
          <div class="mb-2"><strong>Country:</strong> <?php echo mem_h((string) ($member['country'] ?? 'Not set')); ?></div>
          <div class="mb-2"><strong>Rate Type:</strong> <?php echo $isOverseas ? 'Overseas' : 'Domestic'; ?></div>
          <div class="mb-2"><strong>Type:</strong> New Membership</div>
          <div class="mb-2"><strong>Provider:</strong> Stripe</div>
          <div class="mb-0"><strong>Amount:</strong> <?php echo mem_h(mem_money_display($amount, $selectedCurrency)); ?></div>
          <hr>
          <p class="small text-secondary mb-0">Pricing uses selected currency and country-based domestic/overseas rate rules.</p>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://js.stripe.com/v3/"></script>
<script>
(() => {
  const stripeReady = <?php echo $stripeReady ? 'true' : 'false'; ?>;
  if (!stripeReady) return;

  const stripe = Stripe('<?php echo mem_h($stripeConfig['publishable_key']); ?>');
  const elements = stripe.elements();
  const card = elements.create('card', {hidePostalCode: true});
  card.mount('#card-element');

  const cardErrors = document.getElementById('card-errors');
  const form = document.getElementById('stripe-form');
  const payButton = document.getElementById('pay-button');
  const payAmount = document.getElementById('pay-amount');
  const currencyField = document.getElementById('currency');

  let clientSecret = '';
  let intentId = '';
  let isSubmitting = false;

  const setError = (msg) => {
    cardErrors.textContent = msg || '';
  };

  const setBusy = (busy) => {
    isSubmitting = busy;
    payButton.disabled = busy;
    payButton.classList.toggle('disabled', busy);
  };

  const createIntent = () => {
    setBusy(true);
    setError('');
    const formData = new URLSearchParams();
    formData.append('action', 'create_intent');
    formData.append('transaction_type', 'join');
    formData.append('flow', 'join');
    formData.append('currency', currencyField.value);

    fetch('<?php echo mem_base_url('/member-stripe.php'); ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData.toString()
    }).then(r => r.json()).then(data => {
      if (!data || data.error) {
        throw new Error(data && data.error ? data.error : 'Could not start payment.');
      }
      clientSecret = data.client_secret;
      intentId = data.payment_intent_id;
      payAmount.textContent = new Intl.NumberFormat('en-GB', { style: 'currency', currency: data.currency }).format(data.amount);
    }).catch(err => {
      setError(err.message);
    }).finally(() => setBusy(false));
  };

  currencyField.addEventListener('change', () => {
    createIntent();
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (isSubmitting) return;
    if (!clientSecret) {
      setError('Preparing payment… please try again.');
      return;
    }
    setBusy(true);
    setError('');
    const cardName = document.getElementById('card_name').value.trim();
    if (cardName === '') {
      setError('Please enter the cardholder name.');
      setBusy(false);
      return;
    }
    const {error, paymentIntent} = await stripe.confirmCardPayment(clientSecret, {
      payment_method: {
        card,
        billing_details: { name: cardName }
      }
    });
    if (error) {
      setError(error.message || 'Card was not accepted.');
      setBusy(false);
      return;
    }
    if (paymentIntent && paymentIntent.status === 'succeeded') {
      const finalizeData = new URLSearchParams();
      finalizeData.append('action', 'finalize');
      finalizeData.append('transaction_type', 'join');
      finalizeData.append('flow', 'join');
      finalizeData.append('payment_intent_id', paymentIntent.id);
      fetch('<?php echo mem_base_url('/member-stripe.php'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: finalizeData.toString()
      }).then(r => r.json()).then(data => {
        if (data && data.ok && data.redirect) {
          window.location.href = data.redirect;
        } else {
          throw new Error(data && data.error ? data.error : 'Could not record payment.');
        }
      }).catch(err => {
        setError(err.message || 'Payment recorded but could not update membership. Please contact support.');
      }).finally(() => setBusy(false));
    } else {
      setError('Payment not completed yet. Please try again.');
      setBusy(false);
    }
  });

  createIntent();
})();
</script>
<?php mem_page_footer(); ?>
