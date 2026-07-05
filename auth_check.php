<?php
session_start();
require_once 'role_helpers.php';

// Normalize roles stored in session so role checks are consistent
if (isset($_SESSION['role'])) {
    $_SESSION['role'] = normalizeRoles($_SESSION['role']);
}

// Check if user is logged in
$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
    || (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
);

if (!isset($_SESSION['user_id'])) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    header('Location: login.php');
    exit;
}

// Optional: Check session timeout (30 minutes)
$timeout = 30 * 60; // 30 minutes
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
    session_destroy();
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Session expired']);
        exit;
    }
    header('Location: login.php?expired=1');
    exit;
}

// Update login time on each request
$_SESSION['login_time'] = time();
?>