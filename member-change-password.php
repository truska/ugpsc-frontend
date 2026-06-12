<?php
require_once __DIR__ . '/includes/member/ui.php';
require_once __DIR__ . '/includes/member/password-change.php';

mem_require_login();
$memberId = (int) ($_SESSION['mem_member']['id'] ?? 0);
$member = mem_load_member($memberId);
if (!$member) {
  mem_logout();
  header('Location: ' . mem_base_url('/member-login.php'));
  exit;
}

$error = null;
$success = null;
mem_handle_password_change($memberId, $success, $error);

mem_page_header('UGPSC Members | Change Password', ['active' => 'profile']);
?>
<div class="container" style="max-width:620px;">
  <div class="mem-card p-4 p-lg-5">
    <h1 class="display-font h3 mb-2">Change Password</h1>
    <p class="text-secondary mb-4">Enter your new password twice, then save it.</p>

    <?php if ($success): ?>
      <div class="alert alert-success" role="alert"><?php echo mem_h($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h($error); ?></div>
    <?php endif; ?>

    <?php mem_render_password_change_form(); ?>
  </div>
</div>
<?php mem_page_footer(); ?>
