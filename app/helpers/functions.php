<?php

function safe($v) {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function now() {
    return date('Y-m-d H:i:s');
}
