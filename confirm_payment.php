<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';
require_once __DIR__ . '/classes/Farmer.php';

// Check if user is logged in and is farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'farmer') {
    header('Location: login.php');
    exit();
}

$farmer_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

// Load farmer using OOP
$farmer = new Farmer($db);
$farmer->load($farmer_id);

// Get order details
$sql = "SELECT o.*, c.crop_name, u.full_name as buyer_name 
        FROM orders o 
        JOIN crops c ON o.crop_id = c.id 
        JOIN users u ON o.buyer_id = u.id 
        WHERE o.id = ? AND c.farmer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $farmer_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

// Decrypt order data
$order['crop_name'] = Encryption::decrypt($order['crop_name']);
$order['buyer_name'] = Encryption::decrypt($order['buyer_name']);

$message = '';
$error = '';

// Get transaction
$sql = "SELECT * FROM transactions WHERE order_id = ? AND transaction_type = 'payment'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$transaction = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($transaction) {
        // Update transaction to paid
        $sql = "UPDATE transactions SET payment_status = 'paid', confirmed_by_farmer = 'yes' WHERE order_id = ? AND transaction_type = 'payment'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        
        // Update order payment status
        $sql = "UPDATE orders SET payment_status = 'paid' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        
        // Create delivery fee transaction for logistics
        $delivery_fee = 5000;
        
        $sql = "SELECT logistics_id FROM orders WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        $order_data = $stmt->fetch();
        $logistics_id = $order_data['logistics_id'];
        
        if ($logistics_id) {
            $sql = "SELECT * FROM transactions WHERE order_id = ? AND transaction_type = 'delivery_fee'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$order_id]);
            $delivery_trans = $stmt->fetch();
            
            if (!$delivery_trans) {
                $sql = "INSERT INTO transactions (order_id, amount, payment_method, payment_status, payment_date, logistics_id, farmer_id, transaction_type, delivery_fee) 
                        VALUES (?, ?, 'cash', 'paid', NOW(), ?, ?, 'delivery_fee', ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$order_id, $delivery_fee, $logistics_id, $_SESSION['user_id'], $delivery_fee]);
            }
        }
        
        $message = "✅ " . t('payment_confirmed');
        
        // Refresh transaction
        $sql = "SELECT * FROM transactions WHERE order_id = ? AND transaction_type = 'payment'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id]);
        $transaction = $stmt->fetch();
    } else {
        $error = "❌ " . t('no_payment_found');
    }
}

$current_lang = getCurrentLang();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('payment_confirm'); ?> - Soko Fresh</title>
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
            <h2><?php echo t('brand'); ?></h2>
        </div>
        <a href="my_orders.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> <?php echo t('back_to_orders'); ?>
        </a>
    </div>

    <div class="container">

        <div class="card">
            <div class="title">
                <h1><i class="fas fa-check-circle"></i> <?php echo t('payment_confirm'); ?></h1>
                <p><?php echo t('confirm_payment_for'); ?> #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></p>
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
                    <span class="label"><?php echo t('order'); ?> #</span>
                    <span class="value">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="row">
                    <span class="label"><?php echo t('crop'); ?></span>
                    <span class="value"><?php echo htmlspecialchars($order['crop_name']); ?></span>
                </div>
                <div class="row">
                    <span class="label"><?php echo t('buyer'); ?></span>
                    <span class="value"><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                </div>
                <div class="row">
                    <span class="label"><?php echo t('amount'); ?></span>
                    <span class="value" style="font-size:18px; color:#2e7d32;">TSh <?php echo number_format($order['total_price']); ?></span>
                </div>
            </div>

            <?php if($order['payment_status'] == 'paid'): ?>
                <div class="payment-status paid">
                    <i class="fas fa-check-circle"></i> <?php echo t('payment_confirmed'); ?>
                </div>
            <?php elseif($transaction && $transaction['payment_status'] == 'pending'): ?>
                <div class="payment-status pending">
                    <i class="fas fa-clock"></i> <?php echo t('payment_pending_confirm'); ?>
                </div>
            <?php endif; ?>

            <?php if($transaction && $transaction['payment_status'] == 'pending' && $order['payment_status'] != 'paid'): ?>
                <form method="POST">
                    <button type="submit" class="btn-confirm">
                        <i class="fas fa-check-circle"></i> <?php echo t('confirm_payment_received'); ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if(!$transaction): ?>
                <div style="background:#ffebee; padding:15px; border-radius:10px; text-align:center; color:#c62828;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo t('no_payment_made'); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>