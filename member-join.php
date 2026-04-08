<?php
require_once __DIR__ . '/includes/member/ui.php';

if (mem_is_logged_in()) {
  header('Location: ' . mem_base_url('/member-dashboard.php'));
  exit;
}

$quick = isset($_GET['quick']) && $_GET['quick'] === '1';
$error = null;
$createdMemberId = null;

$form = [
  'email' => trim((string) ($_POST['email'] ?? '')),
  'salutation' => trim((string) ($_POST['salutation'] ?? '')),
  'firstname' => trim((string) ($_POST['firstname'] ?? '')),
  'surname' => trim((string) ($_POST['surname'] ?? '')),
  'address1' => trim((string) ($_POST['address1'] ?? '')),
  'address2' => trim((string) ($_POST['address2'] ?? '')),
  'town' => trim((string) ($_POST['town'] ?? '')),
  'county' => trim((string) ($_POST['county'] ?? '')),
  'country' => trim((string) ($_POST['country'] ?? '')),
  'postcode' => trim((string) ($_POST['postcode'] ?? '')),
  'tel1' => trim((string) ($_POST['tel1'] ?? '')),
  'tel2' => trim((string) ($_POST['tel2'] ?? '')),
  'password' => (string) ($_POST['password'] ?? ''),
  'gdpr_policy_accepted' => (string) ($_POST['gdpr_policy_accepted'] ?? ''),
  'gdpr_marketing_opt_in' => (string) ($_POST['gdpr_marketing_opt_in'] ?? ''),
];
$countryOptions = mem_country_options();
$defaultCountry = '';
if ($form['country'] === '' && $countryOptions) {
  $defaultCountry = (string) ($countryOptions[0]['code'] ?? '');
  $form['country'] = $defaultCountry;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');

  if (!mem_verify_csrf($csrf)) {
    $error = 'Session check failed. Please try again.';
  } elseif ($form['email'] === '' || !$quick && $form['firstname'] === '' || !$quick && $form['surname'] === '') {
    $error = $quick ? 'Email is required for Quick Join.' : 'Email, first name and surname are required.';
  } elseif (empty($form['gdpr_policy_accepted'])) {
    $error = 'Please accept the GDPR/privacy terms.';
  } else {
    [$ok, $message, $createdMemberId, $plainPassword] = mem_create_member($form, $quick);
    if (!$ok) {
      $error = $message ?: 'Unable to create account.';
    } else {
      mem_log_event('join_success', 'Join form completed', null, $createdMemberId ? (int) $createdMemberId : null);
      $_SESSION['mem_join_payment'] = [
        'member_id' => (int) $createdMemberId,
        'flow' => $quick ? 'quick' : 'normal',
        'created_at' => time(),
      ];
      header('Location: ' . mem_base_url('/member-payment.php'));
      exit;
    }
  }
}

mem_page_header('UGPSC Members | Join', ['active' => 'join']);
?>
<div class="container" style="max-width:860px;">
  <div class="mem-card p-4 p-lg-5">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h1 class="display-font h3 mb-1"><?php echo $quick ? 'Quick Join' : 'Join Membership'; ?></h1>
        <p class="text-secondary mb-0">
          <?php if ($quick): ?>
            Minimal details route for QR/link onboarding. Password is auto-generated securely.
          <?php else: ?>
            Create your member account with core contact details.
          <?php endif; ?>
        </p>
      </div>
      <a href="<?php echo mem_h(mem_base_url('/member-join.php' . ($quick ? '' : '?quick=1'))); ?>" class="btn btn-outline-secondary">
        <?php echo $quick ? 'Switch to Full Join' : 'Switch to Quick Join'; ?>
      </a>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h($error); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="mem-label" for="email">Email (login)</label>
          <input type="email" class="form-control" id="email" name="email" value="<?php echo mem_h($form['email']); ?>" required>
        </div>
        <div class="col-md-3">
          <label class="mem-label" for="salutation">Salutation</label>
          <input type="text" class="form-control" id="salutation" name="salutation" value="<?php echo mem_h($form['salutation']); ?>">
        </div>
        <div class="col-md-3">
          <label class="mem-label" for="password">Password (optional)</label>
          <input type="password" class="form-control" id="password" name="password" minlength="6" <?php echo $quick ? 'disabled' : ''; ?>>
        </div>

        <div class="col-md-6">
          <label class="mem-label" for="firstname">First name(s)</label>
          <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo mem_h($form['firstname']); ?>" <?php echo $quick ? '' : 'required'; ?>>
        </div>
        <div class="col-md-6">
          <label class="mem-label" for="surname">Surname</label>
          <input type="text" class="form-control" id="surname" name="surname" value="<?php echo mem_h($form['surname']); ?>" <?php echo $quick ? '' : 'required'; ?>>
        </div>

        <div class="col-md-6">
          <label class="mem-label" for="address1">Address 1</label>
          <input type="text" class="form-control" id="address1" name="address1" value="<?php echo mem_h($form['address1']); ?>">
        </div>
        <div class="col-md-6">
          <label class="mem-label" for="address2">Address 2</label>
          <input type="text" class="form-control" id="address2" name="address2" value="<?php echo mem_h($form['address2']); ?>">
        </div>

        <div class="col-md-4">
          <label class="mem-label" for="town">Town</label>
          <input type="text" class="form-control" id="town" name="town" value="<?php echo mem_h($form['town']); ?>">
        </div>
        <div class="col-md-4">
          <label class="mem-label" for="county">County</label>
          <input type="text" class="form-control" id="county" name="county" value="<?php echo mem_h($form['county']); ?>">
        </div>
        <div class="col-md-4">
          <label class="mem-label" for="country">Country</label>
          <input type="text" class="form-control mb-2" id="country-search" placeholder="Search country...">
          <select class="form-select" id="country" name="country" required>
            <?php foreach ($countryOptions as $opt): ?>
              <?php
                $code = (string) ($opt['code'] ?? '');
                $name = trim((string) ($opt['name'] ?? $code));
                $selected = strtoupper($form['country']) === strtoupper($code) ? 'selected' : '';
              ?>
              <option value="<?php echo mem_h($code); ?>" <?php echo $selected; ?>>
                <?php echo mem_h($name !== '' ? $name : $code); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="mem-label" for="postcode">Post/Zip Eircode</label>
          <input type="text" class="form-control" id="postcode" name="postcode" value="<?php echo mem_h($form['postcode']); ?>">
        </div>
        <div class="col-md-4">
          <label class="mem-label" for="tel1">Tel 1 (landline/home)</label>
          <input type="text" class="form-control" id="tel1" name="tel1" value="<?php echo mem_h($form['tel1']); ?>">
        </div>
        <div class="col-md-4">
          <label class="mem-label" for="tel2">Tel 2 (mobile)</label>
          <input type="text" class="form-control" id="tel2" name="tel2" value="<?php echo mem_h($form['tel2']); ?>">
        </div>
      </div>

      <div class="form-check mt-3">
        <input class="form-check-input" type="checkbox" value="1" id="gdpr_policy_accepted" name="gdpr_policy_accepted" <?php echo !empty($form['gdpr_policy_accepted']) ? 'checked' : ''; ?> required>
        <label class="form-check-label" for="gdpr_policy_accepted">I agree to the privacy and GDPR terms.</label>
      </div>
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" value="1" id="gdpr_marketing_opt_in" name="gdpr_marketing_opt_in" <?php echo !empty($form['gdpr_marketing_opt_in']) ? 'checked' : ''; ?>>
        <label class="form-check-label" for="gdpr_marketing_opt_in">I agree to receive club updates.</label>
      </div>

      <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-mem-primary">Continue to Payment</button>
        <a href="<?php echo mem_h(mem_base_url('/member-login.php')); ?>" class="btn btn-outline-secondary">Back to Login</a>
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const search = document.getElementById('country-search');
  const select = document.getElementById('country');
  if (!search || !select) return;

  const allOptions = Array.from(select.options).map((opt) => ({
    value: opt.value,
    text: opt.text,
    selected: opt.selected,
  }));

  const rebuild = (term) => {
    const lower = term.toLowerCase();
    const matches = allOptions.filter((opt) => lower === '' || opt.text.toLowerCase().includes(lower) || opt.value.toLowerCase().includes(lower));
    const currentValue = select.value;
    select.innerHTML = '';
    matches.forEach((opt) => {
      const el = document.createElement('option');
      el.value = opt.value;
      el.text = opt.text;
      select.appendChild(el);
    });
    const hasCurrent = matches.some((opt) => opt.value === currentValue);
    if (hasCurrent) {
      select.value = currentValue;
    } else if (matches[0]) {
      select.value = matches[0].value;
    }
  };

  rebuild(search.value);
  search.addEventListener('input', () => rebuild(search.value));
});
</script>
<?php mem_page_footer(); ?>
