<?php
require 'includes/auth.php';
require 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plant_id'])) {
    markAsWatered($_POST['plant_id']);
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
exit();
?>