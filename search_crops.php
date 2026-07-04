<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'buyer') {
    header('Location: login.php');
    exit();
}

$search = '';
$location_filter = '';
$min_price = '';
$max_price = '';
$crops = array();

// Build the query
$sql = "SELECT c.*, u.full_name, u.phone, u.location as farmer_location 
        FROM crops c 
        JOIN users u ON c.farmer_id = u.id 
        WHERE c.quantity > 0";

$params = array();

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $sql .= " AND (c.crop_name LIKE ? OR c.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (isset($_GET['location']) && !empty($_GET['location'])) {
    $location_filter = $_GET['location'];
    $sql .= " AND c.location = ?";
    $params[] = $location_filter;
}

if (isset($_GET['crop_type']) && !empty($_GET['crop_type'])) {
    $crop_type = $_GET['crop_type'];
    $sql .= " AND c.crop_name = ?";
    $params[] = $crop_type;
}

if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
    $min_price = $_GET['min_price'];
    $sql .= " AND c.price_per_kg >= ?";
    $params[] = $min_price;
}

if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $max_price = $_GET['max_price'];
    $sql .= " AND c.price_per_kg <= ?";
    $params[] = $max_price;
}

$sql .= " ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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
    
    $decrypted_name = Encryption::decrypt($crop['crop_name']);
    $decrypted['crop_name'] = ($decrypted_name !== false && $decrypted_name !== null) ? $decrypted_name : $crop['crop_name'];
    
    $decrypted_location = Encryption::decrypt($crop['location']);
    $decrypted['location'] = ($decrypted_location !== false && $decrypted_location !== null) ? $decrypted_location : $crop['location'];
    
    if ($crop['description']) {
        $decrypted_desc = Encryption::decrypt($crop['description']);
        $decrypted['description'] = ($decrypted_desc !== false && $decrypted_desc !== null) ? $decrypted_desc : $crop['description'];
    } else {
        $decrypted['description'] = '';
    }
    
    $decrypted_name = Encryption::decrypt($crop['full_name']);
    $decrypted['full_name'] = ($decrypted_name !== false && $decrypted_name !== null) ? $decrypted_name : $crop['full_name'];
    
    $decrypted['phone'] = $crop['phone'];
    
    $decrypted_location = Encryption::decrypt($crop['farmer_location']);
    $decrypted['farmer_location'] = ($decrypted_location !== false && $decrypted_location !== null) ? $decrypted_location : $crop['farmer_location'];
    
    $decrypted_crops[] = $decrypted;
}
$crops = $decrypted_crops;

// Get all locations for filter
$locations = $pdo->query("SELECT DISTINCT location FROM crops ORDER BY location")->fetchAll();
$decrypted_locations = array();
foreach ($locations as $loc) {
    $decrypted_loc = Encryption::decrypt($loc['location']);
    $decrypted_locations[] = array('location' => ($decrypted_loc !== false && $decrypted_loc !== null) ? $decrypted_loc : $loc['location']);
}
$locations = $decrypted_locations;

$current_lang = getCurrentLang();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('search_crops'); ?> - Soko Fresh</title>
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

        .hero-search {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            padding: 40px 30px;
            text-align: center;
            margin-bottom: 30px;
        }

        .hero-search h1 {
            font-size: 32px;
            color: #1a472a;
            margin-bottom: 10px;
        }

        .hero-search h1 i {
            margin-right: 10px;
        }

        .hero-search p {
            color: #555;
            font-size: 16px;
            margin-bottom: 25px;
        }

        .search-form {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .search-form .search-label {
            display: flex;
            align-items: center;
            padding: 0 10px;
            font-size: 15px;
            font-weight: bold;
            color: #1a472a;
            background: white;
            border-radius: 10px;
            border: 2px solid #ddd;
            padding: 10px 18px;
        }

        .search-form .search-label i {
            margin-right: 8px;
            color: #2e7d32;
        }

        .search-form .search-select {
            flex: 1;
            min-width: 150px;
            padding: 13px 18px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            background: white;
            cursor: pointer;
            transition: 0.3s;
            appearance: none;
        }

        .search-form .search-select:focus {
            border-color: #2e7d32;
            outline: none;
        }

        .price-filters {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 2px 10px;
            border: 2px solid #ddd;
            border-radius: 10px;
        }

        .price-filters .price-input {
            width: 100px;
            padding: 11px 8px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            background: transparent;
        }

        .price-filters .price-input:focus {
            outline: none;
        }

        .search-form .search-btn {
            padding: 13px 30px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .search-form .search-btn:hover {
            background: #1a472a;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(46,125,50,0.3);
        }

        .search-form .clear-btn {
            padding: 13px 25px;
            background: #888;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .search-form .clear-btn:hover {
            background: #666;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 30px 20px;
        }

        .results-count {
            max-width: 1200px;
            margin: 0 auto 20px auto;
            padding: 0 20px;
            color: #555;
            font-size: 15px;
        }

        .results-count strong {
            color: #1a472a;
        }

        .crops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
            height: 140px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 55px;
            color: #2e7d32;
            position: relative;
        }

        .crop-card .card-image .farmer-badge {
            position: absolute;
            bottom: 12px;
            left: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .crop-card .card-body {
            padding: 20px;
        }

        .crop-card .card-body .crop-name {
            font-size: 20px;
            font-weight: bold;
            color: #1a472a;
        }

        .crop-card .card-body .crop-location {
            color: #888;
            font-size: 14px;
            margin: 4px 0 10px 0;
        }

        .crop-card .card-body .crop-location i {
            margin-right: 5px;
        }

        .crop-card .card-body .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 10px 0;
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

        .crop-card .card-body .farmer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
            font-size: 14px;
            color: #555;
        }

        .crop-card .card-body .farmer-info i {
            margin-right: 5px;
            color: #2e7d32;
        }

        .crop-card .card-body .btn-order {
            display: block;
            text-align: center;
            padding: 12px;
            background: linear-gradient(135deg, #1a472a, #2e7d32);
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
            margin-top: 10px;
        }

        .crop-card .card-body .btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(46,125,50,0.3);
        }

        .crop-card .card-body .btn-order i {
            margin-right: 8px;
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
            .hero-search {
                padding: 30px 15px;
            }
            .hero-search h1 {
                font-size: 24px;
            }
            .search-form .search-select {
                min-width: 100%;
            }
            .price-filters {
                flex-wrap: wrap;
                justify-content: center;
            }
            .price-filters .price-input {
                width: 80px;
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

    <div class="hero-search">
        <h1><i class="fas fa-search"></i> <?php echo t('search_crops'); ?></h1>
        <p><?php echo t('search_crops_sub'); ?></p>
        
        <form method="GET" class="search-form">
            <div class="search-label">
                <i class="fas fa-filter"></i> <?php echo t('filter_crops'); ?>:
            </div>
            
          <select name="crop_type" class="search-select">
    <option value=""><?php echo t('all_crops'); ?></option>
    <option value="Maize" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Maize') ? 'selected' : ''; ?>>🌽 <?php echo t('crop_maize'); ?></option>
    <option value="Rice" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Rice') ? 'selected' : ''; ?>>🍚 <?php echo t('crop_rice'); ?></option>
    <option value="Beans" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Beans') ? 'selected' : ''; ?>>🫘 <?php echo t('crop_beans'); ?></option>
    <option value="Tomatoes" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Tomatoes') ? 'selected' : ''; ?>>🍅 <?php echo t('crop_tomatoes'); ?></option>
    <option value="Onions" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Onions') ? 'selected' : ''; ?>>🧅 <?php echo t('crop_onions'); ?></option>
    <option value="Potatoes" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Potatoes') ? 'selected' : ''; ?>>🥔 <?php echo t('crop_potatoes'); ?></option>
    <option value="Cassava" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Cassava') ? 'selected' : ''; ?>>🌿 <?php echo t('crop_cassava'); ?></option>
    <option value="Sweet Potatoes" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Sweet Potatoes') ? 'selected' : ''; ?>>🍠 <?php echo t('crop_sweet_potatoes'); ?></option>
    <option value="Cabbage" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Cabbage') ? 'selected' : ''; ?>>🥬 <?php echo t('crop_cabbage'); ?></option>
    <option value="Spinach" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Spinach') ? 'selected' : ''; ?>>🥬 <?php echo t('crop_spinach'); ?></option>
    <option value="Carrots" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Carrots') ? 'selected' : ''; ?>>🥕 <?php echo t('crop_carrots'); ?></option>
    <option value="Peppers" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Peppers') ? 'selected' : ''; ?>>🌶️ <?php echo t('crop_peppers'); ?></option>
    <option value="Avocado" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Avocado') ? 'selected' : ''; ?>>🥑 <?php echo t('crop_avocado'); ?></option>
    <option value="Mangoes" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Mangoes') ? 'selected' : ''; ?>>🥭 <?php echo t('crop_mangoes'); ?></option>
    <option value="Oranges" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Oranges') ? 'selected' : ''; ?>>🍊 <?php echo t('crop_oranges'); ?></option>
    <option value="Bananas" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Bananas') ? 'selected' : ''; ?>>🍌 <?php echo t('crop_bananas'); ?></option>
    <option value="Pineapples" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Pineapples') ? 'selected' : ''; ?>>🍍 <?php echo t('crop_pineapples'); ?></option>
    <option value="Coffee" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Coffee') ? 'selected' : ''; ?>>☕ <?php echo t('crop_coffee'); ?></option>
    <option value="Tea" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Tea') ? 'selected' : ''; ?>>🍵 <?php echo t('crop_tea'); ?></option>
    <option value="Sunflower" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Sunflower') ? 'selected' : ''; ?>>🌻 <?php echo t('crop_sunflower'); ?></option>
    <option value="Cotton" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Cotton') ? 'selected' : ''; ?>>🌾 <?php echo t('crop_cotton'); ?></option>
    <option value="Sugarcane" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Sugarcane') ? 'selected' : ''; ?>>🌿 <?php echo t('crop_sugarcane'); ?></option>
    <option value="Groundnuts" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Groundnuts') ? 'selected' : ''; ?>>🥜 <?php echo t('crop_groundnuts'); ?></option>
    <option value="Soybeans" <?php echo (isset($_GET['crop_type']) && $_GET['crop_type'] == 'Soybeans') ? 'selected' : ''; ?>>🫘 <?php echo t('crop_soybeans'); ?></option>
</select>

            <div class="price-filters">
                <input type="number" name="min_price" placeholder="<?php echo t('min_price'); ?>" value="<?php echo htmlspecialchars($min_price); ?>" step="100" class="price-input">
                <span style="color:#888;">-</span>
                <input type="number" name="max_price" placeholder="<?php echo t('max_price'); ?>" value="<?php echo htmlspecialchars($max_price); ?>" step="100" class="price-input">
            </div>

            <button type="submit" class="search-btn"><i class="fas fa-search"></i> <?php echo t('search'); ?></button>
            <?php if($location_filter || $min_price || $max_price || isset($_GET['crop_type']) || isset($_GET['search'])): ?>
                <a href="search_crops.php" class="clear-btn"><i class="fas fa-times"></i> <?php echo t('clear'); ?></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="container">

        <div class="results-count">
            <i class="fas fa-list"></i> <?php echo t('found_crops'); ?> <strong><?php echo count($crops); ?></strong>
        </div>

        <?php if(count($crops) > 0): ?>
            <div class="crops-grid">
                <?php foreach($crops as $crop): ?>
                <div class="crop-card">
                    <div class="card-image">
                        <i class="fas fa-seedling"></i>
                        <span class="farmer-badge">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($crop['full_name']); ?>
                        </span>
                    </div>
                    <div class="card-body">
    <div class="crop-name">
    <?php 
    $display_name = $crop['crop_name'];
    if ($current_lang == 'sw') {
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
        if (isset($crop_translations[$crop['crop_name']])) {
            $display_name = $crop_translations[$crop['crop_name']];
        }
    }
    echo htmlspecialchars($display_name);
    ?>
</div>
                 <div class="crop-location">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($crop['location']); ?>
                        </div>

                        <div class="details">
                            <div class="detail-item">
                                <div class="label"><?php echo t('available_qty'); ?></div>
                                <div class="value"><?php echo $crop['quantity']; ?> kg</div>
                            </div>
                            <div class="detail-item">
                                <div class="label"><?php echo t('price_per_kg'); ?></div>
                                <div class="value">TSh <?php echo number_format($crop['price_per_kg']); ?></div>
                            </div>
                        </div>

                        <div class="farmer-info">
                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($crop['phone']); ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($crop['farmer_location']); ?></span>
                        </div>

                        <a href="place_order.php?crop_id=<?php echo $crop['id']; ?>" class="btn-order">
                            <i class="fas fa-shopping-cart"></i> <?php echo t('place_order'); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h2><?php echo t('no_crops_found'); ?></h2>
                <p><?php echo t('no_crops_found_msg'); ?></p>
            </div>
        <?php endif; ?>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>