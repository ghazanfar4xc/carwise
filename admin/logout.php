<?php
define('APP_RUNNING', true);
require __DIR__ . '/includes/bootstrap.php';
logout_user();
redirect('admin/login.php');
