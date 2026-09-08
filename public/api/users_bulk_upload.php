<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

auth(); // login required

// Only admin or superadmin can bulk upload
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: /users.php?error=denied");
    exit;
}

$pdo = db();

// Validate method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /users_bulk_upload.php?error=invalid");
    exit;
}

// Validate file
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== 0) {
    header("Location: /users_bulk_upload.php?error=nofile");
    exit;
}

$filePath = $_FILES['excel_file']['tmp_name'];

// Load Excel
try {
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
} catch (Exception $e) {
    header("Location: /users_bulk_upload.php?error=badexcel");
    exit;
}

$inserted = 0;
$duplicates = 0;
$errors = 0;

foreach ($rows as $index => $row) {

    if ($index == 1) continue; // Skip header row

    // Clean all fields
    $employee_id = trim((string)$row["A"]);
    $name        = trim((string)$row["B"]);
    $email       = trim((string)$row["C"]);
    $mobile      = trim((string)$row["D"]);
    $department  = trim((string)$row["E"]);
    $role        = strtolower(trim((string)$row["F"]));   // FIXED (case insensitive role)
    $password    = trim((string)$row["G"]);
    $status      = trim((string)$row["H"]);

    // Mandatory fields
    if ($name === "" || $role === "") {
        $errors++;
        continue;
    }

    // Valid roles
    $allowed_roles = ["employee", "user", "admin", "superadmin"];
    if (!in_array($role, $allowed_roles)) {
        $errors++;
        continue;
    }

    // Status validation
    if ($status === "" || !in_array($status, ["0", "1"])) {
        $status = 1; // default active
    }

    // Password required for login roles
    if ($role !== "employee") {
    if ($password == "") {
        $errors++;
        continue;
    }
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    } else {
    // employee ? no login, set empty password instead of NULL (NOT NULL column)
    $hashedPassword = "";
    }

    // Duplicate email check only if email exists
    if ($email !== "") {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $duplicates++;
            continue;
        }
    }

    // Insert user
    try {
        $sql = "INSERT INTO users 
            (employee_id, name, email, mobile, department, role, password, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $employee_id,
            $name,
            $email,
            $mobile,
            $department,
            $role,
            $hashedPassword,
            $status
        ]);

        $inserted++;

    } catch (Exception $e) {
        $errors++;
        continue;
    }
}

// Redirect back to UI page
header("Location: /users_bulk_upload.php?uploaded=1&added={$inserted}&dupes={$duplicates}&errors={$errors}");
exit;

?>
