<?php
define('APP_RUNNING', true);
require __DIR__ . '/includes/bootstrap.php';
require_admin();
redirect('admin/dashboard.php');
