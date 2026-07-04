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

// Check if order exists and is available (confirmed)
$sql = "SELECT * FROM orders WHERE id = ? AND order_status = 'confirmed'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: available_deliveries.php');
    exit();
}

// Assign delivery to this logistics provider
$sql = "UPDATE orders SET logistics_id = ?, order_status = 'in_transit' WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $order_id]);

// Redirect back to available deliveries
header('Location: available_deliveries.php');
exit();
?>