<?php
require_once __DIR__ . '/includes/member/ui.php';
require_once __DIR__ . '/includes/member/account-claims.php';

$errors = [];
$notice = null;
$devVerificationUrl = null;
$membershipNumber = max(0, (int) ($_POST['membership_number'] ?? 0));
$code = mem_normalize_magic_code((string) ($_POST['code'] ?? ''));
$token = trim((string) ($_GET['token'] ?? ''));
$verifyToken = trim((string) ($_GET['verify'] ?? $_POST['verify'] ?? ''));

if ($token !== '') {
  $tokenClaim = mem_account_claim_by_token($token);
  if ($tokenClaim) {
    mem_account_claim_authorize($tokenClaim);
    header('Location: ' . mem_base_url('/member-claim-account.php'));
    exit;
  }
  $errors[] = 'That account claim link is invalid or has expired.';
}

if ($verifyToken !== '') {
  $verifiedClaim = mem_account_claim_verify_email($verifyToken);
  if ($verifiedClaim) {
    header('Location: ' . mem_base_url('/member-claim-account.php?setup=1'));
    exit;
  }
  $errors[] = 'That email verification link is invalid or has expired.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $verifyToken === '') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');
  $action = (string) ($_POST['action'] ?? '');

  if (!mem_verify_csrf($csrf)) {
    $errors[] = 'Session check failed. Please try again.';
  } elseif ($action === 'request_letter') {
    if ($membershipNumber <= 0) {
      $errors[] = 'Please enter your membership number.';
    } else {
      mem_account_claim_request($membershipNumber);
      $notice = 'If that membership record is eligible, an account claim letter will be posted to the address held on file.';
      $membershipNumber = 0;
    }
  } elseif ($action === 'check_code') {
    $claim = mem_account_claim_by_code($membershipNumber, $code);
    if (!$claim) {
      $errors[] = 'Those account claim details are invalid or have expired.';
    } else {
      mem_account_claim_authorize($claim);
      header('Location: ' . mem_base_url('/member-claim-account.php'));
      exit;
    }
  } elseif ($action === 'set_email') {
    $claim = mem_account_claim_current();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $confirmEmail = strtolower(trim((string) ($_POST['confirm_email'] ?? '')));
    if (!$claim) {
      $errors[] = 'Your account claim session has expired. Please use the code from your letter again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Please enter a valid email address.';
    } elseif ($email !== $confirmEmail) {
      $errors[] = 'The email addresses do not match.';
    } elseif (!mem_account_claim_email_available($email, (int) $claim['member_id'])) {
      $errors[] = 'That email address is already attached to another member account.';
    } else {
      $verificationUrl = null;
      if (mem_account_claim_start_email_verification($claim, $email, $verificationUrl)) {
        if (mem_account_claim_is_dev()) {
          $devVerificationUrl = $verificationUrl;
          $notice = 'Development mode: no email was sent. Use the test verification link below.';
        } else {
          $notice = 'A verification link has been sent to your email address. Open it to continue setting up your account.';
        }
      } else {
        $errors[] = 'The verification email could not be prepared. Please try again.';
      }
    }
  } elseif ($action === 'set_password') {
    $claim = mem_account_claim_verified();
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $completionError = null;
    if (!$claim) {
      $errors[] = 'Your verified account setup session has expired.';
    } elseif ($password === '' || $confirmPassword === '') {
      $errors[] = 'Please enter your password twice.';
    } elseif ($password !== $confirmPassword) {
      $errors[] = 'Passwords do not match.';
    } elseif (mem_account_claim_complete($claim, $password, $completionError)) {
      header('Location: ' . mem_base_url('/member-dashboard.php?claimed=1'));
      exit;
    } else {
      $errors[] = $completionError ?: 'Your account could not be completed.';
    }
  }
}

$currentClaim = mem_account_claim_current();
$verifiedClaim = mem_account_claim_verified();

mem_page_header('UGPSC Members | Claim Account', ['active' => 'login']);
?>
<div class="container" style="max-width:760px;">
  <div class="mem-card p-4 p-lg-5">
    <h1 class="display-font h3 mb-2">Claim Your Member Account</h1>
    <p class="text-secondary mb-4">For existing members whose record does not yet have an online login.</p>

    <?php foreach ($errors as $error): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h($error); ?></div>
    <?php endforeach; ?>
    <?php if ($notice): ?>
      <div class="alert alert-success" role="alert"><?php echo mem_h($notice); ?></div>
    <?php endif; ?>

    <?php if ($verifiedClaim): ?>
      <div class="mb-4">
        <div class="mem-label mb-1">Final Step</div>
        <h2 class="h4">Create your password</h2>
        <p class="text-secondary">Your email has been verified. Create a password to activate your member login.</p>
      </div>
      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
        <input type="hidden" name="action" value="set_password">
        <div class="mb-3">
          <label class="mem-label" for="password">Password</label>
          <div class="input-group">
            <input class="form-control" type="password" id="password" name="password" minlength="<?php echo mem_password_min_length(); ?>" autocomplete="new-password" required>
            <button class="btn mem-password-reveal" type="button" data-password-reveal aria-controls="password" aria-label="Hold to show password">Show</button>
          </div>
        </div>
        <div class="mb-3">
          <label class="mem-label" for="confirm_password">Confirm Password</label>
          <div class="input-group">
            <input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="<?php echo mem_password_min_length(); ?>" autocomplete="new-password" required>
            <button class="btn mem-password-reveal" type="button" data-password-reveal aria-controls="confirm_password" aria-label="Hold to show password">Show</button>
          </div>
        </div>
        <button class="btn btn-mem-primary" type="submit">Activate My Account</button>
      </form>
    <?php elseif ($currentClaim): ?>
      <div class="mb-4">
        <div class="mem-label mb-1">Identity Confirmed</div>
        <h2 class="h4">Add your email address</h2>
        <p class="text-secondary mb-0">We will send a verification link to this address before it is added to your member record.</p>
      </div>
      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
        <input type="hidden" name="action" value="set_email">
        <div class="mb-3">
          <label class="mem-label" for="email">Email Address</label>
          <input class="form-control" type="email" id="email" name="email" autocomplete="email" required>
        </div>
        <div class="mb-3">
          <label class="mem-label" for="confirm_email">Confirm Email Address</label>
          <input class="form-control" type="email" id="confirm_email" name="confirm_email" autocomplete="email" required>
        </div>
        <button class="btn btn-mem-primary" type="submit">Verify My Email</button>
      </form>
      <?php if ($devVerificationUrl): ?>
        <div class="alert alert-warning mt-4">
          <strong>Development verification link:</strong><br>
          <a href="<?php echo mem_h($devVerificationUrl); ?>"><?php echo mem_h($devVerificationUrl); ?></a>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="border rounded p-3 p-lg-4 h-100">
            <div class="mem-label mb-2">Have Your Letter?</div>
            <h2 class="h5">Enter your claim details</h2>
            <p class="small text-secondary">Use the membership number and eight-character code printed on your letter.</p>
            <form method="post" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
              <input type="hidden" name="action" value="check_code">
              <div class="mb-3">
                <label class="mem-label" for="membership_number">Membership Number</label>
                <input class="form-control" type="number" id="membership_number" name="membership_number" min="1" inputmode="numeric" required>
              </div>
              <div class="mb-3">
                <label class="mem-label" for="code">Claim Code</label>
                <input class="form-control text-uppercase" type="text" id="code" name="code" maxlength="8" autocomplete="one-time-code" required>
              </div>
              <button class="btn btn-mem-primary" type="submit">Continue</button>
            </form>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="border rounded p-3 p-lg-4 h-100 bg-light-subtle">
            <div class="mem-label mb-2">Need A Claim Letter?</div>
            <h2 class="h5">Request one by post</h2>
            <p class="small text-secondary">We will post a one-time claim code to the address held on your membership record.</p>
            <form method="post" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
              <input type="hidden" name="action" value="request_letter">
              <div class="mb-3">
                <label class="mem-label" for="request_membership_number">Membership Number</label>
                <input class="form-control" type="number" id="request_membership_number" name="membership_number" min="1" inputmode="numeric" required>
              </div>
              <button class="btn btn-mem-primary" type="submit">Request Claim Letter</button>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php mem_page_footer(); ?>
