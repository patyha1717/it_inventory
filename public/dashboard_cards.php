<?php
include "header.php";
require_once __DIR__ . '/../app/db.php';

$pdo = db();

/* FETCH CARDS */
$cards = $pdo->query("SELECT * FROM dashboard_cards ORDER BY sort_order ASC")->fetchAll();
?>

<div class="d-flex justify-content-between mb-4">
    <h3>Dashboard Cards</h3>
    <a href="dashboard_cards_add.php" class="btn btn-success">+ Add Card</a>
</div>

<style>
.sort-item {
    padding: 10px;
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    margin-bottom: 10px;
    cursor: move;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sort-item:hover {
    background: #f9fafb;
}
</style>

<div id="sortable">
    <?php foreach ($cards as $c): ?>
    <div class="sort-item" data-id="<?= $c['id'] ?>">
        <div>
            <strong><?= $c['title'] ?></strong><br>
            <small class="text-muted"><?= $c['value_query'] ?></small>
        </div>

        <div>
            <a href="dashboard_cards_edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
            <a href="dashboard_cards_delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger"
                onclick="return confirm('Delete this card?')">Delete</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
$("#sortable").sortable({
    update: function() {
        let order = [];
        $(".sort-item").each(function(){
            order.push($(this).data("id"));
        });

        $.post("update_card_order.php", {order: order}, function(res){
            console.log("Updated:", res);
        });
    }
});
</script>

<?php include "footer.php"; ?>
