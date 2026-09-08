<?php
include "header.php";
require_once __DIR__ . '/../app/db.php';

$pdo = db();
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = $_POST['title'];
    $color = $_POST['color'];
    $icon = $_POST['icon'];
    $query = trim($_POST['value_query']);
    $status = $_POST['status'];
    $sort = $_POST['sort_order'];

    if (stripos($query, "select") !== 0) {
        $msg = "Only SELECT queries allowed!";
    } else {
        $st = $pdo->prepare("
            INSERT INTO dashboard_cards (title, color, icon, value_query, status, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $st->execute([$title, $color, $icon, $query, $status, $sort]);

        header("Location: dashboard_cards.php");
        exit;
    }
}
?>

<h3>Add Dashboard Card</h3>

<?php if ($msg): ?>
<div class="alert alert-danger"><?= $msg ?></div>
<?php endif; ?>

<div class="row">

    <div class="col-md-7">

        <form method="POST">

            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="title" class="form-control"
                       required onkeyup="updatePreview()">
            </div>

            <div class="mb-3">
                <label>Color</label>
                <select name="color" class="form-control" onchange="updatePreview()">
                    <option value="bg-blue">Blue</option>
                    <option value="bg-green">Green</option>
                    <option value="bg-yellow">Yellow</option>
                    <option value="bg-red">Red</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Icon</label>
                <div class="input-group">
                    <input type="text" name="icon" class="form-control" value="ri-dashboard-line"
                           onkeyup="updatePreview()">
                    <button type="button" class="btn btn-secondary" onclick="openIconPicker()">Pick</button>
                </div>
            </div>

            <div class="mb-3">
                <label>Value SQL Query</label>
                <textarea name="value_query" class="form-control" rows="4"
                          placeholder="SELECT COUNT(*) FROM inventory" required></textarea>
            </div>

            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="0">
            </div>

            <button class="btn btn-success">Save</button>

        </form>
    </div>

    <!-- LIVE PREVIEW -->
    <div class="col-md-5">
        <label>Live Preview</label>

        <div id="cardPreview" class="stat-card bg-blue mt-3">
            <p class="label">Hello-Add</p>
            <h3>0</h3>
            <i class="icon ri-dashboard-line"></i>
        </div>
    </div>

</div>

<script>
function updatePreview() {
    let title = document.querySelector("[name=title]").value;
    let icon = document.querySelector("[name=icon]").value;
    let color = document.querySelector("[name=color]").value;

    document.querySelector("#cardPreview .label").innerText = title || "Preview";
    document.querySelector("#cardPreview .icon").className = "icon " + icon;
    document.getElementById("cardPreview").className = "stat-card " + color;
}

function openIconPicker() {
    new bootstrap.Modal(document.getElementById("iconPickerModal")).show();
}
</script>

<?php include "footer.php"; ?>
<?php include "components/icon_picker.php"; ?>
