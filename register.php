<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $user_type = $_POST['user_type'];
    $location = $_POST['location'];
    $business_name = $_POST['business_name'] ?? null;
    $farm_name = $_POST['farm_name'] ?? null;
    $profile_photo = null;

    // Validate Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ " . t('invalid_email');
    }
    
    // Validate Phone (10 digits)
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "❌ " . t('invalid_phone');
    }
    
    // Validate Full Name (not empty)
    if (empty($full_name)) {
        $error = "❌ " . t('full_name_required');
    }

    if (empty($error)) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Handle profile photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $filename);
                $upload_path = 'uploads/profiles/' . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $profile_photo = $new_filename;
                }
            }
        }

        try {
            $sql = "INSERT INTO users (full_name, email, password, phone, user_type, location, business_name, farm_name, profile_photo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $full_name, 
                $email, 
                $password, 
                $phone, 
                $user_type, 
                $location, 
                $business_name, 
                $farm_name, 
                $profile_photo
            ]);
            $message = "✅ " . t('register_success');
        } catch(PDOException $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}

$current_lang = getCurrentLang();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('nav_register'); ?> - Soko Fresh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body {
            background: linear-gradient(135deg, #1a472a, #2e7d32, #4caf50);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 500px;
            max-width: 100%;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo a {
            text-decoration: none;
            color: inherit;
        }

        .logo i {
            font-size: 45px;
            color: #2e7d32;
            background: #e8f5e9;
            padding: 15px;
            border-radius: 50%;
        }

        .logo h1 {
            font-size: 26px;
            color: #1a472a;
            margin-top: 8px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .input-group {
            position: relative;
            margin-bottom: 15px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 16px;
            z-index: 5;
        }

        .input-group input, .input-group select {
            width: 100%;
            padding: 13px 15px 13px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: 0.3s;
            background: #f9f9f9;
        }

        .input-group select {
            padding-left: 45px;
            appearance: none;
            cursor: pointer;
        }

        .input-group input:focus, .input-group select:focus {
            border-color: #2e7d32;
            outline: none;
            background: white;
            box-shadow: 0 0 15px rgba(46, 125, 50, 0.1);
        }

        .input-group input::placeholder {
            color: #aaa;
        }

        .input-group .fa-chevron-down {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            pointer-events: none;
        }

        .input-group input[type="file"] {
            padding: 11px 15px 11px 45px;
        }

        .input-group .error-text {
            font-size: 12px;
            color: #c62828;
            margin-top: 3px;
            display: none;
        }

        .input-group .error-text.show {
            display: block;
        }

        .input-group.invalid input,
        .input-group.invalid select {
            border-color: #c62828;
        }

        .btn-register {
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

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(46,125,50,0.3);
        }

        .btn-register i {
            margin-right: 10px;
        }

        .message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .login-link a {
            color: #2e7d32;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .divider {
            text-align: center;
            margin: 18px 0;
            color: #aaa;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            font-size: 13px;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .hint {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        .back-home {
            text-align: center;
            margin-top: 10px;
        }

        .back-home a {
            color: #888;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }

        .back-home a:hover {
            color: #2e7d32;
        }

        @media (max-width: 600px) {
            .row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-seedling"></i>
                <h1>🌾 <?php echo t('brand'); ?></h1>
            </a>
            <p><?php echo t('register_sub'); ?></p>
        </div>

        <?php if($message): ?>
            <div class="message">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" novalidate id="registerForm">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="full_name" id="full_name" placeholder="<?php echo t('full_name'); ?>" required>
                <div class="error-text" id="full_name_error"><?php echo t('full_name_required'); ?></div>
            </div>

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="<?php echo t('email'); ?>" required>
                <div class="error-text" id="email_error"><?php echo t('invalid_email'); ?></div>
            </div>

            <div class="input-group" style="position:relative;">
                <i class="fas fa-lock" style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#888; z-index:5;"></i>
                <input type="password" name="password" id="regPassword" placeholder="<?php echo t('password'); ?>" required style="width:100%; padding:13px 50px 13px 45px; border:2px solid #e0e0e0; border-radius:10px; font-size:15px; background:#f9f9f9; transition:0.3s;">
                <i class="fas fa-eye" id="toggleRegPassword" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); color:#888; cursor:pointer; z-index:5;" onclick="togglePassword('regPassword', 'toggleRegPassword')"></i>
            </div>

            <div class="input-group">
                <i class="fas fa-phone"></i>
                <input type="text" name="phone" id="phone" placeholder="<?php echo t('phone'); ?>" required>
                <div class="error-text" id="phone_error"><?php echo t('invalid_phone'); ?></div>
                <div class="hint"><?php echo t('phone_hint'); ?></div>
            </div>

            <div class="input-group">
                <i class="fas fa-user-tag"></i>
                <select name="user_type" required>
                    <option value=""><?php echo t('select_user_type'); ?></option>
                    <option value="farmer">👨‍🌾 <?php echo t('farmer'); ?></option>
                    <option value="buyer">🛒 <?php echo t('buyer'); ?></option>
                    <option value="logistics">🚚 <?php echo t('logistics'); ?></option>
                </select>
                <i class="fas fa-chevron-down"></i>
            </div>

            <div class="input-group">
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

            <div class="input-group">
                <i class="fas fa-image"></i>
                <input type="file" name="profile_photo" accept="image/*" style="padding: 13px 15px 13px 45px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px; background: #f9f9f9; width: 100%;">
                <div class="hint"><?php echo t('profile_photo_optional'); ?></div>
            </div>

            <div class="row">
                <div class="input-group">
                    <i class="fas fa-store"></i>
                    <input type="text" name="business_name" placeholder="<?php echo t('business_name'); ?>">
                </div>
                <div class="input-group">
                    <i class="fas fa-tractor"></i>
                    <input type="text" name="farm_name" placeholder="<?php echo t('farm_name'); ?>">
                </div>
            </div>

            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> <?php echo t('nav_register'); ?>
            </button>
        </form>

        <div class="divider">
            <span><?php echo t('already_have_account'); ?></span>
        </div>

        <div class="login-link">
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> <?php echo t('nav_login'); ?></a>
        </div>

        <div class="back-home">
            <a href="index.php"><i class="fas fa-home"></i> <?php echo t('nav_back_home'); ?></a>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Client-side validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate Email
            const email = document.getElementById('email');
            const emailError = document.getElementById('email_error');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email.value)) {
                emailError.classList.add('show');
                email.closest('.input-group').classList.add('invalid');
                isValid = false;
            } else {
                emailError.classList.remove('show');
                email.closest('.input-group').classList.remove('invalid');
            }
            
            // Validate Phone (10 digits)
            const phone = document.getElementById('phone');
            const phoneError = document.getElementById('phone_error');
            const phonePattern = /^[0-9]{10}$/;
            if (!phonePattern.test(phone.value)) {
                phoneError.classList.add('show');
                phone.closest('.input-group').classList.add('invalid');
                isValid = false;
            } else {
                phoneError.classList.remove('show');
                phone.closest('.input-group').classList.remove('invalid');
            }
            
            // Validate Full Name
            const fullName = document.getElementById('full_name');
            const fullNameError = document.getElementById('full_name_error');
            if (fullName.value.trim() === '') {
                fullNameError.classList.add('show');
                fullName.closest('.input-group').classList.add('invalid');
                isValid = false;
            } else {
                fullNameError.classList.remove('show');
                fullName.closest('.input-group').classList.remove('invalid');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });

        // Remove error styling on input
        document.querySelectorAll('input, select').forEach(function(field) {
            field.addEventListener('input', function() {
                this.closest('.input-group').classList.remove('invalid');
                const errorEl = this.closest('.input-group').querySelector('.error-text');
                if (errorEl) {
                    errorEl.classList.remove('show');
                }
            });
        });
    </script>

</body>
</html>