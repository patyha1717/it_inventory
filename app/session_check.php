<?php
ob_start();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$inactive_timeout = 1 * 60; // 15 minutes

if (isset($_SESSION['LAST_ACTIVITY'])) {
    if (time() - $_SESSION['LAST_ACTIVITY'] > $inactive_timeout) {
        session_unset();
        session_destroy();
        ob_end_clean();
        header("Location: /itms.arukustech.com/public/login.php?timeout=1");
        exit;
    }
}

$_SESSION['LAST_ACTIVITY'] = time();
