<?php
require_once __DIR__ . '/includes/member/ui.php';

$code = mem_normalize_magic_code((string) ($_GET['code'] ?? $_POST['code'] ?? ''));
if ($code !== '' && mem_validate_magic_code($code, 'renewal')) {
  header('Location: ' . mem_base_url('/member-renew-quick.php?code=' . urlencode($code)));
  exit;
}

$invalid = $code !== '';
mem_page_header('UGPSC Members | Renewal Code', ['active' => 'join']);
?>
<div class="container" style="max-width:620px;">
  <div class="mem-card p-4 p-lg-5">
    <h1 class="display-font h3 mb-2">Renew Membership</h1>
    <p class="text-secondary">Enter the eight-character renewal code printed on your letter.</p>
    <?php if ($invalid): ?>
      <div class="alert alert-danger">That code is invalid, expired, or has already been used.</div>
    <?php endif; ?>
    <form method="post" class="row g-3">
      <div class="col-12">
        <label class="form-label mem-label" for="code">Renewal Code</label>
        <input
          class="form-control form-control-lg text-uppercase"
          id="code"
          name="code"
          value="<?php echo mem_h($code); ?>"
          maxlength="8"
          autocomplete="one-time-code"
          required
        >
      </div>
      <div class="col-12">
        <button class="btn btn-mem-primary" type="submit">Continue</button>
      </div>
    </form>
  </div>
</div>
<?php mem_page_footer(); ?>
