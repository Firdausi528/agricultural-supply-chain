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

// Get filter period
$period = $_GET['period'] ?? 'all';
$date_filter = '';

if ($period == 'today') {
    $date_filter = "DATE(order_date) = CURDATE()";
} elseif ($period == 'week') {
    $date_filter = "YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($period == 'month') {
    $date_filter = "MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())";
} elseif ($period == 'year') {
    $date_filter = "YEAR(order_date) = YEAR(CURDATE())";
} else {
    $date_filter = "1=1";
}

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

// Get report data
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_farmers = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'farmer'")->fetchColumn();
$total_buyers = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'buyer'")->fetchColumn();
$total_logistics = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'logistics'")->fetchColumn();
$total_crops = $pdo->query("SELECT COUNT(*) FROM crops")->fetchColumn();

// Orders with filter
$sql_total_orders = "SELECT COUNT(*) FROM orders WHERE $date_filter";
$total_orders = $pdo->query($sql_total_orders)->fetchColumn();

$sql_pending = "SELECT COUNT(*) FROM orders WHERE order_status = 'pending' AND $date_filter";
$pending_orders = $pdo->query($sql_pending)->fetchColumn();

$sql_confirmed = "SELECT COUNT(*) FROM orders WHERE order_status = 'confirmed' AND $date_filter";
$confirmed_orders = $pdo->query($sql_confirmed)->fetchColumn();

$sql_delivered = "SELECT COUNT(*) FROM orders WHERE order_status = 'delivered' AND $date_filter";
$delivered_orders = $pdo->query($sql_delivered)->fetchColumn();

$sql_revenue = "SELECT SUM(total_price) FROM orders WHERE order_status = 'delivered' AND $date_filter";
$total_revenue = $pdo->query($sql_revenue)->fetchColumn();

// Top crops
$top_crops = $pdo->query("SELECT c.crop_name, COUNT(o.id) as order_count, SUM(o.quantity_ordered) as total_qty 
                          FROM crops c 
                          JOIN orders o ON c.id = o.crop_id 
                          GROUP BY c.id 
                          ORDER BY order_count DESC LIMIT 5")->fetchAll();

// Translate top crops
foreach ($top_crops as &$crop) {
    $crop_name = Encryption::decrypt($crop['crop_name']);
    if ($current_lang == 'sw' && isset($crop_translations[$crop_name])) {
        $crop['crop_name_display'] = $crop_translations[$crop_name];
    } else {
        $crop['crop_name_display'] = $crop_name;
    }
}

// Top farmers
$top_farmers = $pdo->query("SELECT u.full_name, COUNT(o.id) as order_count, SUM(o.total_price) as total_sales 
                            FROM users u 
                            JOIN crops c ON u.id = c.farmer_id 
                            JOIN orders o ON c.id = o.crop_id 
                            WHERE o.order_status = 'delivered'
                            GROUP BY u.id 
                            ORDER BY total_sales DESC LIMIT 5")->fetchAll();

foreach ($top_farmers as &$farmer) {
    $farmer['full_name'] = Encryption::decrypt($farmer['full_name']);
}

// Monthly orders
$monthly_data = $pdo->query("SELECT DATE_FORMAT(order_date, '%M') as month, COUNT(*) as count 
                             FROM orders 
                             WHERE YEAR(order_date) = YEAR(CURDATE())
                             GROUP BY MONTH(order_date) 
                             ORDER BY MONTH(order_date)")->fetchAll();

// Recent orders
$recent_orders = $pdo->query("SELECT o.*, c.crop_name, u.full_name as buyer_name 
                              FROM orders o 
                              JOIN crops c ON o.crop_id = c.id 
                              JOIN users u ON o.buyer_id = u.id 
                              WHERE $date_filter
                              ORDER BY o.order_date DESC LIMIT 10")->fetchAll();

foreach ($recent_orders as &$order) {
    $crop_name = Encryption::decrypt($order['crop_name']);
    if ($current_lang == 'sw' && isset($crop_translations[$crop_name])) {
        $order['crop_name_display'] = $crop_translations[$crop_name];
    } else {
        $order['crop_name_display'] = $crop_name;
    }
    $order['buyer_name'] = Encryption::decrypt($order['buyer_name']);
}

$period_label = ucfirst($period);
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('reports'); ?> - Soko Fresh</title>
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

        .btn-print {
            background: #1b263b;
            color: white;
            padding: 8px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-print:hover {
            background: #0d1b2a;
            transform: translateY(-2px);
        }

        .period-filter {
            background: white;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .period-filter .label {
            font-weight: 600;
            color: #1b263b;
            font-size: 14px;
        }

        .period-filter .filter-btn {
            padding: 6px 16px;
            border-radius: 6px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #555;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            font-size: 13px;
        }

        .period-filter .filter-btn:hover {
            border-color: #4caf50;
            color: #4caf50;
        }

        .period-filter .filter-btn.active {
            background: #4caf50;
            border-color: #4caf50;
            color: white;
        }

        .period-filter .period-label {
            color: #888;
            font-size: 13px;
            margin-left: auto;
        }

        .period-filter .period-label strong {
            color: #1b263b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-card .number {
            font-size: 22px;
            font-weight: bold;
            color: #1b263b;
        }

        .stat-card .label {
            font-size: 12px;
            color: #888;
            margin-top: 2px;
        }

        .stat-card .icon {
            font-size: 18px;
            margin-bottom: 3px;
            display: block;
        }

        .stat-card.green .number { color: #2e7d32; }
        .stat-card.blue .number { color: #1565c0; }
        .stat-card.orange .number { color: #e65100; }
        .stat-card.purple .number { color: #6a1b9a; }
        .stat-card.red .number { color: #c62828; }
        .stat-card.gold .number { color: #f57f17; }
        .stat-card.teal .number { color: #00695c; }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .report-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .report-section .section-title {
            font-size: 16px;
            color: #1b263b;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
        }

        .report-section .section-title i {
            color: #4caf50;
            margin-right: 8px;
        }

        .report-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-section th, .report-section td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }

        .report-section th {
            color: #666;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }

        .report-section tr:hover td {
            background: #f8faf8;
        }

        .report-section .empty-msg {
            text-align: center;
            padding: 20px;
            color: #888;
            font-size: 14px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-badge.pending { background: #fff3e0; color: #e65100; }
        .status-badge.confirmed { background: #e3f2fd; color: #1565c0; }
        .status-badge.in_transit { background: #f3e5f5; color: #6a1b9a; }
        .status-badge.delivered { background: #e8f5e9; color: #2e7d32; }

        .footer {
            text-align: center;
            color: #888;
            font-size: 12px;
            padding: 20px 0 10px 0;
            border-top: 1px solid #e0e0e0;
            margin-top: 20px;
        }

        @media print {
            .simple-header, .page-title .btn-print, .period-filter, .footer {
                display: none !important;
            }
            .container { margin: 0; padding: 0; }
            .report-section { box-shadow: none; border: 1px solid #ddd; }
            .stat-card { box-shadow: none; border: 1px solid #eee; }
            body { background: white; }
        }

        @media (max-width: 768px) {
            .simple-header {
                flex-wrap: wrap;
                gap: 10px;
                padding: 12px 15px;
            }
            .two-col {
                grid-template-columns: 1fr;
            }
            .period-filter {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            .period-filter .period-label {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .stat-card .number {
                font-size: 18px;
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

    <div class="container" id="reportContent">

        <div class="page-title">
            <h1><i class="fas fa-chart-bar"></i> <?php echo t('reports'); ?></h1>
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> <?php echo t('print'); ?>
            </button>
        </div>

        <div class="period-filter">
            <span class="label"><i class="fas fa-calendar-alt"></i> <?php echo t('period'); ?>:</span>
            <a href="?period=all" class="filter-btn <?php echo $period == 'all' ? 'active' : ''; ?>"><?php echo t('all_time'); ?></a>
            <a href="?period=today" class="filter-btn <?php echo $period == 'today' ? 'active' : ''; ?>"><?php echo t('today'); ?></a>
            <a href="?period=week" class="filter-btn <?php echo $period == 'week' ? 'active' : ''; ?>"><?php echo t('this_week'); ?></a>
            <a href="?period=month" class="filter-btn <?php echo $period == 'month' ? 'active' : ''; ?>"><?php echo t('this_month'); ?></a>
            <a href="?period=year" class="filter-btn <?php echo $period == 'year' ? 'active' : ''; ?>"><?php echo t('this_year'); ?></a>
            <span class="period-label"><?php echo t('showing'); ?>: <strong><?php echo $period_label; ?></strong></span>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue"><span class="icon">👥</span><div class="number"><?php echo $total_users; ?></div><div class="label"><?php echo t('users'); ?></div></div>
            <div class="stat-card green"><span class="icon">🚜</span><div class="number"><?php echo $total_farmers; ?></div><div class="label"><?php echo t('farmers'); ?></div></div>
            <div class="stat-card blue"><span class="icon">🛒</span><div class="number"><?php echo $total_buyers; ?></div><div class="label"><?php echo t('buyers'); ?></div></div>
            <div class="stat-card orange"><span class="icon">🚚</span><div class="number"><?php echo $total_logistics; ?></div><div class="label"><?php echo t('logistics'); ?></div></div>
            <div class="stat-card green"><span class="icon">🌾</span><div class="number"><?php echo $total_crops; ?></div><div class="label"><?php echo t('crops'); ?></div></div>
            <div class="stat-card purple"><span class="icon">📦</span><div class="number"><?php echo $total_orders; ?></div><div class="label"><?php echo t('orders'); ?></div></div>
            <div class="stat-card gold"><span class="icon">💰</span><div class="number">TSh <?php echo number_format($total_revenue ?? 0); ?></div><div class="label"><?php echo t('revenue'); ?></div></div>
            <div class="stat-card red"><span class="icon">⏳</span><div class="number"><?php echo $pending_orders; ?></div><div class="label"><?php echo t('pending'); ?></div></div>
            <div class="stat-card teal"><span class="icon">✅</span><div class="number"><?php echo $delivered_orders; ?></div><div class="label"><?php echo t('delivered'); ?></div></div>
        </div>

        <div class="two-col">
            <div class="report-section">
                <div class="section-title"><i class="fas fa-seedling"></i> <?php echo t('top_crops'); ?></div>
                <?php if(count($top_crops) > 0): ?>
                    <table>
                        <thead><tr><th>#</th><th><?php echo t('crop'); ?></th><th><?php echo t('orders'); ?></th></tr></thead>
                        <tbody>
                            <?php $i = 1; foreach($top_crops as $crop): ?>
                            <tr><td><?php echo $i++; ?></td><td><?php echo htmlspecialchars($crop['crop_name_display']); ?></td><td><?php echo $crop['order_count']; ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-msg"><?php echo t('no_data'); ?></div>
                <?php endif; ?>
            </div>

            <div class="report-section">
                <div class="section-title"><i class="fas fa-trophy"></i> <?php echo t('top_farmers'); ?></div>
                <?php if(count($top_farmers) > 0): ?>
                    <table>
                        <thead><tr><th>#</th><th><?php echo t('farmer'); ?></th><th><?php echo t('sales'); ?></th></tr></thead>
                        <tbody>
                            <?php $i = 1; foreach($top_farmers as $farmer): ?>
                            <tr><td><?php echo $i++; ?></td><td><?php echo htmlspecialchars($farmer['full_name']); ?></td><td>TSh <?php echo number_format($farmer['total_sales']); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-msg"><?php echo t('no_data'); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="report-section full-width" style="margin-bottom: 25px;">
            <div class="section-title"><i class="fas fa-chart-line"></i> <?php echo t('monthly_orders'); ?> (<?php echo date('Y'); ?>)</div>
            <?php if(count($monthly_data) > 0): ?>
                <table>
                    <thead><tr><th><?php echo t('month'); ?></th><th><?php echo t('orders'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach($monthly_data as $month): ?>
                        <tr><td><?php echo $month['month']; ?></td><td><?php echo $month['count']; ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-msg"><?php echo t('no_data'); ?></div>
            <?php endif; ?>
        </div>

        <div class="report-section full-width">
            <div class="section-title"><i class="fas fa-history"></i> <?php echo t('recent_orders'); ?></div>
            <?php if(count($recent_orders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo t('buyer'); ?></th>
                            <th><?php echo t('crop'); ?></th>
                            <th><?php echo t('qty'); ?></th>
                            <th><?php echo t('total'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('date'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['crop_name_display']); ?></td>
                            <td><?php echo $order['quantity_ordered']; ?> kg</td>
                            <td>TSh <?php echo number_format($order['total_price']); ?></td>
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
                <div class="empty-msg"><?php echo t('no_orders_found'); ?></div>
            <?php endif; ?>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> | <?php echo t('report_generated'); ?>: <?php echo date('F j, Y H:i'); ?>
        </div>
    </div>

</body>
</html>