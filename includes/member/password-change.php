<?php

function mem_handle_password_change(int $memberId, ?string &$success = null, ?string &$error = null): bool {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (string) ($_POST['action'] ?? '') !== 'change_password') {
    return false;
  }

  $csrf = (string) ($_POST['csrf_token'] ?? '');
  $password = (string) ($_POST['new_password'] ?? '');
  $confirm = (string) ($_POST['confirm_new_password'] ?? '');

  if (!mem_verify_csrf($csrf)) {
    $error = 'Session check failed. Please try again.';
  } elseif ($password === '' || $confirm === '') {
    $error = 'Please enter your new password twice.';
  } elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
  } elseif (mem_change_password($memberId, $password, $error)) {
    $success = 'Your password has been updated.';
  }

  return true;
}

function mem_render_password_change_form(): void {
  ?>
  <form method="post" novalidate>
    <input type="hidden" name="action" value="change_password">
    <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">

    <div class="mb-3">
      <label class="mem-label" for="new_password">New Password</label>
      <div class="input-group">
        <input type="password" class="form-control" id="new_password" name="new_password" minlength="<?php echo mem_password_min_length(); ?>" autocomplete="new-password" required>
        <button class="btn mem-password-reveal" type="button" data-password-reveal aria-controls="new_password" aria-label="Hold to show new password" aria-pressed="false" title="Hover or hold to show password" onmouseenter="this.previousElementSibling.type='text'" onmouseleave="this.previousElementSibling.type='password'" ontouchstart="this.previousElementSibling.type='text'" ontouchend="this.previousElementSibling.type='password'" onkeydown="if(event.key===' '||event.key==='Enter'){event.preventDefault();this.previousElementSibling.type='text'}" onkeyup="this.previousElementSibling.type='password'" onblur="this.previousElementSibling.type='password'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
        </button>
      </div>
    </div>

    <div class="mb-4">
      <label class="mem-label" for="confirm_new_password">Confirm New Password</label>
      <div class="input-group">
        <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" minlength="<?php echo mem_password_min_length(); ?>" autocomplete="new-password" required>
        <button class="btn mem-password-reveal" type="button" data-password-reveal aria-controls="confirm_new_password" aria-label="Hold to show confirmed password" aria-pressed="false" title="Hover or hold to show password" onmouseenter="this.previousElementSibling.type='text'" onmouseleave="this.previousElementSibling.type='password'" ontouchstart="this.previousElementSibling.type='text'" ontouchend="this.previousElementSibling.type='password'" onkeydown="if(event.key===' '||event.key==='Enter'){event.preventDefault();this.previousElementSibling.type='text'}" onkeyup="this.previousElementSibling.type='password'" onblur="this.previousElementSibling.type='password'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
        </button>
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
      <button type="submit" class="btn btn-mem-primary">Save Password</button>
      <a href="<?php echo mem_h(mem_base_url('/member-dashboard.php')); ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
  </form>
  <?php
}
