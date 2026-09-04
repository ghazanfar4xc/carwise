<?php
define('APP_RUNNING', true);
require __DIR__ . '/includes/bootstrap.php';

// preview tokens are single-purpose: log out destroys the token session too
if (PREVIEW_APT !== '') {
    apt_destroy(PREVIEW_APT);
}
logout_user();
header('Location: ' . url('admin/login.php'));
exit;
