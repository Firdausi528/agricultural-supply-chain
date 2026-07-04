<?php
session_start();
include 'config/config.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'farmer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? '';

// Valid statuses
$valid_statuses = ['confirmed', 'assigned', 'in_transit', 'delivered'];

if (!in_array($status, $valid_statuses)) {
    header('Location: my_orders.php');
    exit();
}

// Check if this order belongs to this farmer
$sql = "SELECT o.*, c.farmer_id FROM orders o 
        JOIN crops c ON o.crop_id = c.id 
        WHERE o.id = ? AND c.farmer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if ($order) {
    // Check if logistics_id is posted
    $logistics_id = $_POST['logistics_id'] ?? null;
    
    if ($logistics_id) {
        // Update with logistics_id
        $sql = "UPDATE orders SET order_status = ?, logistics_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $logistics_id, $order_id]);
    } else {
        // Update only status
        $sql = "UPDATE orders SET order_status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $order_id]);
    }
}

// Redirect back to my orders
header('Location: my_orders.php');
exit();
?>