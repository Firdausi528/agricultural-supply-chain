<?php
session_start();
include 'config/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = $_GET['id'] ?? 0;

if ($user_id) {
    // Check if user is not admin (prevent deleting admin)
    $sql = "SELECT user_type FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['user_type'] != 'admin') {
        // Delete the user
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    }
}

// Redirect back to manage users
header('Location: manage_users.php');
exit();
?>