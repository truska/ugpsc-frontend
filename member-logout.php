<?php
require_once __DIR__ . '/includes/member/auth.php';

mem_logout();
header('Location: ' . mem_base_url('/member-login.php'));
exit;
