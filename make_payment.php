<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/classes/Buyer.php';

// Check if user is logged in and is buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'buyer') {
    header('Location: login.php');
    exit();
}

$buyer_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

// Load buyer using OOP
$buyer = new Buyer($db);
$buyer->load($buyer_id);

// Get order details with farmer_id
$sql = "SELECT o.*, c.crop_name, u.full_name as farmer_name, u.phone as farmer_phone, c.farmer_id 
        FROM orders o 
        JOIN crops c ON o.crop_id = c.id 
        JOIN users u ON c.farmer_id = u.id 
        WHERE o.id = ? AND o.buyer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $buyer_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

// Decrypt order data
$order['crop_name'] = Encryption::decrypt($order['crop_name']);
$order['farmer_name'] = Encryption::decrypt($order['farmer_name']);

// Check if already PAID
$sql = "SELECT * FROM transactions WHERE order_id = ? AND transaction_type = 'payment' AND payment_status = 'paid'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$paid_transaction = $stmt->fetch();

// Check if already PENDING
$sql = "SELECT * FROM transactions WHERE order_id = ? AND transaction_type = 'payment' AND payment_status = 'pending'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$pending_transaction = $stmt->fetch();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];
    $amount = $order['total_price'];
    $farmer_id = $order['farmer_id']; // Get farmer_id from order
    
    if ($paid_transaction) {
        $error = "❌ This order has already been paid.";
    } else if ($pending_transaction) {
        // Update existing pending transaction
        $sql = "UPDATE transactions SET payment_method = ?, payment_status = 'pending', payment_date = NOW(), farmer_id = ? WHERE order_id = ? AND transaction_type = 'payment'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$payment_method, $farmer_id, $order_id]);
        
        $sql = "UPDATE orders SET payment_status = 'pending' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        
        $message = "✅ Payment updated! Waiting for farmer confirmation.";
        
        $sql = "SELECT * FROM transactions WHERE order_id = ? AND transaction_type = 'payment' AND payment_status = 'pending'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        $pending_transaction = $stmt->fetch();
    } else {
        try {
            // Create new transaction with farmer_id
            $sql = "INSERT INTO transactions (order_id, amount, payment_method, payment_status, payment_date, buyer_id, farmer_id, transaction_type) 
                    VALUES (?, ?, ?, 'pending', NOW(), ?, ?, 'payment')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$order_id, $amount, $payment_method, $buyer_id, $farmer_id]);
            
            $sql = "UPDATE orders SET payment_status = 'pending' WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$order_id]);
            
            $message = "✅ Payment submitted! Waiting for farmer confirmation.";
            
            $sql = "SELECT * FROM transactions WHERE order_id = ? AND transaction_type = 'payment' AND payment_status = 'pending'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$order_id]);
            $pending_transaction = $stmt->fetch();
        } catch(PDOException $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Payment - Agricultural Supply</title>
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
            color: #1565c0;
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

        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            background: #f9f9f9;
            transition: 0.3s;
        }

        .form-group select:focus {
            border-color: #1565c0;
            outline: none;
        }

        .btn-pay {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1565c0, #1a73e8);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-pay:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(21,101,192,0.3);
        }

        .btn-pay i {
            margin-right: 8px;
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
                <h1><i class="fas fa-credit-card"></i> Make Payment</h1>
                <p>Pay for order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></p>
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
                    <span class="label">Farmer</span>
                    <span class="value"><?php echo htmlspecialchars($order['farmer_name']); ?></span>
                </div>
                <div class="row">
                    <span class="label">Quantity</span>
                    <span class="value"><?php echo $order['quantity_ordered']; ?> kg</span>
                </div>
                <div class="row">
                    <span class="label">Total Amount</span>
                    <span class="value" style="font-size:18px; color:#1565c0;">TSh <?php echo number_format($order['total_price']); ?></span>
                </div>
            </div>

            <?php if($order['payment_status'] == 'paid'): ?>
                <div class="payment-status paid">
                    <i class="fas fa-check-circle"></i> Payment Confirmed
                </div>
            <?php elseif($order['payment_status'] == 'pending'): ?>
                <div class="payment-status pending">
                    <i class="fas fa-clock"></i> Payment Pending - Waiting for Farmer Confirmation
                </div>
            <?php endif; ?>

            <?php if($order['payment_status'] != 'paid'): ?>
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-money"></i> Payment Method</label>
                        <select name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="mpesa">📱 M-Pesa</option>
                            <option value="tigo_pesa">📱 Tigo Pesa</option>
                            <option value="airtel_money">📱 Airtel Money</option>
                            <option value="cash">💵 Cash</option>
                            <option value="bank">🏦 Bank Transfer</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-pay">
                        <i class="fas fa-check-circle"></i> 
                        <?php echo ($order['payment_status'] == 'pending') ? 'Update Payment' : 'Pay Now'; ?>
                    </button>
                </form>
                <?php if($order['payment_status'] == 'pending'): ?>
                    <p style="color:#888; text-align:center; margin-top:10px; font-size:13px;">
                        <i class="fas fa-info-circle"></i> Payment already submitted. You can update the payment method if needed.
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($order['payment_status'] == 'paid' && $paid_transaction): ?>
                <div style="background:#e8f5e9; padding:15px; border-radius:10px; margin-top:15px;">
                    <p style="color:#2e7d32; text-align:center;">
                        <i class="fas fa-check-circle"></i> Payment confirmed on <?php echo date('F j, Y', strtotime($paid_transaction['payment_date'])); ?>
                        <br>
                        <small>Method: <?php echo ucfirst($paid_transaction['payment_method']); ?></small>
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