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
$message = '';
$error = '';

// Load farmer using OOP
$farmer = new Farmer($db);
$farmer->load($user_id);
$farm_name = $farmer->getFarmName();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $crop_name = trim($_POST['crop_name']);
    $quantity = $_POST['quantity'];
    $price_per_kg = $_POST['price_per_kg'];
    $location = trim($_POST['location']);
    $description = trim($_POST['description'] ?? '');

    $encrypted_crop_name = Encryption::encrypt($crop_name);
    $encrypted_location = Encryption::encrypt($location);
    $encrypted_description = $description ? Encryption::encrypt($description) : null;

    try {
        $sql = "INSERT INTO crops (farmer_id, crop_name, quantity, price_per_kg, location, description) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $encrypted_crop_name, $quantity, $price_per_kg, $encrypted_location, $encrypted_description]);
        $message = "✅ " . t('crop_added');
    } catch(PDOException $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <title><?php echo t('add_crop_title'); ?> - Soko Fresh</title>
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
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border: 1px solid #e8f0e8;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header .icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            font-size: 30px;
            color: #2e7d32;
        }

        .form-header h1 {
            color: #1a472a;
            font-size: 26px;
        }

        .form-header p {
            color: #888;
            font-size: 14px;
            margin-top: 5px;
        }

        .form-header .farm-name {
            color: #2e7d32;
            font-weight: bold;
            margin-top: 5px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .form-group label i {
            color: #2e7d32;
            margin-right: 6px;
        }

        .form-group .input-wrapper {
            position: relative;
        }

        .form-group .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 16px;
            z-index: 5;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 13px 15px 13px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: 0.3s;
            background: #f9f9f9;
        }

        .form-group textarea {
            padding: 13px 15px 13px 45px;
            min-height: 100px;
            resize: vertical;
        }

        .form-group select {
            appearance: none;
            cursor: pointer;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2e7d32;
            outline: none;
            background: white;
            box-shadow: 0 0 20px rgba(46,125,50,0.08);
        }

        .form-group .input-wrapper .fa-chevron-down {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            pointer-events: none;
        }

        .form-group .hint {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1a472a, #2e7d32);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(46,125,50,0.3);
        }

        .btn-submit i {
            margin-right: 10px;
        }

        .footer {
            text-align: center;
            color: #888;
            font-size: 13px;
            padding: 30px 0 20px 0;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .top-header {
                flex-wrap: wrap;
                gap: 10px;
                padding: 12px 15px;
            }
            .form-card {
                padding: 25px;
            }
            .form-row {
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

        <div class="form-card">
            <div class="form-header">
                <div class="icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h1><?php echo t('add_crop_title'); ?></h1>
                <p><?php echo t('add_crop_sub'); ?></p>
                <?php if(!empty($farm_name)): ?>
                    <div class="farm-name">
                        <i class="fas fa-tractor"></i> <?php echo htmlspecialchars($farm_name); ?>
                    </div>
                <?php endif; ?>
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

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-seedling"></i> <?php echo t('crop_name'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-seedling"></i>
                        <select name="crop_name" required>
                            <option value=""><?php echo t('select_crop'); ?></option>
                            <option value="Maize">🌽 <?php echo t('crop_maize'); ?></option>
                            <option value="Rice">🍚 <?php echo t('crop_rice'); ?></option>
                            <option value="Beans">🫘 <?php echo t('crop_beans'); ?></option>
                            <option value="Tomatoes">🍅 <?php echo t('crop_tomatoes'); ?></option>
                            <option value="Onions">🧅 <?php echo t('crop_onions'); ?></option>
                            <option value="Potatoes">🥔 <?php echo t('crop_potatoes'); ?></option>
                            <option value="Cassava">🌿 <?php echo t('crop_cassava'); ?></option>
                            <option value="Sweet Potatoes">🍠 <?php echo t('crop_sweet_potatoes'); ?></option>
                            <option value="Cabbage">🥬 <?php echo t('crop_cabbage'); ?></option>
                            <option value="Spinach">🥬 <?php echo t('crop_spinach'); ?></option>
                            <option value="Carrots">🥕 <?php echo t('crop_carrots'); ?></option>
                            <option value="Peppers">🌶️ <?php echo t('crop_peppers'); ?></option>
                            <option value="Avocado">🥑 <?php echo t('crop_avocado'); ?></option>
                            <option value="Mangoes">🥭 <?php echo t('crop_mangoes'); ?></option>
                            <option value="Oranges">🍊 <?php echo t('crop_oranges'); ?></option>
                            <option value="Bananas">🍌 <?php echo t('crop_bananas'); ?></option>
                            <option value="Pineapples">🍍 <?php echo t('crop_pineapples'); ?></option>
                            <option value="Coffee">☕ <?php echo t('crop_coffee'); ?></option>
                            <option value="Tea">🍵 <?php echo t('crop_tea'); ?></option>
                            <option value="Sunflower">🌻 <?php echo t('crop_sunflower'); ?></option>
                            <option value="Cotton">🌾 <?php echo t('crop_cotton'); ?></option>
                            <option value="Sugarcane">🌿 <?php echo t('crop_sugarcane'); ?></option>
                            <option value="Groundnuts">🥜 <?php echo t('crop_groundnuts'); ?></option>
                            <option value="Soybeans">🫘 <?php echo t('crop_soybeans'); ?></option>
                        </select>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-weight-hanging"></i> <?php echo t('quantity'); ?></label>
                        <div class="input-wrapper">
                            <i class="fas fa-weight-hanging"></i>
                            <input type="number" step="0.01" name="quantity" placeholder="<?php echo t('quantity_placeholder'); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> <?php echo t('price'); ?></label>
                        <div class="input-wrapper">
                            <i class="fas fa-tag"></i>
                            <input type="number" step="0.01" name="price_per_kg" placeholder="<?php echo t('price_placeholder'); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> <?php echo t('location'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <select name="location" required>
                            <option value=""><?php echo t('select_region'); ?></option>
                            <option value="Arusha">Arusha</option>
                            <option value="Dar es Salaam">Dar es Salaam</option>
                            <option value="Dodoma">Dodoma</option>
                            <option value="Geita">Geita</option>
                            <option value="Iringa">Iringa</option>
                            <option value="Kagera">Kagera</option>
                            <option value="Katavi">Katavi</option>
                            <option value="Kigoma">Kigoma</option>
                            <option value="Kilimanjaro">Kilimanjaro</option>
                            <option value="Lindi">Lindi</option>
                            <option value="Manyara">Manyara</option>
                            <option value="Mara">Mara</option>
                            <option value="Mbeya">Mbeya</option>
                            <option value="Morogoro">Morogoro</option>
                            <option value="Mtwara">Mtwara</option>
                            <option value="Mwanza">Mwanza</option>
                            <option value="Njombe">Njombe</option>
                            <option value="Pemba North">Pemba North</option>
                            <option value="Pemba South">Pemba South</option>
                            <option value="Pwani">Pwani</option>
                            <option value="Rukwa">Rukwa</option>
                            <option value="Ruvuma">Ruvuma</option>
                            <option value="Shinyanga">Shinyanga</option>
                            <option value="Simiyu">Simiyu</option>
                            <option value="Singida">Singida</option>
                            <option value="Songwe">Songwe</option>
                            <option value="Tabora">Tabora</option>
                            <option value="Tanga">Tanga</option>
                            <option value="Zanzibar North">Zanzibar North</option>
                            <option value="Zanzibar South">Zanzibar South</option>
                            <option value="Zanzibar West">Zanzibar West</option>
                        </select>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> <?php echo t('description'); ?></label>
                    <div class="input-wrapper">
                        <i class="fas fa-align-left"></i>
                        <textarea name="description" placeholder="<?php echo t('description_placeholder'); ?>"></textarea>
                    </div>
                    <div class="hint"><?php echo t('description_hint'); ?></div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus-circle"></i> <?php echo t('add_crop_btn'); ?>
                </button>
            </form>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>