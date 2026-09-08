<?php
include "header.php";
require_once __DIR__ . '/../app/db.php';

$pdo = db();
$id = (int)$_GET['id'];

$card = $pdo->query("SELECT * FROM dashboard_cards WHERE id=$id")->fetch();
if (!$card) { die("Card not found"); }

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
            UPDATE dashboard_cards 
            SET title=?, color=?, icon=?, value_query=?, status=?, sort_order=? 
            WHERE id=?
        ");

        $st->execute([$title, $color, $icon, $query, $status, $sort, $id]);

        header("Location: dashboard_cards.php");
        exit;
    }
}
?>

<h3>Edit Dashboard Card</h3>

<?php if ($msg): ?>
<div class="alert alert-danger"><?= $msg ?></div>
<?php endif; ?>

<div class="row">

    <div class="col-md-7">

        <form method="POST">

            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="title" class="form-control"
                    required value="<?= $card['title'] ?>" onkeyup="updatePreview()">
            </div>

            <div class="mb-3">
                <label>Card Color</label>
                <select name="color" class="form-control" onchange="updatePreview()">
                    <option value="bg-blue"   <?= $card['color']=='bg-blue'?'selected':'' ?>>Blue</option>
                    <option value="bg-green"  <?= $card['color']=='bg-green'?'selected':'' ?>>Green</option>
                    <option value="bg-yellow" <?= $card['color']=='bg-yellow'?'selected':'' ?>>Yellow</option>
                    <option value="bg-red"    <?= $card['color']=='bg-red'?'selected':'' ?>>Red</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Card Icon</label>
                <div class="input-group">
                    <input type="text" name="icon" class="form-control"
                        value="<?= $card['icon'] ?>" onkeyup="updatePreview()">
                    <button type="button" class="btn btn-secondary" onclick="openIconPicker()">Pick Icon</button>
                </div>
            </div>

            <div class="mb-3">
                <label>Value Query (SELECT only)</label>
                <textarea name="value_query" class="form-control" rows="4" required><?= $card['value_query'] ?></textarea>
            </div>

            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="1" <?= $card['status']==1?'selected':'' ?>>Active</option>
                    <option value="0" <?= $card['status']==0?'selected':'' ?>>Inactive</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Sort Order</label>
                <input type="number" name="sort_order" class="form-control"
                    value="<?= $card['sort_order'] ?>">
            </div>

            <button class="btn btn-primary">Update Card</button>

        </form>
    </div>

    <!-- LIVE PREVIEW -->
    <div class="col-md-5">
        <label>Live Preview</label>
        <div id="cardPreview" class="stat-card <?= $card['color'] ?> mt-3">
            <p class="label"><?= $card['title'] ?></p>
            <h3>0</h3>
            <i class="icon <?= $card['icon'] ?>"></i>
        </div>
    </div>

</div>

<script>
function updatePreview() {
    let title = document.querySelector("[name=title]").value;
    let icon = document.querySelector("[name=icon]").value;
    let color = document.querySelector("[name=color]").value;

    document.querySelector("#cardPreview .label").innerText = title;
    document.querySelector("#cardPreview .icon").className = "icon " + icon;
    document.getElementById("cardPreview").className = "stat-card " + color;
}

function openIconPicker() {
    new bootstrap.Modal(document.getElementById("iconPickerModal")).show();
}
</script>

<?php include "footer.php"; ?>
<?php include "components/icon_picker.php"; ?>
