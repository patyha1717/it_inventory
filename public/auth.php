<?php
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* Prevent direct access */
if (!defined('ALLOW_AUTH')) {
    define('ALLOW_AUTH', true);
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
       header("Location: /itms.arukustech.com/public/login.php");
        exit;
    }
}
