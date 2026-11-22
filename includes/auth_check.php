<?php
function require_login() {
    if (!isset($_SESSION["user_id"])) {
        header("Location: /rescuebite-food-rescue/auth/login.php");
        exit;
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION["role"] !== $role) {
        http_response_code(403);
        die("Akses ditolak.");
    }
}