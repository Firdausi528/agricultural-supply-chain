<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$current_lang = getCurrentLang();

// Get user data from database
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Decrypt user data for display
if ($user) {
    $user['full_name'] = Encryption::decrypt($user['full_name']);
    $user['email'] = Encryption::decrypt($user['email']);
    $user['location'] = Encryption::decrypt($user['location']);
    if (!empty($user['business_name'])) {
        $user['business_name'] = Encryption::decrypt($user['business_name']);
    }
    if (!empty($user['farm_name'])) {
        $user['farm_name'] = Encryption::decrypt($user['farm_name']);
    }
}

$message = '';
$error = '';

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            if (!empty($user['profile_photo']) && file_exists('uploads/profiles/'.$user['profile_photo'])) {
                unlink('uploads/profiles/'.$user['profile_photo']);
            }
            
            $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $filename);
            $upload_path = 'uploads/profiles/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $sql = "UPDATE users SET profile_photo = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$new_filename, $user_id]);
                $message = "✅ " . t('photo_updated');
                
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $user['full_name'] = Encryption::decrypt($user['full_name']);
                    $user['email'] = Encryption::decrypt($user['email']);
                    $user['location'] = Encryption::decrypt($user['location']);
                    if (!empty($user['business_name'])) {
                        $user['business_name'] = Encryption::decrypt($user['business_name']);
                    }
                    if (!empty($user['farm_name'])) {
                        $user['farm_name'] = Encryption::decrypt($user['farm_name']);
                    }
                }
            } else {
                $error = "❌ " . t('upload_failed');
            }
        } else {
            $error = "❌ " . t('invalid_file');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('profile_settings'); ?> - Soko Fresh</title>
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

        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border: 1px solid #e8f0e8;
            text-align: center;
        }

        .profile-card .profile-image {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            margin: 0 auto 20px auto;
            overflow: hidden;
            border: 5px solid #2e7d32;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 70px;
            color: #2e7d32;
            box-shadow: 0 8px 25px rgba(46,125,50,0.2);
        }

        .profile-card .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-card h2 {
            color: #1a472a;
            margin-bottom: 5px;
        }

        .profile-card .user-type {
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .profile-card .user-type span {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 14px;
            border-radius: 20px;
            font-weight: 600;
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

        .upload-section {
            border: 2px dashed #ddd;
            padding: 30px;
            border-radius: 12px;
            margin: 20px 0;
        }

        .upload-section .custom-file-label {
            display: block;
            padding: 12px 20px;
            background: #e8f5e9;
            border: 2px dashed #2e7d32;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            color: #2e7d32;
            font-weight: bold;
            transition: 0.3s;
        }

        .upload-section .custom-file-label:hover {
            background: #c8e6c9;
        }

        .upload-section .custom-file-label i {
            margin-right: 8px;
        }

        .upload-section input[type="file"] {
            display: none;
        }

        .btn-upload {
            padding: 12px 35px;
            background: linear-gradient(135deg, #1a472a, #2e7d32);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-upload:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(46,125,50,0.3);
        }

        .btn-upload i {
            margin-right: 8px;
        }

        .user-info {
            text-align: left;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        .user-info .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .user-info .info-row .label {
            color: #888;
        }

        .user-info .info-row .value {
            font-weight: 500;
            color: #1a472a;
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
            .profile-card {
                padding: 25px;
            }
            .profile-card .profile-image {
                width: 150px;
                height: 150px;
                font-size: 60px;
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
        <div class="profile-card">
            <h2><i class="fas fa-user-cog"></i> <?php echo t('profile_settings'); ?></h2>
            <p style="color:#888; font-size:14px; margin-bottom:20px;"><?php echo t('update_photo_label'); ?></p>

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

            <div class="profile-image">
                <?php if(!empty($user['profile_photo']) && file_exists('uploads/profiles/'.$user['profile_photo'])): ?>
                    <img src="uploads/profiles/<?php echo $user['profile_photo']; ?>" alt="Profile Photo">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>

            <div class="user-type">
                <span><?php echo ucfirst($user['user_type']); ?></span>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="upload-section">
                    <p style="color:#888; margin-bottom:10px;">
                        <i class="fas fa-cloud-upload-alt" style="color:#2e7d32; font-size:24px; display:block; margin-bottom:8px;"></i>
                        <?php echo t('upload_photo_label'); ?>
                    </p>
                    <label for="profile_photo" class="custom-file-label">
                        <i class="fas fa-camera"></i> <?php echo t('choose_file'); ?>
                    </label>
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*">
                    <div style="font-size:12px; color:#888; margin-top:8px;" id="file-name"><?php echo t('no_file_chosen'); ?></div>
                    <button type="submit" class="btn-upload" style="margin-top:15px;">
                        <i class="fas fa-upload"></i> <?php echo t('upload_photo'); ?>
                    </button>
                </div>
            </form>

            <div class="user-info">
                <div class="info-row">
                    <span class="label"><i class="fas fa-user"></i> <?php echo t('full_name'); ?></span>
                    <span class="value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label"><i class="fas fa-envelope"></i> <?php echo t('email'); ?></span>
                    <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label"><i class="fas fa-phone"></i> <?php echo t('phone'); ?></span>
                    <span class="value"><?php echo htmlspecialchars($user['phone']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label"><i class="fas fa-map-marker-alt"></i> <?php echo t('location'); ?></span>
                    <span class="value"><?php echo htmlspecialchars($user['location']); ?></span>
                </div>
                <?php if(!empty($user['farm_name'])): ?>
                <div class="info-row">
                    <span class="label"><i class="fas fa-tractor"></i> <?php echo t('farm_name'); ?></span>
                    <span class="value"><?php echo htmlspecialchars($user['farm_name']); ?></span>
                </div>
                <?php endif; ?>
                <?php if(!empty($user['business_name'])): ?>
                <div class="info-row">
                    <span class="label"><i class="fas fa-store"></i> <?php echo t('business_name'); ?></span>
                    <span class="value"><?php echo htmlspecialchars($user['business_name']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

    <script>
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : '<?php echo t('no_file_chosen'); ?>';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>

</body>
</html>