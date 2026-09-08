<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

require_once __DIR__ . '/../db.php';   // Needed for log_action()

/* ---------------------------------------------------------
   LOGIN CHECK FUNCTIONS
---------------------------------------------------------- */
function auth() {
    if (empty($_SESSION['user_id'])) {
        header("Location: /itms.arukustech.com/public/login.php");
        exit;
    }
}

function check_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: /itms.arukustech.com/public/login.php");
        exit;
    }
}

/* ---------------------------------------------------------
   ROLE CHECK FUNCTIONS
---------------------------------------------------------- */
function is_superadmin() {
    return ($_SESSION['role'] ?? '') === 'superadmin';
}

function is_admin() {
    $role = $_SESSION['role'] ?? '';
    return ($role === 'admin' || $role === 'superadmin');
}

function is_user() {
    return ($_SESSION['role'] ?? '') === 'user';
}

/* ---------------------------------------------------------
   GLOBAL SYSTEM ACTIVITY LOGGING FUNCTION
---------------------------------------------------------- */
function log_action($action, $details = "") {
    try {
        $pdo = db();

        $user_id = $_SESSION['user_id'] ?? 0;
        $ip      = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([$user_id, $action, $details, $ip]);

    } catch (Exception $e) {
        // Silent fail (do not break UI even if log fails)
        error_log("Activity Log Error: " . $e->getMessage());
    }
}
