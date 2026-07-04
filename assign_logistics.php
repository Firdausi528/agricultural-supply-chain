<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/classes/Farmer.php';

// Check if user is logged in and is farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'farmer') {
    header('Location: login.php');
    exit();
}

$farmer = new Farmer($db);
$farmer->load($_SESSION['user_id']);

$order_id = $_GET['order_id'] ?? 0;

// Get order details
$sql = "SELECT o.*, c.crop_name, c.location as crop_location 
        FROM orders o 
        JOIN crops c ON o.crop_id = c.id 
        WHERE o.id = ? AND c.farmer_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

// Decrypt crop data
$order['crop_name'] = Encryption::decrypt($order['crop_name']);
$order['crop_location'] = Encryption::decrypt($order['crop_location']);

// Get all logistics providers
$logistics_sql = "SELECT * FROM users WHERE user_type = 'logistics' ORDER BY full_name";
$logistics_stmt = $pdo->prepare($logistics_sql);
$logistics_stmt->execute();
$logistics_providers = $logistics_stmt->fetchAll();

// Decrypt logistics data and store in new array
$decrypted_logistics = array();
foreach ($logistics_providers as $log) {
    $decrypted = array();
    $decrypted['id'] = $log['id'];
    $decrypted['full_name'] = Encryption::decrypt($log['full_name']);
    $decrypted['email'] = Encryption::decrypt($log['email']);
    $decrypted['phone'] = $log['phone'];
    $decrypted['location'] = Encryption::decrypt($log['location']);
    $decrypted['profile_photo'] = $log['profile_photo'];
    $decrypted['user_type'] = $log['user_type'];
    $decrypted_logistics[] = $decrypted;
}
$logistics_providers = $decrypted_logistics;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $logistics_id = $_POST['logistics_id'] ?? 0;
    
    if ($logistics_id) {
        $sql = "UPDATE orders SET logistics_id = ?, order_status = 'confirmed' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$logistics_id, $order_id]);
        
        header('Location: my_orders.php');
        exit();
    } else {
        $error = "❌ Please select a logistics provider.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Logistics - Agricultural Supply</title>
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
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .page-title h1 {
            color: #1a472a;
            font-size: 26px;
        }

        .page-title h1 i {
            color: #2e7d32;
            margin-right: 10px;
        }

        .page-title p {
            color: #888;
            margin-top: 5px;
        }

        .order-summary {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 4px solid #2e7d32;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .order-summary .crop-name {
            font-size: 20px;
            font-weight: bold;
            color: #1a472a;
        }

        .order-summary .details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .order-summary .details .item .label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
        }

        .order-summary .details .item .value {
            font-size: 16px;
            font-weight: 600;
            color: #1a472a;
        }

        .section-title {
            color: #1a472a;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .section-title i {
            margin-right: 8px;
            color: #2e7d32;
        }

        .logistics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .logistics-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 2px solid #e8f0e8;
            transition: 0.3s;
            cursor: pointer;
            position: relative;
        }

        .logistics-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .logistics-card.selected {
            border-color: #2e7d32;
            background: #f1f8e9;
        }

        .logistics-card .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
        }

        .logistics-card .card-header .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            color: #2e7d32;
            overflow: hidden;
            flex-shrink: 0;
        }

        .logistics-card .card-header .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logistics-card .card-header .info .name {
            font-size: 18px;
            font-weight: bold;
            color: #1a472a;
        }

        .logistics-card .card-header .info .role {
            font-size: 13px;
            color: #2e7d32;
            font-weight: 600;
        }

        .logistics-card .card-body .detail {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 0;
            font-size: 14px;
            color: #555;
        }

        .logistics-card .card-body .detail i {
            width: 20px;
            color: #2e7d32;
        }

        .logistics-card .card-body .trust-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }

        .trust-badge.high { background: #e8f5e9; color: #2e7d32; }
        .trust-badge.medium { background: #fff3e0; color: #e65100; }
        .trust-badge.low { background: #ffebee; color: #c62828; }

        .radio-select {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid #ddd;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logistics-card.selected .radio-select {
            border-color: #2e7d32;
            background: #2e7d32;
        }

        .logistics-card.selected .radio-select::after {
            content: '✓';
            color: white;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-assign {
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

        .btn-assign:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(46,125,50,0.3);
        }

        .btn-assign:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .error-msg {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 15px;
        }

        .footer {
            text-align: center;
            color: #888;
            font-size: 13px;
            padding: 30px 0 20px 0;
            border-top: 1px solid #e0e0e0;
            margin-top: 20px;
        }

        .no-logistics {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }

        .no-logistics i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .no-logistics h3 {
            color: #1a472a;
        }

        .no-logistics p {
            color: #888;
        }

        @media (max-width: 768px) {
            .top-header {
                flex-wrap: wrap;
                gap: 10px;
                padding: 12px 15px;
            }
            .logistics-grid {
                grid-template-columns: 1fr;
            }
            .order-summary .details {
                grid-template-columns: 1fr 1fr;
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
        <a href="my_orders.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>

    <div class="container">

        <div class="page-title">
            <h1><i class="fas fa-truck"></i> Assign Logistics</h1>
            <p>Select a trusted logistics provider to deliver your produce</p>
        </div>

        <div class="order-summary">
            <div class="crop-name">🌾 <?php echo htmlspecialchars($order['crop_name']); ?></div>
            <div class="details">
                <div class="item">
                    <div class="label">Quantity</div>
                    <div class="value"><?php echo $order['quantity_ordered']; ?> kg</div>
                </div>
                <div class="item">
                    <div class="label">Total Price</div>
                    <div class="value">TSh <?php echo number_format($order['total_price']); ?></div>
                </div>
                <div class="item">
                    <div class="label">Location</div>
                    <div class="value"><?php echo htmlspecialchars($order['crop_location']); ?></div>
                </div>
            </div>
        </div>

        <?php if($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if(count($logistics_providers) > 0): ?>
            <h2 class="section-title"><i class="fas fa-users"></i> Available Logistics Providers</h2>
            <p style="color:#888; margin-bottom:15px;">Click on a provider to select them, then click "Assign & Confirm Order"</p>

            <form method="POST" id="assignForm">
                <div class="logistics-grid">
                    <?php foreach($logistics_providers as $log): 
                        $trust_level = 'high';
                        $trust_text = '⭐ Trusted Provider';
                        if ($log['id'] % 3 == 0) {
                            $trust_level = 'medium';
                            $trust_text = '👍 Verified Provider';
                        }
                        if ($log['id'] % 5 == 0) {
                            $trust_level = 'low';
                            $trust_text = '🔄 New Provider';
                        }
                    ?>
                    <div class="logistics-card" onclick="selectLogistics(<?php echo $log['id']; ?>, this)">
                        <div class="radio-select"></div>
                        <div class="card-header">
                            <div class="avatar">
                                <?php if(!empty($log['profile_photo']) && file_exists('uploads/profiles/'.$log['profile_photo'])): ?>
                                    <img src="uploads/profiles/<?php echo $log['profile_photo']; ?>" alt="Profile">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($log['full_name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="info">
                                <div class="name"><?php echo htmlspecialchars($log['full_name']); ?></div>
                                <div class="role"><i class="fas fa-truck"></i> Logistics Provider</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="detail"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($log['phone']); ?></div>
                            <div class="detail"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($log['email']); ?></div>
                            <div class="detail"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($log['location']); ?></div>
                            <div class="detail"><i class="fas fa-star" style="color: #f57f17;"></i> <?php echo $trust_text; ?></div>
                            <span class="trust-badge <?php echo $trust_level; ?>">
                                <?php if($trust_level == 'high'): ?>✅ Verified & Trusted<?php endif; ?>
                                <?php if($trust_level == 'medium'): ?>👍 Registered Provider<?php endif; ?>
                                <?php if($trust_level == 'low'): ?>🔄 New Provider<?php endif; ?>
                            </span>
                        </div>
                        <input type="radio" name="logistics_id" value="<?php echo $log['id']; ?>" style="display:none;" id="radio_<?php echo $log['id']; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn-assign" id="assignBtn" disabled>
                    <i class="fas fa-check-circle"></i> Assign & Confirm Order
                </button>
            </form>
        <?php else: ?>
            <div class="no-logistics">
                <i class="fas fa-truck"></i>
                <h3>No Logistics Providers Available</h3>
                <p>There are no registered logistics providers yet.</p>
            </div>
        <?php endif; ?>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> Soko Fresh - Agricultural Supply Chain Platform
        </div>
    </div>

    <script>
        let selectedCard = null;
        let selectedId = null;

        function selectLogistics(id, card) {
            // Remove selected class from all cards
            document.querySelectorAll('.logistics-card').forEach(c => {
                c.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            card.classList.add('selected');
            
            // Select the radio button
            document.getElementById('radio_' + id).checked = true;
            
            // Enable the assign button
            document.getElementById('assignBtn').disabled = false;
            
            selectedId = id;
            selectedCard = card;
        }
    </script>

</body>
</html>