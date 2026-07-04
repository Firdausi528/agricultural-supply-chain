<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/classes/Buyer.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'buyer') {
    header('Location: login.php');
    exit();
}

$buyer_id = $_SESSION['user_id'];
$crop_id = $_GET['crop_id'] ?? 0;
$message = '';
$error = '';

// Load buyer using OOP
$buyer = new Buyer($db);
$buyer->load($buyer_id);

// Get crop details
$sql = "SELECT c.*, u.full_name, u.phone FROM crops c JOIN users u ON c.farmer_id = u.id WHERE c.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$crop_id]);
$crop = $stmt->fetch();

if (!$crop) {
    header('Location: search_crops.php');
    exit();
}

// Decrypt crop data
$crop['crop_name'] = Encryption::decrypt($crop['crop_name']);
$crop['location'] = Encryption::decrypt($crop['location']);
$crop['full_name'] = Encryption::decrypt($crop['full_name']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantity_ordered = $_POST['quantity'];
    $total_price = $quantity_ordered * $crop['price_per_kg'];
    
    try {
        $sql = "INSERT INTO orders (buyer_id, crop_id, quantity_ordered, total_price, order_status) 
                VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$buyer_id, $crop_id, $quantity_ordered, $total_price]);
        $message = "✅ Order placed successfully!";
    } catch(PDOException $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Place Order - Agricultural Supply</title>
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
            max-width: 700px;
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
            margin-bottom: 25px;
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

        .crop-details {
            background: #f8faf8;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e8f0e8;
        }

        .crop-details .crop-name {
            font-size: 22px;
            font-weight: bold;
            color: #1a472a;
        }

        .crop-details .crop-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .crop-details .crop-meta .meta-item .label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }

        .crop-details .crop-meta .meta-item .value {
            font-size: 15px;
            font-weight: 600;
            color: #1a472a;
        }

        .crop-details .farmer-info {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e0e0e0;
            color: #555;
            font-size: 14px;
        }

        .crop-details .farmer-info i {
            color: #2e7d32;
            margin-right: 5px;
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

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quantity-selector .qty-btn {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            background: white;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            color: #1a472a;
        }

        .quantity-selector .qty-btn:hover {
            background: #2e7d32;
            color: white;
            border-color: #2e7d32;
        }

        .quantity-selector .qty-input {
            width: 80px;
            height: 45px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: #f9f9f9;
            color: #1a472a;
        }

        .quantity-selector .qty-input:focus {
            border-color: #2e7d32;
            outline: none;
        }

        .quantity-selector .available-info {
            color: #888;
            font-size: 14px;
        }

        .total-price {
            background: #e8f5e9;
            padding: 15px 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        .total-price .label {
            font-size: 16px;
            color: #1a472a;
            font-weight: 600;
        }

        .total-price .amount {
            font-size: 24px;
            font-weight: bold;
            color: #1a472a;
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

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .footer {
            text-align: center;
            color: #888;
            font-size: 13px;
            padding: 30px 0 20px 0;
            margin-top: 10px;
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
            .crop-details .crop-meta {
                grid-template-columns: 1fr 1fr;
            }
            .quantity-selector {
                flex-wrap: wrap;
            }
            .total-price {
                flex-direction: column;
                gap: 5px;
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
        <a href="search_crops.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Search
        </a>
    </div>

    <div class="container">

        <div class="form-card">
            <div class="form-header">
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h1>Place Order</h1>
                <p>Confirm your order details below</p>
            </div>

            <?php if($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                    <br>
                    <a href="my_orders.php" style="color: #2e7d32; font-weight: bold;">View My Orders →</a>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if(!$message): ?>
                <div class="crop-details">
                    <div class="crop-name"><?php echo htmlspecialchars($crop['crop_name']); ?></div>
                    <div class="crop-meta">
                        <div class="meta-item">
                            <div class="label">Available Quantity</div>
                            <div class="value"><?php echo $crop['quantity']; ?> kg</div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Price per kg</div>
                            <div class="value">TSh <?php echo number_format($crop['price_per_kg']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Location</div>
                            <div class="value"><?php echo htmlspecialchars($crop['location']); ?></div>
                        </div>
                    </div>
                    <div class="farmer-info">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($crop['full_name']); ?>
                        &nbsp;|&nbsp; <i class="fas fa-phone"></i> <?php echo htmlspecialchars($crop['phone']); ?>
                    </div>
                </div>

                <form method="POST" id="orderForm">
                    <div class="form-group">
                        <label><i class="fas fa-weight-hanging"></i> Quantity (kg)</label>
                        <div class="quantity-selector">
                            <button type="button" class="qty-btn" onclick="changeQuantity(-1)">−</button>
                            <input type="number" name="quantity" id="quantity" class="qty-input" value="1" min="1" max="<?php echo $crop['quantity']; ?>" required>
                            <button type="button" class="qty-btn" onclick="changeQuantity(1)">+</button>
                            <span class="available-info">Available: <?php echo $crop['quantity']; ?> kg</span>
                        </div>
                    </div>

                    <div class="total-price">
                        <span class="label"><i class="fas fa-calculator"></i> Total Price</span>
                        <span class="amount" id="totalAmount">TSh <?php echo number_format($crop['price_per_kg']); ?></span>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-check-circle"></i> Confirm Order
                    </button>
                </form>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 15px;">
                <a href="search_crops.php" style="color: #888; text-decoration: none;">← Continue Shopping</a>
            </div>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> Soko Fresh - Agricultural Supply Chain Platform
        </div>
    </div>

    <script>
        const pricePerKg = <?php echo $crop['price_per_kg']; ?>;
        const maxQuantity = <?php echo $crop['quantity']; ?>;

        function updateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const total = quantity * pricePerKg;
            document.getElementById('totalAmount').textContent = 'TSh ' + total.toLocaleString();
            
            const submitBtn = document.getElementById('submitBtn');
            if (quantity < 1 || quantity > maxQuantity) {
                submitBtn.disabled = true;
            } else {
                submitBtn.disabled = false;
            }
        }

        function changeQuantity(change) {
            const input = document.getElementById('quantity');
            let value = parseInt(input.value) || 0;
            value = Math.max(1, Math.min(maxQuantity, value + change));
            input.value = value;
            updateTotal();
        }

        document.getElementById('quantity').addEventListener('input', updateTotal);
        updateTotal();
    </script>

</body>
</html>