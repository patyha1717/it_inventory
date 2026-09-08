<?php
require_once __DIR__ . '/../app/db.php';
$pdo = db();

$id = (int)$_GET['id'];
$pdo->exec("DELETE FROM dashboard_cards WHERE id=$id");

header("Location: dashboard_cards.php");
exit;
