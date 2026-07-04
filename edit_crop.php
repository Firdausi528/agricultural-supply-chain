<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/classes/Farmer.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'farmer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$crop_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Load farmer using OOP
$farmer = new Farmer($db);
$farmer->load($user_id);

// Get crop details
$sql = "SELECT * FROM crops WHERE id = ? AND farmer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$crop_id, $user_id]);
$crop = $stmt->fetch();

if (!$crop) {
    header('Location: my_crops.php');
    exit();
}

// Decrypt crop data
$crop['crop_name'] = Encryption::decrypt($crop['crop_name']);
$crop['location'] = Encryption::decrypt($crop['location']);
if ($crop['description']) {
    $crop['description'] = Encryption::decrypt($crop['description']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $crop_name = $_POST['crop_name'];
    $quantity = $_POST['quantity'];
    $price_per_kg = $_POST['price_per_kg'];
    $location = $_POST['location'];
    $description = $_POST['description'] ?? '';

    // Encrypt crop data
    $encrypted_crop_name = Encryption::encrypt($crop_name);
    $encrypted_location = Encryption::encrypt($location);
    $encrypted_description = $description ? Encryption::encrypt($description) : null;

    try {
        $sql = "UPDATE crops SET crop_name = ?, quantity = ?, price_per_kg = ?, location = ?, description = ? WHERE id = ? AND farmer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$encrypted_crop_name, $quantity, $price_per_kg, $encrypted_location, $encrypted_description, $crop_id, $user_id]);
        $message = "✅ Crop updated successfully!";
        
        // Refresh crop data
        $stmt = $pdo->prepare("SELECT * FROM crops WHERE id = ? AND farmer_id = ?");
        $stmt->execute([$crop_id, $user_id]);
        $crop = $stmt->fetch();
        $crop['crop_name'] = Encryption::decrypt($crop['crop_name']);
        $crop['location'] = Encryption::decrypt($crop['location']);
        if ($crop['description']) {
            $crop['description'] = Encryption::decrypt($crop['description']);
        }
    } catch(PDOException $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Crop - Agricultural Supply</title>
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
            max-width: 500px;
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
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            font-size: 30px;
            color: #1565c0;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #1565c0, #1a73e8);
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
            box-shadow: 0 10px 30px rgba(21,101,192,0.3);
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
            <h2>Soko Fresh</h2>
        </div>
        <a href="my_crops.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to My Crops
        </a>
    </div>

    <div class="container">

        <div class="form-card">
            <div class="form-header">
                <div class="icon">
                    <i class="fas fa-edit"></i>
                </div>
                <h1>Edit Crop</h1>
                <p>Update your crop details</p>
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
                    <label><i class="fas fa-seedling"></i> Crop Name</label>
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
                        <label><i class="fas fa-weight-hanging"></i> Quantity (kg)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-weight-hanging"></i>
                            <input type="number" step="0.01" name="quantity" value="<?php echo $crop['quantity']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Price per kg (TSh)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-tag"></i>
                            <input type="number" step="0.01" name="price_per_kg" value="<?php echo $crop['price_per_kg']; ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Location</label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <select name="location" required>
                            <option value="">Select Your Region</option>
                            <option value="Arusha" <?php echo $crop['location'] == 'Arusha' ? 'selected' : ''; ?>>Arusha</option>
                            <option value="Dar es Salaam" <?php echo $crop['location'] == 'Dar es Salaam' ? 'selected' : ''; ?>>Dar es Salaam</option>
                            <option value="Dodoma" <?php echo $crop['location'] == 'Dodoma' ? 'selected' : ''; ?>>Dodoma</option>
                            <option value="Geita" <?php echo $crop['location'] == 'Geita' ? 'selected' : ''; ?>>Geita</option>
                            <option value="Iringa" <?php echo $crop['location'] == 'Iringa' ? 'selected' : ''; ?>>Iringa</option>
                            <option value="Kagera" <?php echo $crop['location'] == 'Kagera' ? 'selected' : ''; ?>>Kagera</option>
                            <option value="Katavi" <?php echo $crop['location'] == 'Katavi' ? 'selected' : ''; ?>>Katavi</option>
                            <option value="Kigoma" <?php echo $crop['location'] == 'Kigoma' ? 'selected' : ''; ?>>Kigoma</option>
                            <option value="Kilimanjaro" <?php echo $crop['location'] == 'Kilimanjaro' ? 'selected' : ''; ?>>Kilimanjaro</option>
                            <option value="Lindi" <?php echo $crop['location'] == 'Lindi' ? 'selected' : ''; ?>>Lindi</option>
                            <option value="Manyara" <?php echo $crop['location'] == 'Manyara' ? 'selected' : ''; ?>>Manyara</option>
                            <option value="Mara" <?php echo $crop['location'] == 'Mara' ? 'selected' : ''; ?>>Mara</option>
                            <option value="Mbeya" <?php echo $crop['location'] == 'Mbeya' ? 'selected' : ''; ?>>Mbeya</option>
                            <option value="Morogoro" <?php echo $crop['location'] == 'Morogoro' ? 'selected' : ''; ?>>Morogoro</option>
                            <option value="Mtwara" <?php echo $crop['location'] == 'Mtwara' ? 'selected' : ''; ?>>Mtwara</option>
                            <option value="Mwanza" <?php echo $crop['location'] == 'Mwanza' ? 'selected' : ''; ?>>Mwanza</option>
                            <option value="Njombe" <?php echo $crop['location'] == 'Njombe' ? 'selected' : ''; ?>>Njombe</option>
                            <option value="Pemba North" <?php echo $crop['location'] == 'Pemba North' ? 'selected' : ''; ?>>Pemba North</option>
                            <option value="Pemba South" <?php echo $crop['location'] == 'Pemba South' ? 'selected' : ''; ?>>Pemba South</option>
                            <option value="Pwani" <?php echo $crop['location'] == 'Pwani' ? 'selected' : ''; ?>>Pwani</option>
                            <option value="Rukwa" <?php echo $crop['location'] == 'Rukwa' ? 'selected' : ''; ?>>Rukwa</option>
                            <option value="Ruvuma" <?php echo $crop['location'] == 'Ruvuma' ? 'selected' : ''; ?>>Ruvuma</option>
                            <option value="Shinyanga" <?php echo $crop['location'] == 'Shinyanga' ? 'selected' : ''; ?>>Shinyanga</option>
                            <option value="Simiyu" <?php echo $crop['location'] == 'Simiyu' ? 'selected' : ''; ?>>Simiyu</option>
                            <option value="Singida" <?php echo $crop['location'] == 'Singida' ? 'selected' : ''; ?>>Singida</option>
                            <option value="Songwe" <?php echo $crop['location'] == 'Songwe' ? 'selected' : ''; ?>>Songwe</option>
                            <option value="Tabora" <?php echo $crop['location'] == 'Tabora' ? 'selected' : ''; ?>>Tabora</option>
                            <option value="Tanga" <?php echo $crop['location'] == 'Tanga' ? 'selected' : ''; ?>>Tanga</option>
                            <option value="Zanzibar North" <?php echo $crop['location'] == 'Zanzibar North' ? 'selected' : ''; ?>>Zanzibar North</option>
                            <option value="Zanzibar South" <?php echo $crop['location'] == 'Zanzibar South' ? 'selected' : ''; ?>>Zanzibar South</option>
                            <option value="Zanzibar West" <?php echo $crop['location'] == 'Zanzibar West' ? 'selected' : ''; ?>>Zanzibar West</option>
                        </select>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description (Optional)</label>
                    <div class="input-wrapper">
                        <i class="fas fa-align-left"></i>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($crop['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Update Crop
                </button>
            </form>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> Soko Fresh - Agricultural Supply Chain Platform
        </div>
    </div>

</body>
</html>