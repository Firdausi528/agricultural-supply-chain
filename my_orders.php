<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';
require_once __DIR__ . '/classes/Farmer.php';
require_once __DIR__ . '/classes/Buyer.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$current_lang = getCurrentLang();

// Crop translation array for display
$crop_translations = array(
    'Maize' => 'Mahindi',
    'Rice' => 'Mchele',
    'Beans' => 'Maharage',
    'Tomatoes' => 'Nyanya',
    'Onions' => 'Vitunguu',
    'Potatoes' => 'Viazi',
    'Cassava' => 'Mihogo',
    'Sweet Potatoes' => 'Viazi Vitamu',
    'Cabbage' => 'Kabichi',
    'Spinach' => 'Mchicha',
    'Carrots' => 'Karoti',
    'Peppers' => 'Pilipili',
    'Avocado' => 'Parachichi',
    'Mangoes' => 'Maembe',
    'Oranges' => 'Machungwa',
    'Bananas' => 'Ndizi',
    'Pineapples' => 'Mananasi',
    'Coffee' => 'Kahawa',
    'Tea' => 'Chai',
    'Sunflower' => 'Alizeti',
    'Cotton' => 'Pamba',
    'Sugarcane' => 'Miwa',
    'Groundnuts' => 'Karanga',
    'Soybeans' => 'Soya'
);

// Load user based on type
if ($user_type == 'farmer') {
    $user = new Farmer($db);
    $user->load($user_id);
} elseif ($user_type == 'buyer') {
    $user = new Buyer($db);
    $user->load($user_id);
} else {
    header('Location: dashboard.php');
    exit();
}

// Get orders based on user type
if ($user_type == 'buyer') {
    $sql = "SELECT o.*, c.crop_name, c.location, u.full_name as farmer_name, u.phone as farmer_phone 
            FROM orders o 
            JOIN crops c ON o.crop_id = c.id 
            JOIN users u ON c.farmer_id = u.id 
            WHERE o.buyer_id = ? 
            ORDER BY o.order_date DESC";
} else {
    $sql = "SELECT o.*, c.crop_name, c.location, u.full_name as buyer_name, u.phone as buyer_phone 
            FROM orders o 
            JOIN crops c ON o.crop_id = c.id 
            JOIN users u ON o.buyer_id = u.id 
            WHERE c.farmer_id = ? 
            ORDER BY o.order_date DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Decrypt order data and translate crop names
$decrypted_orders = array();
foreach ($orders as $order) {
    $decrypted = array();
    $decrypted['id'] = $order['id'];
    $decrypted['buyer_id'] = $order['buyer_id'];
    $decrypted['crop_id'] = $order['crop_id'];
    $decrypted['quantity_ordered'] = $order['quantity_ordered'];
    $decrypted['total_price'] = $order['total_price'];
    $decrypted['order_status'] = $order['order_status'];
    $decrypted['order_date'] = $order['order_date'];
    $decrypted['payment_status'] = $order['payment_status'] ?? 'pending';
    
    // Decrypt and translate crop name
    $crop_name = Encryption::decrypt($order['crop_name']);
    $decrypted['crop_name'] = $crop_name;
    if ($current_lang == 'sw' && isset($crop_translations[$crop_name])) {
        $decrypted['crop_name_display'] = $crop_translations[$crop_name];
    } else {
        $decrypted['crop_name_display'] = $crop_name;
    }
    
    $decrypted['location'] = Encryption::decrypt($order['location']);
    
    if ($user_type == 'buyer') {
        $decrypted['farmer_name'] = Encryption::decrypt($order['farmer_name']);
        $decrypted['farmer_phone'] = $order['farmer_phone'];
    } else {
        $decrypted['buyer_name'] = Encryption::decrypt($order['buyer_name']);
        $decrypted['buyer_phone'] = $order['buyer_phone'];
    }
    
    $decrypted_orders[] = $decrypted;
}
$orders = $decrypted_orders;

// Count orders by status
$pending_count = 0;
$confirmed_count = 0;
$delivered_count = 0;
foreach ($orders as $order) {
    if ($order['order_status'] == 'pending') $pending_count++;
    if ($order['order_status'] == 'confirmed') $confirmed_count++;
    if ($order['order_status'] == 'delivered') $delivered_count++;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('my_orders'); ?> - Soko Fresh</title>
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
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .page-header h1 {
            color: #1a472a;
            font-size: 28px;
        }

        .page-header h1 i {
            margin-right: 10px;
        }

        .btn-dashboard {
            background: #666;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-dashboard:hover {
            background: #444;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-item {
            background: white;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-item .number {
            font-size: 28px;
            font-weight: bold;
            color: #1a472a;
        }

        .stat-item .label {
            font-size: 13px;
            color: #888;
            margin-top: 3px;
        }

        .stat-item.pending .number { color: #e65100; }
        .stat-item.confirmed .number { color: #1565c0; }
        .stat-item.delivered .number { color: #2e7d32; }

        .orders-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #e8f0e8;
            transition: 0.3s;
        }

        .order-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }

        .order-card .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .order-card .order-header .order-info h3 {
            color: #1a472a;
            font-size: 20px;
        }

        .order-card .order-header .order-info .order-meta {
            color: #888;
            font-size: 13px;
            margin-top: 3px;
        }

        .order-card .order-header .order-info .order-meta i {
            margin-right: 5px;
        }

        .order-card .order-header .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.pending { background: #fff3e0; color: #e65100; }
        .status-badge.confirmed { background: #e3f2fd; color: #1565c0; }
        .status-badge.in_transit { background: #f3e5f5; color: #6a1b9a; }
        .status-badge.delivered { background: #e8f5e9; color: #2e7d32; }

        .order-card .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            padding: 15px 0;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-card .order-details .detail-item .label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }

        .order-card .order-details .detail-item .value {
            font-size: 16px;
            font-weight: 600;
            color: #1a472a;
            margin-top: 2px;
        }

        .order-card .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .order-card .order-footer .user-info {
            color: #555;
            font-size: 14px;
        }

        .order-card .order-footer .user-info i {
            color: #2e7d32;
            margin-right: 5px;
        }

        .order-card .order-footer .status-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .order-card .order-footer .status-actions a,
        .order-card .order-footer .status-actions button {
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-confirm {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-confirm:hover {
            background: #1565c0;
            color: white;
        }

        .btn-transit {
            background: #f3e5f5;
            color: #6a1b9a;
        }
        .btn-transit:hover {
            background: #6a1b9a;
            color: white;
        }

        .btn-deliver {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .btn-deliver:hover {
            background: #2e7d32;
            color: white;
        }

        .btn-pay-order {
            background: #1565c0;
            color: white;
        }
        .btn-pay-order:hover {
            background: #0d47a1;
            color: white;
        }

        .btn-confirm-payment {
            background: #2e7d32;
            color: white;
        }
        .btn-confirm-payment:hover {
            background: #1a472a;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 80px;
            color: #c8e6c9;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            color: #1a472a;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #888;
        }

        .footer {
            text-align: center;
            color: #888;
            font-size: 13px;
            padding: 30px 0 20px 0;
            border-top: 1px solid #e0e0e0;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .top-header {
                flex-wrap: wrap;
                gap: 10px;
                padding: 12px 15px;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .order-card .order-header {
                flex-direction: column;
                gap: 10px;
            }
            .order-card .order-footer {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
            .order-card .order-footer .status-actions {
                justify-content: center;
            }
            .stats-bar {
                grid-template-columns: repeat(3, 1fr);
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
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> <?php echo t('back_to_dashboard'); ?>
        </a>
    </div>

    <div class="container">

        <div class="page-header">
            <h1><i class="fas fa-shopping-cart"></i> <?php echo t('my_orders'); ?></h1>
            <a href="dashboard.php" class="btn-dashboard">
                <i class="fas fa-home"></i> <?php echo t('nav_dashboard'); ?>
            </a>
        </div>

        <?php if(count($orders) > 0): ?>
        <div class="stats-bar">
            <div class="stat-item pending">
                <div class="number"><?php echo $pending_count; ?></div>
                <div class="label">⏳ <?php echo t('pending'); ?></div>
            </div>
            <div class="stat-item confirmed">
                <div class="number"><?php echo $confirmed_count; ?></div>
                <div class="label">✅ <?php echo t('confirmed'); ?></div>
            </div>
            <div class="stat-item delivered">
                <div class="number"><?php echo $delivered_count; ?></div>
                <div class="label">📦 <?php echo t('delivered'); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if(count($orders) > 0): ?>
            <div class="orders-grid">
                <?php foreach($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3><?php echo htmlspecialchars($order['crop_name_display']); ?></h3>
                            <div class="order-meta">
                                <i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y, g:i A', strtotime($order['order_date'])); ?>
                                &nbsp;|&nbsp; <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['location']); ?>
                            </div>
                        </div>
                       <span class="status-badge <?php echo $order['order_status']; ?>">
    <?php 
    $status_key = 'status_' . $order['order_status'];
    echo t($status_key);
    ?>
</span>
                    </div>

                    <div class="order-details">
                        <div class="detail-item">
                            <div class="label"><?php echo t('quantity'); ?></div>
                            <div class="value"><?php echo $order['quantity_ordered']; ?> kg</div>
                        </div>
                        <div class="detail-item">
                            <div class="label"><?php echo t('total'); ?></div>
                            <div class="value">TSh <?php echo number_format($order['total_price']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="label"><?php echo t('order'); ?></div>
                            <div class="value">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                        </div>
                    </div>

                    <div class="order-footer">
                        <div class="user-info">
                            <?php if($user_type == 'buyer'): ?>
                                <i class="fas fa-user"></i> <?php echo t('farmer'); ?>: <?php echo htmlspecialchars($order['farmer_name']); ?>
                                &nbsp;|&nbsp; <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['farmer_phone']); ?>
                            <?php else: ?>
                                <i class="fas fa-user"></i> <?php echo t('buyer'); ?>: <?php echo htmlspecialchars($order['buyer_name']); ?>
                                &nbsp;|&nbsp; <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['buyer_phone']); ?>
                            <?php endif; ?>
                        </div>

                        <div class="status-actions">
                            <?php if($user_type == 'buyer'): ?>
                                <?php if($order['payment_status'] != 'paid'): ?>
                                    <a href="make_payment.php?order_id=<?php echo $order['id']; ?>" class="btn-pay-order">
                                        <i class="fas fa-credit-card"></i> <?php echo t('pay_now'); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#2e7d32; font-weight:bold;">
                                        <i class="fas fa-check-circle"></i> <?php echo t('paid'); ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if($user_type == 'farmer' && $order['order_status'] != 'delivered'): ?>
                                <?php if($order['order_status'] == 'pending'): ?>
                                    <a href="assign_logistics.php?order_id=<?php echo $order['id']; ?>" class="btn-confirm">
                                        <i class="fas fa-check"></i> <?php echo t('confirm_assign'); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if($order['order_status'] == 'confirmed'): ?>
                                    <?php if($order['payment_status'] != 'paid'): ?>
                                        <a href="confirm_payment.php?order_id=<?php echo $order['id']; ?>" class="btn-confirm-payment">
                                            <i class="fas fa-check-circle"></i> <?php echo t('payment_confirm'); ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="update_order.php?id=<?php echo $order['id']; ?>&status=in_transit" class="btn-transit">
                                            <i class="fas fa-truck"></i> <?php echo t('in_transit'); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if($order['order_status'] == 'in_transit'): ?>
                                    <a href="update_order.php?id=<?php echo $order['id']; ?>&status=delivered" class="btn-deliver">
                                        <i class="fas fa-check-circle"></i> <?php echo t('deliver'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h2><?php echo t('no_orders'); ?></h2>
                <p>
                    <?php if($user_type == 'buyer'): ?>
                        <?php echo t('no_orders_buyer'); ?>
                    <?php else: ?>
                        <?php echo t('no_orders_farmer'); ?>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>