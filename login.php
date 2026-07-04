<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $sql = "SELECT * FROM users";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $all_users = $stmt->fetchAll();

        $user = null;
        foreach ($all_users as $u) {
            $decrypted_email = Encryption::decrypt($u['email']);
            $decrypted_name = Encryption::decrypt($u['full_name']);
            
            if ($decrypted_email === $username || $decrypted_name === $username) {
                $user = $u;
                $user['full_name'] = $decrypted_name;
                $user['email'] = $decrypted_email;
                $user['phone'] = Encryption::decrypt($u['phone']);
                $user['location'] = Encryption::decrypt($u['location']);
                if ($u['business_name']) {
                    $user['business_name'] = Encryption::decrypt($u['business_name']);
                }
                if ($u['farm_name']) {
                    $user['farm_name'] = Encryption::decrypt($u['farm_name']);
                }
                break;
            }
        }

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];
            
            if ($user['user_type'] == 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $error = "❌ " . t('invalid_credentials');
        }
    } catch(PDOException $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <title><?php echo t('nav_login'); ?> - Soko Fresh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body {
            background: linear-gradient(135deg, #1a472a, #2e7d32, #4caf50);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 400px;
            max-width: 90%;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo a {
            text-decoration: none;
            color: inherit;
        }

        .logo i {
            font-size: 50px;
            color: #2e7d32;
            background: #e8f5e9;
            padding: 15px;
            border-radius: 50%;
        }

        .logo h1 {
            font-size: 28px;
            color: #1a472a;
            margin-top: 10px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 18px;
            z-index: 5;
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: 0.3s;
            background: #f9f9f9;
        }

        .input-group input:focus {
            border-color: #2e7d32;
            outline: none;
            background: white;
            box-shadow: 0 0 15px rgba(46, 125, 50, 0.1);
        }

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(46, 125, 50, 0.3);
        }

        .btn-login i {
            margin-right: 10px;
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

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .register-link a {
            color: #2e7d32;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
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
            font-size: 14px;
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
    </style>
</head>
<body>

    <div class="container">
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-leaf"></i>
                <h1>🌾 <?php echo t('brand'); ?></h1>
            </a>
            <p><?php echo t('welcome_back'); ?></p>
        </div>

        <?php if($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="<?php echo t('username_or_email'); ?>" required>
            </div>

            <div class="input-group" style="position:relative;">
                <i class="fas fa-lock" style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#888; z-index:5;"></i>
                <input type="password" name="password" id="loginPassword" placeholder="<?php echo t('password'); ?>" required style="width:100%; padding:15px 50px 15px 50px; border:2px solid #e0e0e0; border-radius:10px; font-size:16px; background:#f9f9f9; transition:0.3s;">
                <i class="fas fa-eye" id="toggleLoginPassword" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); color:#888; cursor:pointer; z-index:5;" onclick="togglePassword('loginPassword', 'toggleLoginPassword')"></i>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> <?php echo t('nav_login'); ?>
            </button>
        </form>

        <div class="divider">
            <span>or</span>
        </div>

        <div class="register-link">
            <?php echo t('no_account'); ?> <a href="register.php"><?php echo t('nav_register'); ?></a>
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
    </script>

</body>
</html>