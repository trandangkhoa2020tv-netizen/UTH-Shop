<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/flash.php';

logoutUser();
flashInfo('Bạn đã đăng xuất khỏi khu vực quản trị.');
header('Location: /Web/admin/login.php');
exit();
