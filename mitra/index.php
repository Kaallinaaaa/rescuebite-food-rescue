<?php
session_start();
require_once "../includes/auth_check.php";
require_role("mitra");
echo "Halo mitra, " . htmlspecialchars($_SESSION["nama"]);