<?php
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

if (!is_admin()) {
    header("Location: /assigned_list.php?error=noaccess");
    exit;
}

$assign_id = intval($_GET['id']);
if ($assign_id <= 0) {
    header("Location: /assigned_list.php?error=invalid");
    exit;
}

// Auto-submit POST form so that API receives assign_id in POST
?>
<form id="f" method="POST" action="/itms.arukustech.com/public/api/unassign_item.php">
    <input type="hidden" name="assign_id" value="<?= $assign_id ?>">
    <input type="hidden" name="remarks" value="Unassigned from UI">
</form>

<script>
document.getElementById("f").submit();
</script>
