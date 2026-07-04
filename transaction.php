<?php
session_start();
include 'config/config.php';

// Check if user is logged in and is farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'farmer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

// Get order details
$sql = "SELECT o.*, c.crop_name, u.full_name as buyer_name, u.phone as buyer_phone 
        FROM orders o 
        JOIN crops c ON o.crop_id = c.id 
        JOIN users u ON o.buyer_id = u.id 
        WHERE o.id = ? AND c.farmer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

// Check if transaction already exists
$sql = "SELECT * FROM transactions WHERE order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$transaction = $stmt->fetch();

$message = '';
$error = '';

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];
    $amount = $order['total_price'];
    
    if ($transaction) {
        // Update existing transaction
        $sql = "UPDATE transactions SET payment_method = ?, payment_status = 'paid', payment_date = NOW() WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$payment_method, $order_id]);
        
        // Update order payment status
        $sql = "UPDATE orders SET payment_status = 'paid' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        
        $message = "✅ Payment confirmed successfully!";
    } else {
        // Create new transaction
        $sql = "INSERT INTO transactions (order_id, amount, payment_method, payment_status, payment_date, buyer_id, farmer_id) 
                VALUES (?, ?, ?, 'paid', NOW(), ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id, $amount, $payment_method, $order['buyer_id'], $user_id]);
        
        // Update order payment status
        $sql = "UPDATE orders SET payment_status = 'paid' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        
        $message = "✅ Payment confirmed successfully!";
    }
    
    // Refresh transaction
    $sql = "SELECT * FROM transactions WHERE order_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);
    $transaction = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transaction - Agricultural Supply</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body {
            background: #f0f4f0;
            min-height: 100vh;
        }

        .top-header {
            background: linear-gradient(135deg, #1a472a, #2e7d32);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .top-header .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-header .brand i {
            font-size: 28px;
        }

        .top-header .brand h2 {
            font-size: 22px;
        }

        .top-header a {
            color: white;
            text-decoration: none;
        }

        .top-header .back-btn {
            background: rgba(255,255,255,0.15);
            padding: 8px 18px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .top-header .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 700px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .card .title {
            text-align: center;
            margin-bottom: 20px;
        }

        .card .title h1 {
            color: #1a472a;
            font-size: 24px;
        }

        .card .title h1 i {
            color: #2e7d32;
            margin-right: 10px;
        }

        .card .title p {
            color: #888;
            font-size: 14px;
            margin-top: 5px;
        }

        .order-summary {
            background: #f8faf8;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .order-summary .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
            border-bottom: 1px solid #e8f0e8;
        }

        .order-summary .row:last-child {
            border-bottom: none;
        }

        .order-summary .row .label {
            color: #888;
        }

        .order-summary .row .value {
            font-weight: 600;
            color: #1a472a;
        }

        .payment-status {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .payment-status.paid {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .payment-status.pending {
            background: #fff3e0;
            color: #e65100;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            background: #f9f9f9;
            transition: 0.3s;
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: #2e7d32;
            outline: none;
        }

        .btn-confirm {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a472a, #2e7d32);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(46,125,50,0.3);
        }

        .btn-confirm i {
            margin-right: 8px;
        }

        .btn-confirm:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .message.error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .message i {
            margin-right: 8px;
        }

        .footer {
            text-align: center;
            color: #888;
            font-size: 13px;
            padding: 20px 0;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .top-header {
                flex-wrap: wrap;
                gap: 10px;
                padding: 12px 15px;
            }
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="top-header">
        <div class="brand">
            <i class="fas fa-leaf"></i>
            <h2>Soko Fresh</h2>
        </div>
        <a href="my_orders.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>

    <div class="container">

        <div class="card">
            <div class="title">
                <h1><i class="fas fa-credit-card"></i> Transaction</h1>
                <p>Confirm payment for order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></p>
            </div>

            <?php if($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Order Summary -->
            <div class="order-summary">
                <div class="row">
                    <span class="label">Order #</span>
                    <span class="value">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="row">
                    <span class="label">Crop</span>
                    <span class="value"><?php echo htmlspecialchars($order['crop_name']); ?></span>
                </div>
                <div class="row">
                    <span class="label">Buyer</span>
                    <span class="value"><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                </div>
                <div class="row">
                    <span class="label">Quantity</span>
                    <span class="value"><?php echo $order['quantity_ordered']; ?> kg</span>
                </div>
                <div class="row">
                    <span class="label">Total Amount</span>
                    <span class="value" style="font-size:18px; color:#2e7d32;">TSh <?php echo number_format($order['total_price']); ?></span>
                </div>
            </div>

            <!-- Payment Status -->
            <?php if($order['payment_status'] == 'paid'): ?>
                <div class="payment-status paid">
                    <i class="fas fa-check-circle"></i> Payment Confirmed
                </div>
            <?php else: ?>
                <div class="payment-status pending">
                    <i class="fas fa-clock"></i> Payment Pending
                </div>
            <?php endif; ?>

            <!-- Payment Form (only if not paid) -->
            <?php if($order['payment_status'] != 'paid'): ?>
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-money"></i> Payment Method</label>
                        <select name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="cash">💵 Cash</option>
                            <option value="mpesa">📱 M-Pesa</option>
                            <option value="bank">🏦 Bank Transfer</option>
                            <option value="tigo_pesa">📱 Tigo Pesa</option>
                            <option value="airtel_money">📱 Airtel Money</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-confirm">
                        <i class="fas fa-check-circle"></i> Confirm Payment
                    </button>
                </form>
            <?php endif; ?>

            <?php if($order['payment_status'] == 'paid' && $transaction): ?>
                <div style="background:#e8f5e9; padding:15px; border-radius:10px; margin-top:15px;">
                    <p style="color:#2e7d32; text-align:center;">
                        <i class="fas fa-check-circle"></i> Payment confirmed on <?php echo date('F j, Y', strtotime($transaction['payment_date'])); ?>
                        <br>
                        <small>Method: <?php echo ucfirst($transaction['payment_method']); ?></small>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> Soko Fresh - Agricultural Supply Chain Platform
        </div>
    </div>

</body>
</html>