<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';
require_once __DIR__ . '/classes/Farmer.php';
require_once __DIR__ . '/classes/Buyer.php';
require_once __DIR__ . '/classes/Logistics.php';
require_once __DIR__ . '/classes/Admin.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Create the appropriate user object based on user type
$user = null;
$dashboard_data = array();

switch ($user_type) {
    case 'farmer':
        $user = new Farmer($db);
        if ($user->load($user_id)) {
            $dashboard_data = $user->getDashboard();
            $dashboard_data['farm_name'] = Encryption::decrypt($dashboard_data['farm_name']);
        }
        break;
    case 'buyer':
        $user = new Buyer($db);
        if ($user->load($user_id)) {
            $dashboard_data = $user->getDashboard();
            $dashboard_data['business_name'] = Encryption::decrypt($dashboard_data['business_name']);
        }
        break;
    case 'logistics':
        $user = new Logistics($db);
        if ($user->load($user_id)) {
            $dashboard_data = $user->getDashboard();
        }
        break;
    case 'admin':
        $user = new Admin($db);
        if ($user->load($user_id)) {
            $dashboard_data = $user->getDashboard();
        }
        break;
    default:
        header('Location: logout.php');
        exit();
}

$user_name = $_SESSION['user_name'];
$user_data = $user;
$current_lang = getCurrentLang();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('dashboard_title'); ?> - Soko Fresh</title>
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
            position: sticky;
            top: 0;
            z-index: 100;
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

        .top-header .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .top-header .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            overflow: hidden;
        }

        .top-header .user-info .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            opacity: 0.8;
        }

        .logout-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            padding: 8px 18px;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-left: 5px solid #2e7d32;
        }

        .welcome-section .greeting h1 {
            font-size: 26px;
            color: #1a472a;
        }

        .welcome-section .greeting p {
            color: #666;
            margin-top: 5px;
        }

        .welcome-section .greeting .business-name {
            color: #2e7d32;
            font-weight: bold;
            font-size: 16px;
            margin-top: 5px;
        }

        .welcome-section .greeting .business-name i {
            margin-right: 8px;
        }

        .welcome-section .date-time {
            color: #666;
            font-size: 14px;
            text-align: right;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .stat-card .icon {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-card .icon.green { background: #e8f5e9; color: #2e7d32; }
        .stat-card .icon.blue { background: #e3f2fd; color: #1565c0; }
        .stat-card .icon.orange { background: #fff3e0; color: #e65100; }
        .stat-card .icon.purple { background: #f3e5f5; color: #6a1b9a; }
        .stat-card .icon.red { background: #ffebee; color: #c62828; }
        .stat-card .icon.gold { background: #fff8e1; color: #f57f17; }
        .stat-card .icon.teal { background: #e0f2f1; color: #00695c; }

        .stat-card .info h3 {
            font-size: 28px;
            color: #1a472a;
        }

        .stat-card .info p {
            color: #888;
            font-size: 14px;
            margin-top: 2px;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .section-title h2 {
            color: #1a472a;
            font-size: 20px;
        }

        .section-title a {
            color: #2e7d32;
            text-decoration: none;
            font-weight: bold;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            padding: 25px 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: 0.3s;
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: #2e7d32;
            box-shadow: 0 8px 25px rgba(46,125,50,0.15);
        }

        .action-card i {
            font-size: 35px;
            margin-bottom: 10px;
            display: block;
        }

        .action-card .action-label {
            font-size: 14px;
            font-weight: 600;
        }

        .action-card .action-desc {
            font-size: 12px;
            color: #888;
            margin-top: 3px;
        }

        .action-card.green i { color: #2e7d32; }
        .action-card.blue i { color: #1565c0; }
        .action-card.orange i { color: #e65100; }
        .action-card.purple i { color: #6a1b9a; }
        .action-card.red i { color: #c62828; }
        .action-card.gold i { color: #f57f17; }

        .footer {
            text-align: center;
            color: #888;
            font-size: 13px;
            padding: 20px 0;
            border-top: 1px solid #e0e0e0;
            margin-top: 20px;
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
            .stat-card {
                padding: 15px;
            }
            .stat-card .info h3 {
                font-size: 22px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

    <!-- Top Header -->
    <div class="top-header">
        <div class="brand">
            <i class="fas fa-leaf"></i>
            <h2><?php echo t('brand'); ?></h2>
        </div>
        <div class="user-info">
            <div class="avatar">
                <?php if(!empty($user_data) && !empty($user_data->getProfilePhoto()) && file_exists('uploads/profiles/'.$user_data->getProfilePhoto())): ?>
                    <img src="uploads/profiles/<?php echo $user_data->getProfilePhoto(); ?>" alt="Profile">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="user-details">
                <div class="name"><?php echo htmlspecialchars($user_name); ?></div>
                <div class="role"><?php echo ucfirst($user_type); ?></div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?>
            </a>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">

        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="greeting">
                <h1><?php echo t('welcome'); ?>, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p><?php echo t('dashboard_sub'); ?></p>
                <?php if($user_type == 'farmer' && !empty($dashboard_data['farm_name'])): ?>
                    <div class="business-name">
                        <i class="fas fa-tractor"></i> <?php echo htmlspecialchars($dashboard_data['farm_name']); ?>
                    </div>
                <?php elseif($user_type == 'buyer' && !empty($dashboard_data['business_name'])): ?>
                    <div class="business-name">
                        <i class="fas fa-store"></i> <?php echo htmlspecialchars($dashboard_data['business_name']); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="date-time">
                <i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
                <br>
                <i class="far fa-clock"></i> <?php echo date('h:i A'); ?>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <?php if($user_type == 'farmer'): ?>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-seedling"></i></div>
                    <div class="info">
                        <h3><?php echo $dashboard_data['crops'] ?? 0; ?></h3>
                        <p><?php echo t('my_crops'); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon blue"><i class="fas fa-shopping-cart"></i></div>
                    <div class="info">
                        <h3><?php echo $dashboard_data['orders'] ?? 0; ?></h3>
                        <p><?php echo t('orders_received'); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-clock"></i></div>
                    <div class="info">
                        <h3><?php echo $dashboard_data['pending'] ?? 0; ?></h3>
                        <p><?php echo t('pending_orders'); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="info">
                        <h3><?php echo $dashboard_data['delivered'] ?? 0; ?></h3>
                        <p><?php echo t('delivered'); ?></p>
                    </div>
                </div>
            <?php elseif($user_type == 'buyer'): ?>
                <div class="stat-card">
                    <div class="icon blue"><i class="fas fa-shopping-cart"></i></div>
                    <div class="info">
                        <h3><?php echo $dashboard_data['orders'] ?? 0; ?></h3>
                        <p><?php echo t('my_orders'); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-clock"></i></div>
                    <div class="info">
                        <h3><?php echo $dashboard_data['pending'] ?? 0; ?></h3>
                        <p><?php echo t('pending_orders'); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="info">
                        <h3><?php echo $dashboard_data['delivered'] ?? 0; ?></h3>
                        <p><?php echo t('delivered'); ?></p>
                    </div>
                </div>
            <?php elseif($user_type == 'logistics'): ?>
                <div class="stat-card">
                    <div class="icon purple"><i class="fas fa-truck"></i></div>
                    <div class="info">
                        <h3><?php echo $dashboard_data['total'] ?? 0; ?></h3>
                        <p><?php echo t('total_deliveries'); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-clock"></i></div>
                    <div class="info">
                        <h3><?php echo $dashboard_data['in_transit'] ?? 0; ?></h3>
                        <p><?php echo t('in_transit'); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="info">
                        <h3><?php echo $dashboard_data['delivered'] ?? 0; ?></h3>
                        <p><?php echo t('delivered'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="section-title">
            <h2><i class="fas fa-bolt"></i> <?php echo t('quick_actions'); ?></h2>
        </div>

        <div class="actions-grid">
            <?php if($user_type == 'farmer'): ?>
                <a href="add_crop.php" class="action-card green">
                    <i class="fas fa-plus-circle"></i>
                    <div class="action-label"><?php echo t('add_crop'); ?></div>
                    <div class="action-desc"><?php echo t('list_produce'); ?></div>
                </a>
                <a href="my_crops.php" class="action-card blue">
                    <i class="fas fa-seedling"></i>
                    <div class="action-label"><?php echo t('my_crops'); ?></div>
                    <div class="action-desc"><?php echo t('view_listings'); ?></div>
                </a>
                <a href="my_orders.php" class="action-card orange">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="action-label"><?php echo t('my_orders'); ?></div>
                    <div class="action-desc"><?php echo t('manage_orders'); ?></div>
                </a>
            <?php elseif($user_type == 'buyer'): ?>
                <a href="search_crops.php" class="action-card green">
                    <i class="fas fa-search"></i>
                    <div class="action-label"><?php echo t('search_crops'); ?></div>
                    <div class="action-desc"><?php echo t('find_produce'); ?></div>
                </a>
                <a href="my_orders.php" class="action-card blue">
                    <i class="fas fa-list"></i>
                    <div class="action-label"><?php echo t('my_orders'); ?></div>
                    <div class="action-desc"><?php echo t('track_orders'); ?></div>
                </a>
            <?php elseif($user_type == 'logistics'): ?>
                <a href="available_deliveries.php" class="action-card purple">
                    <i class="fas fa-truck"></i>
                    <div class="action-label"><?php echo t('available_deliveries'); ?></div>
                    <div class="action-desc"><?php echo t('view_delivery_jobs'); ?></div>
                </a>
                <a href="my_deliveries.php" class="action-card blue">
                    <i class="fas fa-list"></i>
                    <div class="action-label"><?php echo t('my_deliveries'); ?></div>
                    <div class="action-desc"><?php echo t('track_jobs'); ?></div>
                </a>
            <?php endif; ?>
            
            <!-- Profile Settings for ALL users -->
            <a href="profile_settings.php" class="action-card gold">
                <i class="fas fa-user-cog"></i>
                <div class="action-label"><?php echo t('profile_settings'); ?></div>
                <div class="action-desc"><?php echo t('update_photo'); ?></div>
            </a>
            <a href="payment_history.php" class="action-card purple">
                <i class="fas fa-history"></i>
                <div class="action-label"><?php echo t('payment_history'); ?></div>
                <div class="action-desc"><?php echo t('view_transactions'); ?></div>
            </a>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>