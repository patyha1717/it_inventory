<?php
date_default_timezone_set('Asia/Kolkata'); 
$today = date('Y-m-d');

function envv($k, $def = '') {
    static $env = null;
    if ($env === null) {
        $env = @parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW) ?: [];
    }
    return $env[$k] ?? $def;
}

function db() {
    static $pdo = null;

    if (session_status() === PHP_SESSION_NONE) { 
        @session_start(); 
    }

    if ($pdo) return $pdo;

    
    $host = envv('DB_HOST', 'localhost');
    $dbname = envv('DB_NAME', 'itms_prod');
    $charset = envv('DB_CHARSET', 'utf8mb4');
    $user = envv('DB_USER', 'root');
    $pass = envv('DB_PASS', '');

    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        $today = date('Y-m-d');

        // --- TRACKING LOGIC ---
        // 1. TRACK URL VISITS (PRE-LOGIN)
        $hit_key = 'hit_' . $today; 

        if (empty($_SESSION[$hit_key])) {
            $stmt = $pdo->prepare("SELECT slug FROM app_analytics WHERE slug = 'total_url_hits' AND analytics_date = ?");
            $stmt->execute([$today]);
            
            if ($stmt->fetch()) {
                // If row exists, just increment
                $pdo->prepare("UPDATE app_analytics SET hits = hits + 1 WHERE slug = 'total_url_hits' AND analytics_date = ?")
                    ->execute([$today]);
            } else {
                // This will now trigger for the first visitor of every new day
                $pdo->prepare("INSERT INTO app_analytics (slug, analytics_date, hits, successful_logins) VALUES ('total_url_hits', ?, 1, 0)")
                    ->execute([$today]);
            }
            $_SESSION[$hit_key] = true;
        }

        // 2. TRACK SUCCESSFUL LOGINS
        $login_key = 'login_' . $today;
        if (!empty($_SESSION['user_id']) && empty($_SESSION[$login_key])) {
            $pdo->prepare("UPDATE app_analytics SET successful_logins = successful_logins + 1 WHERE slug = 'total_url_hits' AND analytics_date = ?")
                ->execute([$today]);
            $_SESSION[$login_key] = true;
        }

    } catch (PDOException $e) {
        
        die("<b style='color:red;'>Database Connection Error:</b> " . $e->getMessage());
    }

    return $pdo;
}