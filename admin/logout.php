<?php
require_once dirname(__DIR__) . '/includes/auth.php';
logoutUser();
header('Location: /Web/admin/login.php');
exit;