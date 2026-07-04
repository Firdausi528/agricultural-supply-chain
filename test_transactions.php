<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please login first.<br>";
    echo "<a href='login.php'>Login</a>";
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

echo "<h2>User Information</h2>";
echo "User ID: " . $user_id . "<br>";
echo "User Type: " . $user_type . "<br><br>";

// Get ALL transactions for this user (no filters)
echo "<h2>All Transactions</h2>";

// Check if user is buyer
$sql = "SELECT * FROM transactions WHERE buyer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$buyer_trans = $stmt->fetchAll();
echo "Buyer transactions: " . count($buyer_trans) . "<br>";

// Check if user is farmer
$sql = "SELECT * FROM transactions WHERE farmer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$farmer_trans = $stmt->fetchAll();
echo "Farmer transactions: " . count($farmer_trans) . "<br>";

// Check if user is logistics
$sql = "SELECT * FROM transactions WHERE logistics_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$logistics_trans = $stmt->fetchAll();
echo "Logistics transactions: " . count($logistics_trans) . "<br><br>";

// Show all transactions in the entire table
echo "<h2>All Transactions in Database</h2>";
$sql = "SELECT * FROM transactions";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$all_trans = $stmt->fetchAll();
echo "Total transactions in database: " . count($all_trans) . "<br><br>";

if (count($all_trans) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Order ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Buyer ID</th><th>Farmer ID</th><th>Logistics ID</th><th>Type</th><th>Date</th></tr>";
    foreach ($all_trans as $t) {
        echo "<tr>";
        echo "<td>" . $t['id'] . "</td>";
        echo "<td>" . $t['order_id'] . "</td>";
        echo "<td>" . $t['amount'] . "</td>";
        echo "<td>" . $t['payment_method'] . "</td>";
        echo "<td>" . $t['payment_status'] . "</td>";
        echo "<td>" . $t['buyer_id'] . "</td>";
        echo "<td>" . $t['farmer_id'] . "</td>";
        echo "<td>" . $t['logistics_id'] . "</td>";
        echo "<td>" . $t['transaction_type'] . "</td>";
        echo "<td>" . $t['payment_date'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No transactions found in the database.";
}
?>