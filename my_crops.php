<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';
require_once __DIR__ . '/classes/Farmer.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'farmer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Load farmer using OOP
$farmer = new Farmer($db);
$farmer->load($user_id);

// Get all crops for this farmer
$sql = "SELECT * FROM crops WHERE farmer_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$crops = $stmt->fetchAll();

// Decrypt crop data
$decrypted_crops = array();
foreach ($crops as $crop) {
    $decrypted = array();
    $decrypted['id'] = $crop['id'];
    $decrypted['farmer_id'] = $crop['farmer_id'];
    $decrypted['quantity'] = $crop['quantity'];
    $decrypted['price_per_kg'] = $crop['price_per_kg'];
    $decrypted['created_at'] = $crop['created_at'];
    
    // Decrypt crop name
    $decrypted_name = Encryption::decrypt($crop['crop_name']);
    $decrypted['crop_name'] = ($decrypted_name !== false && $decrypted_name !== null) ? $decrypted_name : $crop['crop_name'];
    
    // Decrypt location
    $decrypted_location = Encryption::decrypt($crop['location']);
    $decrypted['location'] = ($decrypted_location !== false && $decrypted_location !== null) ? $decrypted_location : $crop['location'];
    
    // Decrypt description
    if ($crop['description']) {
        $decrypted_desc = Encryption::decrypt($crop['description']);
        $decrypted['description'] = ($decrypted_desc !== false && $decrypted_desc !== null) ? $decrypted_desc : $crop['description'];
    } else {
        $decrypted['description'] = '';
    }
    
    $decrypted_crops[] = $decrypted;
}
$crops = $decrypted_crops;

$current_lang = getCurrentLang();

// Crop name translation array
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
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('my_crops'); ?> - Soko Fresh</title>
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
            max-width: 1200px;
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

        .page-header .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-add {
            background: #2e7d32;
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            background: #1a472a;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(46,125,50,0.3);
        }

        .btn-dashboard {
            background: #666;
            color: white;
            padding: 12px 25px;
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
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stats-bar .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
        }

        .stats-bar .stat-item i {
            font-size: 20px;
            color: #2e7d32;
        }

        .stats-bar .stat-item strong {
            color: #1a472a;
            font-size: 18px;
        }

        .crops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .crop-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            transition: 0.3s;
            border: 1px solid #e8f0e8;
        }

        .crop-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .crop-card .card-image {
            height: 160px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: #2e7d32;
            position: relative;
        }

        .crop-card .card-image .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .status-badge.available { background: #2e7d32; }
        .status-badge.low { background: #e65100; }
        .status-badge.out { background: #c62828; }

        .crop-card .card-body {
            padding: 20px;
        }

        .crop-card .card-body .crop-name {
            font-size: 20px;
            font-weight: bold;
            color: #1a472a;
            margin-bottom: 5px;
        }

        .crop-card .card-body .crop-location {
            color: #888;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .crop-card .card-body .crop-location i {
            margin-right: 5px;
        }

        .crop-card .card-body .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 12px 0;
            padding: 12px 0;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }

        .crop-card .card-body .details .detail-item {
            text-align: center;
        }

        .crop-card .card-body .details .detail-item .label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
        }

        .crop-card .card-body .details .detail-item .value {
            font-size: 16px;
            font-weight: bold;
            color: #1a472a;
        }

        .crop-card .card-body .description {
            color: #666;
            font-size: 14px;
            margin: 8px 0 12px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .crop-card .card-body .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .crop-card .card-body .card-actions a {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-edit {
            background: #e3f2fd;
            color: #1565c0;
        }

        .btn-edit:hover {
            background: #1565c0;
            color: white;
        }

        .btn-delete {
            background: #ffebee;
            color: #c62828;
        }

        .btn-delete:hover {
            background: #c62828;
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
            margin-bottom: 20px;
        }

        .empty-state .btn-add {
            display: inline-block;
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
                gap: 15px;
            }
            .page-header .actions {
                width: 100%;
            }
            .page-header .actions a {
                flex: 1;
                justify-content: center;
            }
            .stats-bar {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            .crops-grid {
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

        <div class="page-header">
            <h1><i class="fas fa-seedling"></i> <?php echo t('my_crops'); ?></h1>
            <div class="actions">
                <a href="add_crop.php" class="btn-add">
                    <i class="fas fa-plus-circle"></i> <?php echo t('add_crop'); ?>
                </a>
                <a href="dashboard.php" class="btn-dashboard">
                    <i class="fas fa-home"></i> <?php echo t('nav_dashboard'); ?>
                </a>
            </div>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <i class="fas fa-seedling"></i>
                <span><?php echo t('total_crops'); ?>: <strong><?php echo count($crops); ?></strong></span>
            </div>
            <div class="stat-item">
                <i class="fas fa-tag"></i>
                <span><?php echo t('listed_for_sale'); ?></span>
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
                    
                    // Translate crop name based on language
                    $display_name = $crop['crop_name'];
                    if ($current_lang == 'sw' && isset($crop_translations[$crop['crop_name']])) {
                        $display_name = $crop_translations[$crop['crop_name']];
                    }
                ?>
                <div class="crop-card">
                    <div class="card-image">
                        <i class="fas fa-seedling"></i>
                        <span class="status-badge <?php echo $status; ?>">
                            <?php echo $status_label; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="crop-name"><?php echo htmlspecialchars($display_name); ?></div>
                        <div class="crop-location">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($crop['location']); ?>
                        </div>

                        <div class="details">
                            <div class="detail-item">
                                <div class="label"><?php echo t('quantity'); ?></div>
                                <div class="value"><?php echo $crop['quantity']; ?> kg</div>
                            </div>
                            <div class="detail-item">
                                <div class="label"><?php echo t('price'); ?></div>
                                <div class="value">TSh <?php echo number_format($crop['price_per_kg']); ?></div>
                            </div>
                        </div>

                        <?php if(!empty($crop['description'])): ?>
                            <div class="description">
                                <?php echo htmlspecialchars($crop['description']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="card-actions">
                            <a href="edit_crop.php?id=<?php echo $crop['id']; ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
                            </a>
                            <a href="delete_crop.php?id=<?php echo $crop['id']; ?>" class="btn-delete" onclick="return confirm('<?php echo t('delete_confirm'); ?>')">
                                <i class="fas fa-trash"></i> <?php echo t('delete'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-seedling"></i>
                <h2><?php echo t('no_crops'); ?></h2>
                <p><?php echo t('no_crops_msg'); ?></p>
                <a href="add_crop.php" class="btn-add">
                    <i class="fas fa-plus-circle"></i> <?php echo t('add_first_crop'); ?>
                </a>
            </div>
        <?php endif; ?>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>