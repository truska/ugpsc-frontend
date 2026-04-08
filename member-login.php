<?php
require_once __DIR__ . '/includes/member/ui.php';

if (mem_is_logged_in()) {
  $target = !empty($_SESSION['mem_member']['is_admin']) ? '/member-admin-dashboard.php' : '/member-dashboard.php';
  header('Location: ' . mem_base_url($target));
  exit;
}

$error = null;
$notice = null;
$email = trim((string) ($_POST['email'] ?? ''));

if (isset($_GET['reset']) && $_GET['reset'] === '1') {
  $notice = 'Password updated. You can now log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');
  $password = (string) ($_POST['password'] ?? '');

  if (!mem_verify_csrf($csrf)) {
    $error = 'Session check failed. Please try again.';
  } elseif ($email === '' || $password === '') {
    $error = 'Please enter your email and password.';
  } elseif (mem_login($email, $password, $error)) {
    $target = !empty($_SESSION['mem_member']['is_admin']) ? '/member-admin-dashboard.php' : '/member-dashboard.php';
    header('Location: ' . mem_base_url($target));
    exit;
  }
}

mem_page_header('UGPSC Members | Login', ['active' => 'login']);
?>
<div class="container" style="max-width:620px;">
  <div class="mem-card p-4 p-lg-5">
    <h1 class="display-font h3 mb-2">Member Login</h1>
    <p class="text-secondary mb-4">Use your email address and password to access your membership dashboard.</p>

    <?php if (!mem_ready()): ?>
      <div class="alert alert-warning" role="alert">
        Membership database tables are not installed yet. Run the migration first.
      </div>
    <?php endif; ?>

    <?php if ($notice): ?>
      <div class="alert alert-success" role="alert"><?php echo mem_h($notice); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h($error); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
      <div class="mb-3">
        <label class="mem-label" for="email">Email address</label>
        <input type="email" class="form-control" id="email" name="email" value="<?php echo mem_h($email); ?>" autocomplete="email" required>
      </div>
      <div class="mb-3">
        <label class="mem-label" for="password">Password</label>
        <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
      </div>
      <div class="d-grid gap-2 d-sm-flex">
        <button type="submit" class="btn btn-mem-primary">Log In</button>
        <a href="<?php echo mem_h(mem_base_url('/member-join.php')); ?>" class="btn btn-outline-secondary">Join Membership</a>
      </div>
    </form>

    <div class="mt-3 d-flex flex-column gap-1">
      <a href="<?php echo mem_h(mem_base_url('/member-forgot-password.php')); ?>">Forgot password?</a>
      <a href="<?php echo mem_h(mem_base_url('/member-join.php?quick=1')); ?>">Quick Join (minimum details)</a>
    </div>
  </div>
</div>
<?php mem_page_footer(); ?>
