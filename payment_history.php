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
$user_type = $_SESSION['user_type'];
$current_lang = getCurrentLang();

// Crop translations
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

// Get transactions based on user type
if ($user_type == 'buyer') {
    $sql = "SELECT t.*, c.crop_name, u.full_name as farmer_name 
            FROM transactions t 
            JOIN orders o ON t.order_id = o.id 
            JOIN crops c ON o.crop_id = c.id 
            JOIN users u ON t.farmer_id = u.id 
            WHERE t.buyer_id = ? AND t.transaction_type = 'payment'
            ORDER BY t.payment_date DESC";
} elseif ($user_type == 'farmer') {
    $sql = "SELECT t.*, c.crop_name, u.full_name as buyer_name 
            FROM transactions t 
            JOIN orders o ON t.order_id = o.id 
            JOIN crops c ON o.crop_id = c.id 
            JOIN users u ON t.buyer_id = u.id 
            WHERE t.farmer_id = ? AND t.transaction_type = 'payment'
            ORDER BY t.payment_date DESC";
} elseif ($user_type == 'logistics') {
    $sql = "SELECT t.*, c.crop_name, u.full_name as farmer_name 
            FROM transactions t 
            JOIN orders o ON t.order_id = o.id 
            JOIN crops c ON o.crop_id = c.id 
            JOIN users u ON t.farmer_id = u.id 
            WHERE t.logistics_id = ? AND t.transaction_type = 'delivery_fee'
            ORDER BY t.payment_date DESC";
} else {
    header('Location: dashboard.php');
    exit();
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

// Decrypt and translate data
foreach ($transactions as &$t) {
    $crop_name = Encryption::decrypt($t['crop_name']);
    if ($current_lang == 'sw' && isset($crop_translations[$crop_name])) {
        $t['crop_name_display'] = $crop_translations[$crop_name];
    } else {
        $t['crop_name_display'] = $crop_name;
    }
    if ($user_type == 'buyer') {
        $t['farmer_name'] = Encryption::decrypt($t['farmer_name']);
    } elseif ($user_type == 'farmer') {
        $t['buyer_name'] = Encryption::decrypt($t['buyer_name']);
    } else {
        $t['farmer_name'] = Encryption::decrypt($t['farmer_name']);
    }
}

// Calculate totals
$total_paid = 0;
$total_pending = 0;
foreach($transactions as $t) {
    if ($t['payment_status'] == 'paid') {
        $total_paid += $t['amount'];
    } else {
        $total_pending += $t['amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('payment_history'); ?> - Soko Fresh</title>
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .page-header h1 {
            color: #1a472a;
            font-size: 24px;
        }

        .page-header h1 i {
            margin-right: 10px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-item {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-item .number {
            font-size: 24px;
            font-weight: bold;
        }

        .stat-item .label {
            font-size: 13px;
            color: #888;
            margin-top: 3px;
        }

        .stat-item.green .number { color: #2e7d32; }
        .stat-item.orange .number { color: #e65100; }
        .stat-item.blue .number { color: #1565c0; }

        .table-wrap {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 12px 15px;
            text-align: left;
            background: #f8faf8;
            color: #1a472a;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tr:hover td {
            background: #f8faf8;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.paid { background: #e8f5e9; color: #2e7d32; }
        .status-badge.pending { background: #fff3e0; color: #e65100; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: #1a472a;
            margin-bottom: 8px;
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
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .stats-row {
                grid-template-columns: 1fr 1fr;
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
            <h1><i class="fas fa-history"></i> <?php echo t('payment_history'); ?></h1>
            <span style="color:#888; font-size:14px;"><?php echo count($transactions); ?> <?php echo t('transactions'); ?></span>
        </div>

        <div class="stats-row">
            <div class="stat-item green">
                <div class="number">TSh <?php echo number_format($total_paid); ?></div>
                <div class="label"><i class="fas fa-check-circle"></i> <?php echo t('total_paid'); ?></div>
            </div>
            <div class="stat-item orange">
                <div class="number">TSh <?php echo number_format($total_pending); ?></div>
                <div class="label"><i class="fas fa-clock"></i> <?php echo t('total_pending'); ?></div>
            </div>
            <div class="stat-item blue">
                <div class="number"><?php echo count($transactions); ?></div>
                <div class="label"><i class="fas fa-receipt"></i> <?php echo t('total_transactions'); ?></div>
            </div>
        </div>

        <div class="table-wrap">
            <?php if(count($transactions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo t('order'); ?></th>
                            <th><?php echo t('crop'); ?></th>
                            <th><?php echo t('amount'); ?></th>
                            <th><?php echo t('method'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('date'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach($transactions as $t): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>#<?php echo str_pad($t['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($t['crop_name_display']); ?></td>
                            <td><strong>TSh <?php echo number_format($t['amount']); ?></strong></td>
                            <td><?php echo ucfirst($t['payment_method']); ?></td>
                            <td><span class="status-badge <?php echo $t['payment_status']; ?>"><?php echo ucfirst($t['payment_status']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($t['payment_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3><?php echo t('no_payment_history'); ?></h3>
                    <p><?php echo t('no_payment_history_msg'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

</body>
</html>