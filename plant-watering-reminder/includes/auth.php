<?php
session_start();
require 'includes/config.php';

// Debug: Check if session is active
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log("Session is not active.");
}

// Debug: Check if user_id is set
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Log the current user details
error_log("Current User ID in auth.php: " . $_SESSION['user_id']);
error_log("Current User Name: " . $_SESSION['first_name'] . " " . $_SESSION['last_name']);
?>