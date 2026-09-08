<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
$pdo = db();
$message = "";

// Handle Adding New Rented Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_project_name'])) {
    $name = trim($_POST['new_project_name']);
    if (!empty($name)) {
        try {
            $st = $pdo->prepare("INSERT INTO rented_projects (project_name) VALUES (?)");
            $st->execute([$name]);
            $message = "<div class='alert alert-success'>Rented Project added successfully!</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Error: This project name already exists.</div>";
        }
    }
}

// Fetch all projects for the table
$projects = $pdo->query("SELECT * FROM rented_projects ORDER BY project_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Rented Projects</title>
    <link href="/itms.arukustech.com/public/assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="/itms.arukustech.com/public/assets/js/jquery.min.js"></script>
    <script src="/itms.arukustech.com/public/assets/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Rented Projects Section</h3>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">Add New Project</div>
                <div class="card-body">
                    <?= $message ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">PROJECT NAME</label>
                            <input type="text" name="new_project_name" class="form-control" placeholder="Enter project name..." required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save Project</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white fw-bold">Existing Rented Projects</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="100">ID</th>
                                <th>Project Name</th>
                                <th>Created Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><strong><?= htmlspecialchars($p['project_name']) ?></strong></td>
                                <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Note: Deleting projects may affect linked assets. Continue?')">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($projects)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No rented projects added yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>