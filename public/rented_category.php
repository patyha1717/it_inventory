<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

if (!in_array($_SESSION['role'], ['admin','superadmin'])) {
    die("Access denied");
}

$pdo = db();
$msg = '';
$editData = null;

/* =========================
   ADD / UPDATE CATEGORY
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $msg = "Category name is required";
    } else {

        // UPDATE
        if (!empty($_POST['id'])) {
            $st = $pdo->prepare(
                "UPDATE rented_categories SET name = ? WHERE id = ?"
            );
            $st->execute([$name, $_POST['id']]);
            $msg = "Category updated successfully";
        }

        // INSERT
        else {
            $st = $pdo->prepare(
                "INSERT INTO rented_categories (name) VALUES (?)"
            );
            $st->execute([$name]);
            $msg = "Category added successfully";
        }
    }
}

/* =========================
   EDIT LOAD
========================= */
if (!empty($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM rented_categories WHERE id = ?");
    $st->execute([$_GET['edit']]);
    $editData = $st->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   STATUS TOGGLE
========================= */
if (!empty($_GET['toggle'])) {
    $st = $pdo->prepare(
        "UPDATE rented_categories 
         SET status = IF(status=1,0,1)
         WHERE id = ?"
    );
    $st->execute([$_GET['toggle']]);
    header("Location: rented_category.php");
    exit;
}

/* =========================
   DELETE
========================= */
if (!empty($_GET['delete'])) {
    $st = $pdo->prepare("DELETE FROM rented_categories WHERE id = ?");
    $st->execute([$_GET['delete']]);
    header("Location: rented_category.php");
    exit;
}

/* =========================
   FETCH LIST
========================= */
$categories = $pdo
    ->query("SELECT * FROM rented_categories ORDER BY id DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="main-content-inner px-4 py-3">

    <h3 class="mb-3">Rented Categories</h3>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- ADD / EDIT FORM -->
    <div class="card mb-4">
        <div class="card-header fw-bold">
            <?= $editData ? 'Edit Category' : 'Add Category' ?>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Category Name *</label>
                        <input type="text"
                               name="name"
                               class="form-control"
                               required
                               value="<?= htmlspecialchars($editData['name'] ?? '') ?>">
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary">
                            <?= $editData ? 'Update' : 'Add' ?>
                        </button>
                        <?php if ($editData): ?>
                            <a href="rented_category.php" class="btn btn-secondary ms-2">Cancel</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- CATEGORY LIST -->
    <div class="card">
        <div class="card-body">
            <table id="categoryTable" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th width="160">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td>
                            <?php if ($c['status']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="?toggle=<?= $c['id'] ?>"
                               class="btn btn-sm btn-info"
                               onclick="return confirm('Change status?')">
                               Toggle
                            </a>
                            <a href="?delete=<?= $c['id'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete category?')">
                               Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- DATATABLE -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
$(function () {
    $('#categoryTable').DataTable({
        dom: 'Bfrtip',
        order: [[0, 'desc']],
        buttons: [
            { extend: 'excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdf', className: 'btn btn-danger btn-sm' },
            { extend: 'print', className: 'btn btn-info btn-sm' }
        ]
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
