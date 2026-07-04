<?php
// Database configuration using OOP
require_once __DIR__ . '/../classes/Database.php';

// Create database object
$db = new Database();
$pdo = $db->getConnection();

// Include encryption helper
require_once __DIR__ . '/encryption.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>