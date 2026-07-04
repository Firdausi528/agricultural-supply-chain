<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Logistics.php';

// Check if user is logged in and is logistics
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'logistics') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? 0;

// Load logistics using OOP
$logistics = new Logistics($db);
$logistics->load($user_id);

// Check if order exists and is assigned to this logistics
$sql = "SELECT * FROM orders WHERE id = ? AND logistics_id = ? AND order_status = 'in_transit'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: my_deliveries.php');
    exit();
}

// Update order status to delivered
$sql = "UPDATE orders SET order_status = 'delivered' WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);

// Redirect back
header('Location: my_deliveries.php');
exit();
?>