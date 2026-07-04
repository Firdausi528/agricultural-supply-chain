<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';
require_once __DIR__ . '/classes/Admin.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Load admin using OOP
$admin = new Admin($db);
$admin->load($_SESSION['user_id']);

$current_lang = getCurrentLang();

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

// Get filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT o.*, 
        u.full_name as buyer_name, 
        u.phone as buyer_phone,
        c.crop_name,
        c.location as crop_location,
        f.full_name as farmer_name,
        f.phone as farmer_phone
        FROM orders o 
        JOIN users u ON o.buyer_id = u.id 
        JOIN crops c ON o.crop_id = c.id 
        JOIN users f ON c.farmer_id = f.id 
        WHERE 1=1";

$params = array();

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR f.full_name LIKE ? OR c.crop_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $sql .= " AND o.order_status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY o.order_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Decrypt and translate data
foreach ($orders as $key => $order) {
    $orders[$key]['buyer_name'] = Encryption::decrypt($order['buyer_name']);
    $orders[$key]['farmer_name'] = Encryption::decrypt($order['farmer_name']);
    $crop_name = Encryption::decrypt($order['crop_name']);
    if ($current_lang == 'sw' && isset($crop_translations[$crop_name])) {
        $orders[$key]['crop_name_display'] = $crop_translations[$crop_name];
    } else {
        $orders[$key]['crop_name_display'] = $crop_name;
    }
    $orders[$key]['crop_location'] = Encryption::decrypt($order['crop_location']);
}

// Get status counts
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
$confirmed_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'confirmed'")->fetchColumn();
$transit_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'in_transit'")->fetchColumn();
$delivered_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'delivered'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('all_orders'); ?> - Soko Fresh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body {
            background: #f0f4f0;
            min-height: 100vh;
        }

        .simple-header {
            background: linear-gradient(135deg, #0d1b2a, #1b263b);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .simple-header .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .simple-header .brand i {
            font-size: 24px;
            color: #4caf50;
        }

        .simple-header .brand h2 {
            font-size: 20px;
        }

        .simple-header .brand span {
            color: #4caf50;
        }

        .simple-header a {
            color: white;
            text-decoration: none;
        }

        .simple-header .back-btn {
            background: rgba(255,255,255,0.15);
            padding: 8px 18px;
            border-radius: 8px;
            transition: 0.3s;
            font-size: 14px;
        }

        .simple-header .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 25px auto;
            padding: 0 20px;
        }

        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .page-title h1 {
            color: #1b263b;
            font-size: 24px;
        }

        .page-title h1 i {
            color: #4caf50;
            margin-right: 10px;
        }

        .page-title .total-badge {
            background: #1b263b;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
        }

        .filter-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-bar .status-filter {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-bar .status-filter a {
            padding: 6px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border: 2px solid #e0e0e0;
            color: #555;
            transition: 0.3s;
        }

        .filter-bar .status-filter a:hover {
            border-color: #4caf50;
            color: #4caf50;
        }

        .filter-bar .status-filter a.active {
            background: #4caf50;
            border-color: #4caf50;
            color: white;
        }

        .filter-bar .status-filter a .count {
            background: rgba(0,0,0,0.1);
            padding: 0 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        .filter-bar .search-box {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }

        .filter-bar .search-box form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-bar .search-box input {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            min-width: 200px;
        }

        .filter-bar .search-box input:focus {
            border-color: #4caf50;
            outline: none;
        }

        .filter-bar .search-box button {
            padding: 8px 18px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .filter-bar .search-box button:hover {
            background: #2e7d32;
        }

        .filter-bar .search-box .clear-btn {
            padding: 8px 14px;
            background: #ddd;
            color: #333;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .filter-bar .search-box .clear-btn:hover {
            background: #bbb;
        }

        .orders-table-wrap {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        .orders-table-wrap table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table-wrap th {
            padding: 12px 15px;
            text-align: left;
            background: #f8faf8;
            color: #1b263b;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .orders-table-wrap td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .orders-table-wrap tr:hover td {
            background: #f8faf8;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.pending { background: #fff3e0; color: #e65100; }
        .status-badge.confirmed { background: #e3f2fd; color: #1565c0; }
        .status-badge.in_transit { background: #f3e5f5; color: #6a1b9a; }
        .status-badge.delivered { background: #e8f5e9; color: #2e7d32; }

        .order-id {
            font-weight: 600;
            color: #1b263b;
        }

        .empty-msg {
            text-align: center;
            padding: 40px;
            color: #888;
        }

        .empty-msg i {
            font-size: 50px;
            color: #ddd;
            display: block;
            margin-bottom: 15px;
        }

        .footer {
            text-align: center;
            color: #888;
            font-size: 12px;
            padding: 20px 0 10px 0;
            border-top: 1px solid #e0e0e0;
            margin-top: 25px;
        }

        @media (max-width: 768px) {
            .simple-header {
                flex-wrap: wrap;
                gap: 10px;
                padding: 12px 15px;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-bar .search-box {
                margin-left: 0;
            }
            .filter-bar .search-box input {
                min-width: 100%;
                flex: 1;
            }
            .page-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

    <div class="simple-header">
        <div class="brand">
            <i class="fas fa-leaf"></i>
            <h2>Soko <span>Fresh</span></h2>
        </div>
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> <?php echo t('back_to_dashboard'); ?>
        </a>
    </div>

    <div class="container">

        <div class="page-title">
            <h1><i class="fas fa-shopping-cart"></i> <?php echo t('all_orders'); ?></h1>
            <span class="total-badge"><?php echo t('total'); ?>: <?php echo count($orders); ?> <?php echo t('orders'); ?></span>
        </div>

        <div class="filter-bar">
            <div class="status-filter">
                <a href="all_orders.php" class="<?php echo empty($status_filter) ? 'active' : ''; ?>"><?php echo t('all'); ?></a>
                <a href="?status=pending" class="<?php echo $status_filter == 'pending' ? 'active' : ''; ?>"><?php echo t('pending'); ?> <span class="count"><?php echo $pending_count; ?></span></a>
                <a href="?status=confirmed" class="<?php echo $status_filter == 'confirmed' ? 'active' : ''; ?>"><?php echo t('confirmed'); ?> <span class="count"><?php echo $confirmed_count; ?></span></a>
                <a href="?status=in_transit" class="<?php echo $status_filter == 'in_transit' ? 'active' : ''; ?>"><?php echo t('in_transit'); ?> <span class="count"><?php echo $transit_count; ?></span></a>
                <a href="?status=delivered" class="<?php echo $status_filter == 'delivered' ? 'active' : ''; ?>"><?php echo t('delivered'); ?> <span class="count"><?php echo $delivered_count; ?></span></a>
            </div>

            <div class="search-box">
                <form method="GET" style="display: flex; gap: 8px;">
                    <?php if(!empty($status_filter)): ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="<?php echo t('search_orders'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                    <?php if(!empty($search)): ?>
                        <a href="all_orders.php<?php echo !empty($status_filter) ? '?status='.$status_filter : ''; ?>" class="clear-btn"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="orders-table-wrap">
            <?php if(count($orders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo t('order'); ?> #</th>
                            <th><?php echo t('buyer'); ?></th>
                            <th><?php echo t('farmer'); ?></th>
                            <th><?php echo t('crop'); ?></th>
                            <th><?php echo t('qty'); ?></th>
                            <th><?php echo t('total'); ?></th>
                            <th><?php echo t('location'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('date'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td class="order-id">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['buyer_name']); ?>
                                <br><small style="color:#888; font-size:11px;"><?php echo htmlspecialchars($order['buyer_phone']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['farmer_name']); ?>
                                <br><small style="color:#888; font-size:11px;"><?php echo htmlspecialchars($order['farmer_phone']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($order['crop_name_display']); ?></td>
                            <td><?php echo $order['quantity_ordered']; ?> kg</td>
                            <td>TSh <?php echo number_format($order['total_price']); ?></td>
                            <td><?php echo htmlspecialchars($order['crop_location']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $order['order_status']; ?>">
                                    <?php 
                                    $status_key = 'status_' . $order['order_status'];
                                    echo t($status_key);
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-msg">
                    <i class="fas fa-shopping-cart"></i>
                    <p><?php echo t('no_orders_found'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>