<?php
require_once __DIR__ . '/includes/member/ui.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$error = null;
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');
  $password = (string) ($_POST['password'] ?? '');
  $confirm = (string) ($_POST['confirm_password'] ?? '');

  if (!mem_verify_csrf($csrf)) {
    $error = 'Session check failed. Please try again.';
  } elseif ($password === '' || $confirm === '') {
    $error = 'Please enter your new password twice.';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
  } elseif (strlen($password) < mem_password_min_length()) {
    $error = 'Use at least ' . mem_password_min_length() . ' characters for your password.';
  } elseif (mem_reset_password($token, $password, $error)) {
    header('Location: ' . mem_base_url('/member-login.php?reset=1'));
    exit;
  }
}

mem_page_header('UGPSC Members | Reset Password', ['active' => 'login']);
?>
<div class="container" style="max-width:620px;">
  <div class="mem-card p-4 p-lg-5">
    <h1 class="display-font h3 mb-2">Set New Password</h1>
    <p class="text-secondary">Create a new secure password for your member account.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h($error); ?></div>
    <?php endif; ?>
    <?php if ($message): ?>
      <div class="alert alert-success" role="alert"><?php echo mem_h($message); ?></div>
    <?php endif; ?>

    <?php if ($token === ''): ?>
      <div class="alert alert-warning" role="alert">Reset token missing or invalid.</div>
      <a href="<?php echo mem_h(mem_base_url('/member-forgot-password.php')); ?>" class="btn btn-outline-secondary">Request a new link</a>
    <?php else: ?>
      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
        <input type="hidden" name="token" value="<?php echo mem_h($token); ?>">
        <div class="mb-3">
          <label class="mem-label" for="password">New Password</label>
          <input type="password" class="form-control" id="password" name="password" minlength="6" required>
        </div>
        <div class="mb-3">
          <label class="mem-label" for="confirm_password">Confirm Password</label>
          <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-mem-primary" type="submit">Update password</button>
          <a href="<?php echo mem_h(mem_base_url('/member-login.php')); ?>" class="btn btn-outline-secondary">Back to login</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php mem_page_footer(); ?>
