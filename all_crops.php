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

// Crop translation array
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

// Get search filter
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT c.*, u.full_name as farmer_name, u.phone as farmer_phone, u.location as farmer_location 
        FROM crops c 
        JOIN users u ON c.farmer_id = u.id 
        WHERE 1=1";

$params = array();

if (!empty($search)) {
    $sql .= " AND (c.crop_name LIKE ? OR u.full_name LIKE ? OR c.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$crops = $stmt->fetchAll();

// Decrypt and translate crop data
foreach ($crops as $key => $crop) {
    $crop_name = Encryption::decrypt($crop['crop_name']);
    if ($current_lang == 'sw' && isset($crop_translations[$crop_name])) {
        $crops[$key]['crop_name_display'] = $crop_translations[$crop_name];
    } else {
        $crops[$key]['crop_name_display'] = $crop_name;
    }
    $crops[$key]['location'] = Encryption::decrypt($crop['location']);
    $crops[$key]['farmer_name'] = Encryption::decrypt($crop['farmer_name']);
    $crops[$key]['farmer_location'] = Encryption::decrypt($crop['farmer_location']);
    if ($crop['description']) {
        $crops[$key]['description'] = Encryption::decrypt($crop['description']);
    }
}

// Get total crops count
$total_crops = $pdo->query("SELECT COUNT(*) FROM crops")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('all_crops'); ?> - Soko Fresh</title>
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

        .search-bar {
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

        .search-bar .search-box {
            display: flex;
            gap: 8px;
            flex: 1;
        }

        .search-bar .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            min-width: 200px;
        }

        .search-bar .search-box input:focus {
            border-color: #4caf50;
            outline: none;
        }

        .search-bar .search-box button {
            padding: 10px 20px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .search-bar .search-box button:hover {
            background: #2e7d32;
        }

        .search-bar .clear-btn {
            padding: 10px 18px;
            background: #ddd;
            color: #333;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
        }

        .search-bar .clear-btn:hover {
            background: #bbb;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: white;
            padding: 12px 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-item .number {
            font-size: 22px;
            font-weight: bold;
            color: #1b263b;
        }

        .stat-item .label {
            font-size: 12px;
            color: #888;
            margin-top: 2px;
        }

        .crops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .crop-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: 0.3s;
            border: 1px solid #e8f0e8;
        }

        .crop-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .crop-card .card-header {
            padding: 15px 20px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .crop-card .card-header .crop-name {
            font-size: 18px;
            font-weight: bold;
            color: #1b263b;
        }

        .crop-card .card-header .crop-icon {
            font-size: 28px;
            color: #2e7d32;
        }

        .crop-card .card-body {
            padding: 15px 20px;
        }

        .crop-card .card-body .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .crop-card .card-body .detail-row .label {
            color: #888;
        }

        .crop-card .card-body .detail-row .value {
            font-weight: 500;
            color: #1b263b;
        }

        .crop-card .card-body .farmer-info {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #f0f0f0;
        }

        .crop-card .card-body .farmer-info .farmer-name {
            font-weight: 600;
            color: #1b263b;
        }

        .crop-card .card-body .farmer-info .farmer-phone {
            color: #888;
            font-size: 13px;
        }

        .crop-card .card-body .farmer-info i {
            color: #4caf50;
            margin-right: 5px;
        }

        .status-badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-badge.available { background: #e8f5e9; color: #2e7d32; }
        .status-badge.low { background: #fff3e0; color: #e65100; }
        .status-badge.out { background: #ffebee; color: #c62828; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }

        .empty-state h3 {
            color: #1b263b;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #888;
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
            .search-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .search-bar .search-box {
                flex-wrap: wrap;
            }
            .search-bar .search-box input {
                min-width: 100%;
            }
            .page-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .crops-grid {
                grid-template-columns: 1fr;
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
            <h1><i class="fas fa-seedling"></i> <?php echo t('all_crops'); ?></h1>
            <span class="total-badge"><?php echo t('total'); ?>: <?php echo count($crops); ?> <?php echo t('crops'); ?></span>
        </div>

        <div class="search-bar">
            <div class="search-box">
                <form method="GET" style="display: flex; gap: 8px; width: 100%; flex-wrap: wrap;">
                    <input type="text" name="search" placeholder="<?php echo t('search_crops_placeholder'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i> <?php echo t('search'); ?></button>
                    <?php if(!empty($search)): ?>
                        <a href="all_crops.php" class="clear-btn"><i class="fas fa-times"></i> <?php echo t('clear'); ?></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-item">
                <div class="number"><?php echo $total_crops; ?></div>
                <div class="label"><?php echo t('total_crops'); ?></div>
            </div>
            <div class="stat-item">
                <div class="number"><?php echo count($crops); ?></div>
                <div class="label"><?php echo t('showing'); ?></div>
            </div>
        </div>

        <?php if(count($crops) > 0): ?>
            <div class="crops-grid">
                <?php foreach($crops as $crop): 
                    $status = 'available';
                    $status_label = t('available');
                    if ($crop['quantity'] <= 0) {
                        $status = 'out';
                        $status_label = t('out_of_stock');
                    } elseif ($crop['quantity'] < 10) {
                        $status = 'low';
                        $status_label = t('low_stock');
                    }
                ?>
                <div class="crop-card">
                    <div class="card-header">
                        <span class="crop-name"><?php echo htmlspecialchars($crop['crop_name_display']); ?></span>
                        <span class="crop-icon"><i class="fas fa-seedling"></i></span>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <span class="label"><?php echo t('quantity'); ?></span>
                            <span class="value"><?php echo $crop['quantity']; ?> kg</span>
                        </div>
                        <div class="detail-row">
                            <span class="label"><?php echo t('price'); ?></span>
                            <span class="value">TSh <?php echo number_format($crop['price_per_kg']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label"><?php echo t('location'); ?></span>
                            <span class="value"><?php echo htmlspecialchars($crop['location']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label"><?php echo t('status'); ?></span>
                            <span class="value"><span class="status-badge <?php echo $status; ?>"><?php echo $status_label; ?></span></span>
                        </div>
                        <div class="detail-row">
                            <span class="label"><?php echo t('listed'); ?></span>
                            <span class="value"><?php echo date('d/m/Y', strtotime($crop['created_at'])); ?></span>
                        </div>

                        <div class="farmer-info">
                            <div class="farmer-name">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($crop['farmer_name']); ?>
                            </div>
                            <div class="farmer-phone">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($crop['farmer_phone']); ?>
                                <span style="margin-left: 10px;">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($crop['farmer_location']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-seedling"></i>
                <h3><?php echo t('no_crops_found'); ?></h3>
                <p><?php echo t('no_crops_found_msg'); ?></p>
            </div>
        <?php endif; ?>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>