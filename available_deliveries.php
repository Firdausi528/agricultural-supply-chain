<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';
require_once __DIR__ . '/classes/Logistics.php';

// Check if user is logged in and is logistics
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'logistics') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$current_lang = getCurrentLang();

// Load logistics using OOP
$logistics = new Logistics($db);
$logistics->load($user_id);

// Crop translations
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

// Get orders that need delivery (confirmed status)
$sql = "SELECT o.*, 
        c.crop_name,
        c.location as crop_location,
        u.full_name as farmer_name,
        u.phone as farmer_phone,
        b.full_name as buyer_name,
        b.phone as buyer_phone
        FROM orders o 
        JOIN crops c ON o.crop_id = c.id 
        JOIN users u ON c.farmer_id = u.id 
        JOIN users b ON o.buyer_id = b.id 
        WHERE o.order_status = 'confirmed' 
        ORDER BY o.order_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$deliveries = $stmt->fetchAll();

// Decrypt and translate delivery data
foreach ($deliveries as &$delivery) {
    $crop_name = Encryption::decrypt($delivery['crop_name']);
    if ($current_lang == 'sw' && isset($crop_translations[$crop_name])) {
        $delivery['crop_name_display'] = $crop_translations[$crop_name];
    } else {
        $delivery['crop_name_display'] = $crop_name;
    }
    $delivery['crop_location'] = Encryption::decrypt($delivery['crop_location']);
    $delivery['farmer_name'] = Encryption::decrypt($delivery['farmer_name']);
    $delivery['buyer_name'] = Encryption::decrypt($delivery['buyer_name']);
}

// Get logistics own deliveries (in_transit)
$sql = "SELECT o.*, 
        c.crop_name,
        c.location as crop_location,
        u.full_name as farmer_name,
        b.full_name as buyer_name
        FROM orders o 
        JOIN crops c ON o.crop_id = c.id 
        JOIN users u ON c.farmer_id = u.id 
        JOIN users b ON o.buyer_id = b.id 
        WHERE o.logistics_id = ? AND o.order_status = 'in_transit'
        ORDER BY o.order_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$my_deliveries = $stmt->fetchAll();

// Decrypt and translate my deliveries data
foreach ($my_deliveries as &$delivery) {
    $crop_name = Encryption::decrypt($delivery['crop_name']);
    if ($current_lang == 'sw' && isset($crop_translations[$crop_name])) {
        $delivery['crop_name_display'] = $crop_translations[$crop_name];
    } else {
        $delivery['crop_name_display'] = $crop_name;
    }
    $delivery['crop_location'] = Encryption::decrypt($delivery['crop_location']);
    $delivery['farmer_name'] = Encryption::decrypt($delivery['farmer_name']);
    $delivery['buyer_name'] = Encryption::decrypt($delivery['buyer_name']);
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('available_deliveries'); ?> - Soko Fresh</title>
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

        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .page-title h1 {
            color: #1a472a;
            font-size: 24px;
        }

        .page-title h1 i {
            margin-right: 10px;
        }

        .page-title .badge {
            background: #2e7d32;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
        }

        .section-title {
            margin: 30px 0 15px 0;
            color: #1a472a;
            font-size: 18px;
        }

        .section-title i {
            margin-right: 8px;
        }

        .deliveries-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .delivery-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e8f0e8;
            transition: 0.3s;
        }

        .delivery-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .delivery-card .crop-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a472a;
        }

        .delivery-card .order-id {
            font-size: 12px;
            color: #888;
        }

        .delivery-card .details {
            margin: 12px 0;
            padding: 12px 0;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }

        .delivery-card .details .row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 14px;
        }

        .delivery-card .details .row .label {
            color: #888;
        }

        .delivery-card .details .row .value {
            font-weight: 500;
            color: #1a472a;
        }

        .delivery-card .parties {
            font-size: 14px;
            color: #555;
            margin: 10px 0;
        }

        .delivery-card .parties i {
            color: #2e7d32;
            margin-right: 5px;
        }

        .btn-accept {
            display: block;
            text-align: center;
            padding: 10px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-accept:hover {
            background: #1a472a;
            transform: translateY(-2px);
        }

        .btn-accept i {
            margin-right: 8px;
        }

        .btn-complete {
            display: block;
            text-align: center;
            padding: 10px;
            background: #1565c0;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-complete:hover {
            background: #0d47a1;
            transform: translateY(-2px);
        }

        .btn-complete i {
            margin-right: 8px;
        }

        .delivered-label {
            text-align: center;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 8px;
            color: #2e7d32;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.confirmed { background: #e3f2fd; color: #1565c0; }
        .status-badge.in_transit { background: #f3e5f5; color: #6a1b9a; }
        .status-badge.delivered { background: #e8f5e9; color: #2e7d32; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: #1a472a;
            margin-bottom: 8px;
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
            .page-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .deliveries-grid {
                grid-template-columns: 1fr;
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

        <div class="page-title">
            <h1><i class="fas fa-truck"></i> <?php echo t('available_deliveries'); ?></h1>
            <span class="badge"><?php echo count($deliveries); ?> <?php echo t('available'); ?></span>
        </div>

        <!-- Available Deliveries -->
        <?php if(count($deliveries) > 0): ?>
            <h2 class="section-title"><i class="fas fa-clock"></i> <?php echo t('ready_for_pickup'); ?></h2>
            <div class="deliveries-grid">
                <?php foreach($deliveries as $delivery): ?>
                <div class="delivery-card">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="crop-name"><?php echo htmlspecialchars($delivery['crop_name_display']); ?></span>
                        <span class="status-badge <?php echo $delivery['order_status']; ?>">
                            <?php 
                            $status_key = 'status_' . $delivery['order_status'];
                            echo t($status_key);
                            ?>
                        </span>
                    </div>
                    <div class="order-id"><?php echo t('order'); ?> #<?php echo str_pad($delivery['id'], 5, '0', STR_PAD_LEFT); ?></div>

                    <div class="details">
                        <div class="row">
                            <span class="label"><?php echo t('quantity'); ?></span>
                            <span class="value"><?php echo $delivery['quantity_ordered']; ?> kg</span>
                        </div>
                        <div class="row">
                            <span class="label"><?php echo t('total'); ?></span>
                            <span class="value">TSh <?php echo number_format($delivery['total_price']); ?></span>
                        </div>
                        <div class="row">
                            <span class="label"><?php echo t('location'); ?></span>
                            <span class="value"><?php echo htmlspecialchars($delivery['crop_location']); ?></span>
                        </div>
                    </div>

                    <div class="parties">
                        <div><i class="fas fa-user"></i> <?php echo t('farmer'); ?>: <?php echo htmlspecialchars($delivery['farmer_name']); ?> (<?php echo htmlspecialchars($delivery['farmer_phone']); ?>)</div>
                        <div><i class="fas fa-user"></i> <?php echo t('buyer'); ?>: <?php echo htmlspecialchars($delivery['buyer_name']); ?> (<?php echo htmlspecialchars($delivery['buyer_phone']); ?>)</div>
                    </div>

                    <a href="accept_delivery.php?id=<?php echo $delivery['id']; ?>" class="btn-accept" onclick="return confirm('<?php echo t('accept_delivery_confirm'); ?>')">
                        <i class="fas fa-check-circle"></i> <?php echo t('accept_delivery'); ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-truck"></i>
                <h3><?php echo t('no_deliveries_available'); ?></h3>
                <p><?php echo t('no_deliveries_available_msg'); ?></p>
            </div>
        <?php endif; ?>

        <!-- My In-Transit Deliveries -->
        <?php if(count($my_deliveries) > 0): ?>
            <h2 class="section-title"><i class="fas fa-list"></i> <?php echo t('my_in_transit'); ?></h2>
            <div class="deliveries-grid">
                <?php foreach($my_deliveries as $delivery): ?>
                <div class="delivery-card">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="crop-name"><?php echo htmlspecialchars($delivery['crop_name_display']); ?></span>
                        <span class="status-badge <?php echo $delivery['order_status']; ?>">
                            <?php 
                            $status_key = 'status_' . $delivery['order_status'];
                            echo t($status_key);
                            ?>
                        </span>
                    </div>
                    <div class="order-id"><?php echo t('order'); ?> #<?php echo str_pad($delivery['id'], 5, '0', STR_PAD_LEFT); ?></div>

                    <div class="details">
                        <div class="row">
                            <span class="label"><?php echo t('quantity'); ?></span>
                            <span class="value"><?php echo $delivery['quantity_ordered']; ?> kg</span>
                        </div>
                        <div class="row">
                            <span class="label"><?php echo t('total'); ?></span>
                            <span class="value">TSh <?php echo number_format($delivery['total_price']); ?></span>
                        </div>
                        <div class="row">
                            <span class="label"><?php echo t('location'); ?></span>
                            <span class="value"><?php echo htmlspecialchars($delivery['crop_location']); ?></span>
                        </div>
                    </div>

                    <div class="parties">
                        <div><i class="fas fa-user"></i> <?php echo t('farmer'); ?>: <?php echo htmlspecialchars($delivery['farmer_name']); ?></div>
                        <div><i class="fas fa-user"></i> <?php echo t('buyer'); ?>: <?php echo htmlspecialchars($delivery['buyer_name']); ?></div>
                    </div>

                    <a href="complete_delivery.php?id=<?php echo $delivery['id']; ?>" class="btn-complete" onclick="return confirm('<?php echo t('mark_delivered_confirm'); ?>')">
                        <i class="fas fa-check-double"></i> <?php echo t('mark_delivered'); ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>