<?php
// DEBUG ENABLED
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

auth();

include __DIR__ . '/header.php';

// Only admin or superadmin can upload users
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: users.php?error=denied");
    exit;
}

$inserted   = $_GET['added'] ?? 0;
$duplicates = $_GET['dupes'] ?? 0;
$errors     = $_GET['errors'] ?? 0;
?>

<!-- <div class="container mt-4"> -->
    <div class="main-content-inner px-4 py-3 d-block w-100">

<div class="container-fluid">

    <h3>User Bulk Upload</h3>

    <!-- Success Message -->
    <?php if (isset($_GET['uploaded'])): ?>
        <div class="alert alert-success">
            <strong>Upload Completed!</strong><br>
            <b>Inserted:</b> <?php echo $inserted; ?><br>
            <b>Duplicates:</b> <?php echo $duplicates; ?><br>
            <b>Errors:</b> <?php echo $errors; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">

            <p><b>Excel Format:</b></p>

            <table class="table table-bordered" style="max-width: 700px;">
                <thead>
                    <tr>
                        <th>employee_id</th>
                        <th>name *</th>
                        <th>email</th>
                        <th>mobile</th>
                        <th>department</th>
                        <th>role *</th>
                        <th>password (if login user)</th>
                        <th>status (1/0)</th>
                    </tr>
                </thead>
            </table>

            <p>
                <a href="user_template.xlsx" class="btn btn-sm btn-success">Download Template</a>
            </p>

            <hr>

            <!-- FIXED: Correct API path -->
            <form action="/itms.arukustech.com/public/api/users_bulk_upload.php" method="POST" enctype="multipart/form-data">


                <div class="mb-3">
                    <label class="form-label">Select Excel File (.xlsx)</label>
                    <input type="file" name="excel_file" accept=".xlsx" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">Upload</button>
                <a href="users.php" class="btn btn-secondary">Back</a>

            </form>

        </div>
    </div>

</div>
</div>

<?php include 'footer.php'; ?>
