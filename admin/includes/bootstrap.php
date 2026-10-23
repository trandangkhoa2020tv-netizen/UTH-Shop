<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if (isset($_SESSION['user_id'])) {
    $roleQuery = $conn->prepare('SELECT role FROM users WHERE id = :id');
    $roleQuery->execute([':id' => (int)$_SESSION['user_id']]);
    $currentRole = $roleQuery->fetchColumn();
    if ($currentRole === false) {
        logoutUser();
    } else {
        $_SESSION['user_role'] = $currentRole;
    }
}

requireAdmin();
