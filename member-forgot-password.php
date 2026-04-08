<?php
require_once __DIR__ . '/includes/member/ui.php';

$error = null;
$message = null;
$debugLink = null;
$email = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');

  if (!mem_verify_csrf($csrf)) {
    $error = 'Session check failed. Please try again.';
  } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email.';
  } else {
    $token = mem_request_password_reset($email);
    if ($token) {
      $debugLink = mem_base_url('/member-reset-password.php?token=' . urlencode($token));
      $mailOk = mem_send_mail(
        $email,
        'UGPSC member password reset',
        '<p>Please use the link below to reset your password.</p><p><a href="' . mem_h($debugLink) . '">' . mem_h($debugLink) . '</a></p><p>This link expires in 1 hour.</p>',
        "Reset link: {$debugLink}\nThis link expires in 1 hour."
      );
      $message = $mailOk
        ? 'If your email is registered, a reset link has been sent.'
        : 'Reset token created. Email sending is not configured; use the temporary link below.';
    } else {
      $message = 'If your email is registered, a reset link has been sent.';
    }
  }
}

mem_page_header('UGPSC Members | Forgot Password', ['active' => 'login']);
?>
<div class="container" style="max-width:620px;">
  <div class="mem-card p-4 p-lg-5">
    <h1 class="display-font h3 mb-2">Forgot Password</h1>
    <p class="text-secondary">Enter your email and we will issue a password reset link.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h($error); ?></div>
    <?php endif; ?>
    <?php if ($message): ?>
      <div class="alert alert-success" role="alert"><?php echo mem_h($message); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
      <div class="mb-3">
        <label class="mem-label" for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="<?php echo mem_h($email); ?>" required>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-mem-primary" type="submit">Send reset link</button>
        <a href="<?php echo mem_h(mem_base_url('/member-login.php')); ?>" class="btn btn-outline-secondary">Back to login</a>
      </div>
    </form>

    <?php if ($debugLink): ?>
      <div class="alert alert-warning mt-4" role="alert">
        <strong>Temporary dev link:</strong><br>
        <a href="<?php echo mem_h($debugLink); ?>"><?php echo mem_h($debugLink); ?></a>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php mem_page_footer(); ?>
