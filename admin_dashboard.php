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
$dashboard_data = $admin->getDashboard();
$admin_name = $_SESSION['user_name'];
$current_lang = getCurrentLang();

// Get recent orders (decrypted and translated)
$recent_orders = $pdo->query("SELECT o.*, c.crop_name, u.full_name as buyer_name 
                              FROM orders o 
                              JOIN crops c ON o.crop_id = c.id 
                              JOIN users u ON o.buyer_id = u.id 
                              ORDER BY o.order_date DESC LIMIT 5")->fetchAll();

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

foreach ($recent_orders as &$order) {
    $crop_name = Encryption::decrypt($order['crop_name']);
    if ($current_lang == 'sw' && isset($crop_translations[$crop_name])) {
        $order['crop_name_display'] = $crop_translations[$crop_name];
    } else {
        $order['crop_name_display'] = $crop_name;
    }
    $order['buyer_name'] = Encryption::decrypt($order['buyer_name']);
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('admin_panel'); ?> - Soko Fresh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body {
            background: #f0f4f0;
            min-height: 100vh;
        }

        .top-header {
            background: linear-gradient(135deg, #0d1b2a, #1b263b);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .top-header .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .top-header .brand i {
            font-size: 28px;
            color: #4caf50;
        }

        .top-header .brand h2 {
            font-size: 22px;
        }

        .top-header .brand span {
            color: #4caf50;
        }

        .top-header .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .top-header .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #4caf50;
        }

        .top-header .user-info .user-details {
            text-align: right;
        }

        .top-header .user-info .user-details .name {
            font-weight: bold;
            font-size: 15px;
        }

        .top-header .user-info .user-details .role {
            font-size: 12px;
            opacity: 0.7;
            color: #4caf50;
        }

        .logout-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 8px 18px;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.15);
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-section {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-left: 5px solid #4caf50;
        }

        .welcome-section h1 {
            color: #1b263b;
            font-size: 24px;
        }

        .welcome-section h1 i {
            color: #4caf50;
            margin-right: 10px;
        }

        .welcome-section p {
            color: #888;
            font-size: 14px;
            margin-top: 3px;
        }

        .welcome-section .date-time {
            color: #666;
            font-size: 14px;
            text-align: right;
        }

        .welcome-section .date-time i {
            margin-right: 5px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-card .icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-card .icon.blue { background: #e3f2fd; color: #1565c0; }
        .stat-card .icon.green { background: #e8f5e9; color: #2e7d32; }
        .stat-card .icon.orange { background: #fff3e0; color: #e65100; }
        .stat-card .icon.purple { background: #f3e5f5; color: #6a1b9a; }
        .stat-card .icon.red { background: #ffebee; color: #c62828; }
        .stat-card .icon.gold { background: #fff8e1; color: #f57f17; }
        .stat-card .icon.teal { background: #e0f2f1; color: #00695c; }

        .stat-card .info h3 {
            font-size: 22px;
            color: #1b263b;
        }

        .stat-card .info p {
            color: #888;
            font-size: 12px;
            margin-top: 2px;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .section-title h2 {
            color: #1b263b;
            font-size: 20px;
        }

        .section-title h2 i {
            color: #4caf50;
            margin-right: 8px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: 0.3s;
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-4px);
            border-color: #4caf50;
            box-shadow: 0 8px 25px rgba(76,175,80,0.15);
        }

        .action-card i {
            font-size: 30px;
            margin-bottom: 8px;
            display: block;
        }

        .action-card .action-label {
            font-size: 14px;
            font-weight: 600;
        }

        .action-card .action-desc {
            font-size: 11px;
            color: #888;
            margin-top: 3px;
        }

        .action-card.green i { color: #2e7d32; }
        .action-card.blue i { color: #1565c0; }
        .action-card.orange i { color: #e65100; }
        .action-card.purple i { color: #6a1b9a; }
        .action-card.red i { color: #c62828; }

        .recent-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .recent-section table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .recent-section th {
            padding: 12px 15px;
            text-align: left;
            background: #f8faf8;
            color: #1b263b;
            font-weight: 600;
            font-size: 13px;
        }

        .recent-section td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .recent-section tr:hover td {
            background: #f8faf8;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.pending { background: #fff3e0; color: #e65100; }
        .status-badge.confirmed { background: #e3f2fd; color: #1565c0; }
        .status-badge.in_transit { background: #f3e5f5; color: #6a1b9a; }
        .status-badge.delivered { background: #e8f5e9; color: #2e7d32; }

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
            .top-header .user-info {
                flex-wrap: wrap;
                gap: 10px;
            }
            .welcome-section {
                flex-direction: column;
                text-align: center;
            }
            .welcome-section .date-time {
                text-align: center;
                margin-top: 10px;
            }
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .stat-card {
                padding: 12px;
                flex-direction: column;
                text-align: center;
            }
            .stat-card .icon {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }
            .stat-card .info h3 {
                font-size: 18px;
            }
            .recent-section {
                padding: 15px;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

    <div class="top-header">
        <div class="brand">
            <i class="fas fa-leaf"></i>
            <h2>Soko <span>Fresh</span></h2>
        </div>
        <div class="user-info">
            <div class="avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="user-details">
                <div class="name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="role"><?php echo t('admin'); ?></div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?>
            </a>
        </div>
    </div>

    <div class="container">

        <div class="welcome-section">
            <div>
                <h1><i class="fas fa-chart-line"></i> <?php echo t('admin_panel'); ?></h1>
                <p><?php echo t('welcome_admin'); ?></p>
            </div>
            <div class="date-time">
                <i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
                <br>
                <i class="far fa-clock"></i> <?php echo date('h:i A'); ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon blue"><i class="fas fa-users"></i></div>
                <div class="info">
                    <h3><?php echo $dashboard_data['total_users'] ?? 0; ?></h3>
                    <p><?php echo t('total_users'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon green"><i class="fas fa-tractor"></i></div>
                <div class="info">
                    <h3><?php echo $dashboard_data['farmers'] ?? 0; ?></h3>
                    <p><?php echo t('farmers'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon blue"><i class="fas fa-shopping-bag"></i></div>
                <div class="info">
                    <h3><?php echo $dashboard_data['buyers'] ?? 0; ?></h3>
                    <p><?php echo t('buyers'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon orange"><i class="fas fa-truck"></i></div>
                <div class="info">
                    <h3><?php echo $dashboard_data['logistics'] ?? 0; ?></h3>
                    <p><?php echo t('logistics'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon green"><i class="fas fa-seedling"></i></div>
                <div class="info">
                    <h3><?php echo $dashboard_data['crops'] ?? 0; ?></h3>
                    <p><?php echo t('total_crops'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon purple"><i class="fas fa-shopping-cart"></i></div>
                <div class="info">
                    <h3><?php echo $dashboard_data['orders'] ?? 0; ?></h3>
                    <p><?php echo t('total_orders'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon gold"><i class="fas fa-coins"></i></div>
                <div class="info">
                    <h3>TSh <?php echo number_format($dashboard_data['revenue'] ?? 0); ?></h3>
                    <p><?php echo t('total_revenue'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon red"><i class="fas fa-clock"></i></div>
                <div class="info">
                    <h3><?php echo $dashboard_data['pending'] ?? 0; ?></h3>
                    <p><?php echo t('pending_orders'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon teal"><i class="fas fa-check-circle"></i></div>
                <div class="info">
                    <h3><?php echo $dashboard_data['delivered'] ?? 0; ?></h3>
                    <p><?php echo t('delivered'); ?></p>
                </div>
            </div>
        </div>

        <div class="section-title">
            <h2><i class="fas fa-bolt"></i> <?php echo t('quick_actions'); ?></h2>
        </div>

        <div class="actions-grid">
            <a href="manage_users.php" class="action-card blue">
                <i class="fas fa-users-cog"></i>
                <div class="action-label"><?php echo t('manage_users'); ?></div>
                <div class="action-desc"><?php echo t('view_all_users'); ?></div>
            </a>
            <a href="all_crops.php" class="action-card green">
                <i class="fas fa-seedling"></i>
                <div class="action-label"><?php echo t('all_crops'); ?></div>
                <div class="action-desc"><?php echo t('view_all_listings'); ?></div>
            </a>
            <a href="all_orders.php" class="action-card purple">
                <i class="fas fa-shopping-cart"></i>
                <div class="action-label"><?php echo t('all_orders'); ?></div>
                <div class="action-desc"><?php echo t('view_all_orders'); ?></div>
            </a>
            <a href="reports.php" class="action-card orange">
                <i class="fas fa-chart-bar"></i>
                <div class="action-label"><?php echo t('reports'); ?></div>
                <div class="action-desc"><?php echo t('view_analytics'); ?></div>
            </a>
        </div>

        <div class="recent-section">
            <div class="section-title">
                <h2><i class="fas fa-history"></i> <?php echo t('recent_orders'); ?></h2>
                <a href="all_orders.php" style="color: #4caf50; text-decoration: none; font-weight: 600;"><?php echo t('view_all'); ?> →</a>
            </div>
            <?php if(count($recent_orders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo t('buyer'); ?></th>
                            <th><?php echo t('crop'); ?></th>
                            <th><?php echo t('qty'); ?></th>
                            <th><?php echo t('total'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('date'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach($recent_orders as $order): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
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
                <p style="text-align: center; padding: 30px; color: #888;"><?php echo t('no_orders_yet'); ?></p>
            <?php endif; ?>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>