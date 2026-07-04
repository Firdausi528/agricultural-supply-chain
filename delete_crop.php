<?php
session_start();
require_once __DIR__ . '/config/config.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'farmer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$crop_id = $_GET['id'] ?? 0;

// Check if crop belongs to this farmer
$sql = "SELECT * FROM crops WHERE id = ? AND farmer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$crop_id, $user_id]);
$crop = $stmt->fetch();

if ($crop) {
    // Delete the crop
    $sql = "DELETE FROM crops WHERE id = ? AND farmer_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$crop_id, $user_id]);
}

// Redirect back to my crops
header('Location: my_crops.php');
exit();
?>