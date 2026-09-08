<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
if (!is_admin()) {
    echo "Access denied.";
    exit;
}

include __DIR__ . '/header.php';

$pdo = db();
$rows = $pdo->query("SELECT * FROM inv_categories ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-content-inner px-4 py-3">
    <h3 class="mb-4 fw-bold">Inventory Categories</h3>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Add New Category</h5>
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small fw-bold">Category Name *</label>
                    <input type="text" id="catName" class="form-control" placeholder="Laptop / Printer / Scanner">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold">Asset Prefix *</label>
                    <input type="text" id="catPrefix" class="form-control" placeholder="TECHLAP / TECHPRN">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="addBtn" class="btn btn-primary w-100">Add Category</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="60" class="ps-3">ID</th>
                        <th>Name</th>
                        <th>Prefix</th>
                        <th>Next Code</th>
                        <th width="180" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="ps-3"><?= $r['id'] ?></td>
                        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['asset_prefix']) ?></span></td>
                        <td><code><?= str_pad($r['next_number'], 4, "0", STR_PAD_LEFT) ?></code></td>
                        <td class="text-center">
                            <?php if ($_SESSION['role'] === 'superadmin'): ?>
                                <button 
                                    class="btn btn-info btn-sm editBtn"
                                    data-id="<?= $r['id'] ?>"
                                    data-name="<?= htmlspecialchars($r['name']) ?>"
                                    data-prefix="<?= htmlspecialchars($r['asset_prefix']) ?>"
                                    data-next="<?= $r['next_number'] ?>"
                                >Edit</button>

                                <button 
                                    class="btn btn-danger btn-sm deleteBtn"
                                    data-id="<?= $r['id'] ?>"
                                >Delete</button>
                            <?php else: ?>
                                <span class="badge bg-secondary">View Only</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Category (SuperAdmin)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Name *</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Asset Prefix *</label>
                        <input type="text" id="edit_prefix" name="asset_prefix" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Next Sequence Number *</label>
                        <input type="number" id="edit_next" name="next_number" class="form-control" min="1" required>
                        <small class="text-muted">Manually setting this changes the next generated Asset ID.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pageScript = <<<SCRIPT
<script>
const API = "/api/";

/*****************************
 * ADD CATEGORY
 *****************************/
$("#addBtn").click(function () {
    let name = $("#catName").val().trim();
    let prefix = $("#catPrefix").val().trim();

    if (name === "" || prefix === "") {
        alert("Category Name & Prefix required");
        return;
    }

    $.post(API + "categories_add.php",
        { name: name, asset_prefix: prefix },
        function (res) {
            if (res.status === "success") {
                location.reload();
            } else {
                alert(res.message);
            }
        },
        "json"
    ).fail(() => alert("API failed"));
});


/*****************************
 * OPEN EDIT MODAL
 *****************************/
$(document).on('click', '.editBtn', function () {
    $("#edit_id").val($(this).data("id"));
    $("#edit_name").val($(this).data("name"));
    $("#edit_prefix").val($(this).data("prefix"));
    $("#edit_next").val($(this).data("next"));

    $("#editModal").modal("show");
});


/*****************************
 * UPDATE CATEGORY (Submit Handler)
 *****************************/
$("#editCategoryForm").submit(function (e) {
    e.preventDefault();

    $.post(API + "category_update.php",
        $(this).serialize(),
        function (res) {
            if (res.status === "success") {
                location.reload();
            } else {
                alert(res.message);
            }
        },
        "json"
    ).fail(() => alert("Update failed. Make sure api/category_update.php exists."));
});


/*****************************
 * DELETE CATEGORY
 *****************************/
$(document).on('click', '.deleteBtn', function () {
    let id = $(this).data("id");
    
    if (confirm("Are you sure you want to delete this category? This cannot be undone.")) {
        $.post(API + "categories_delete.php", { id: id }, function (res) {
            if (res.status === "success") {
                location.reload();
            } else {
                alert(res.message);
            }
        }, "json").fail(() => alert("Delete failed. Check if items exist in this category."));
    }
});
</script>
SCRIPT;

include __DIR__ . '/footer.php';
?>