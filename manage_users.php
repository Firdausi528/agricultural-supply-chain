<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';
require_once __DIR__ . '/classes/Admin.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Load admin using OOP
$admin = new Admin($db);
$admin->load($_SESSION['user_id']);

$current_lang = getCurrentLang();

// Get search filter
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = array();

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $sql .= " AND user_type = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Decrypt user data for display
foreach ($users as $key => $user) {
    $users[$key]['full_name'] = Encryption::decrypt($user['full_name']);
    $users[$key]['email'] = Encryption::decrypt($user['email']);
    $users[$key]['phone'] = $user['phone'];
    $users[$key]['location'] = Encryption::decrypt($user['location']);
    $users[$key]['business_name'] = !empty($user['business_name']) ? Encryption::decrypt($user['business_name']) : '';
    $users[$key]['farm_name'] = !empty($user['farm_name']) ? Encryption::decrypt($user['farm_name']) : '';
}

// Get counts by role
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$farmer_count = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'farmer'")->fetchColumn();
$buyer_count = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'buyer'")->fetchColumn();
$logistics_count = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'logistics'")->fetchColumn();
$admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <title><?php echo t('manage_users'); ?> - Soko Fresh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body {
            background: #f0f4f0;
            min-height: 100vh;
        }

        .simple-header {
            background: linear-gradient(135deg, #0d1b2a, #1b263b);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .simple-header .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .simple-header .brand i {
            font-size: 24px;
            color: #4caf50;
        }

        .simple-header .brand h2 {
            font-size: 20px;
        }

        .simple-header .brand span {
            color: #4caf50;
        }

        .simple-header a {
            color: white;
            text-decoration: none;
        }

        .simple-header .back-btn {
            background: rgba(255,255,255,0.15);
            padding: 8px 18px;
            border-radius: 8px;
            transition: 0.3s;
            font-size: 14px;
        }

        .simple-header .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 25px auto;
            padding: 0 20px;
        }

        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .page-title h1 {
            color: #1b263b;
            font-size: 24px;
        }

        .page-title h1 i {
            color: #4caf50;
            margin-right: 10px;
        }

        .page-title .total-badge {
            background: #1b263b;
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
        }

        .filter-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-bar .role-filter {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-bar .role-filter a {
            padding: 6px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border: 2px solid #e0e0e0;
            color: #555;
            transition: 0.3s;
        }

        .filter-bar .role-filter a:hover {
            border-color: #4caf50;
            color: #4caf50;
        }

        .filter-bar .role-filter a.active {
            background: #4caf50;
            border-color: #4caf50;
            color: white;
        }

        .filter-bar .role-filter a .count {
            background: rgba(0,0,0,0.1);
            padding: 0 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        .filter-bar .search-box {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }

        .filter-bar .search-box form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-bar .search-box input {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            min-width: 200px;
        }

        .filter-bar .search-box input:focus {
            border-color: #4caf50;
            outline: none;
        }

        .filter-bar .search-box button {
            padding: 8px 18px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .filter-bar .search-box button:hover {
            background: #2e7d32;
        }

        .filter-bar .search-box .clear-btn {
            padding: 8px 14px;
            background: #ddd;
            color: #333;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .filter-bar .search-box .clear-btn:hover {
            background: #bbb;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: white;
            padding: 12px 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-item .number {
            font-size: 22px;
            font-weight: bold;
            color: #1b263b;
        }

        .stat-item .label {
            font-size: 12px;
            color: #888;
            margin-top: 2px;
        }

        .stat-item .label i {
            margin-right: 3px;
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .user-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: 0.3s;
            border: 1px solid #e8f0e8;
            position: relative;
        }

        .user-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .user-card .user-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
        }

        .user-card .user-header .avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            flex-shrink: 0;
            overflow: hidden;
            cursor: pointer;
        }

        .user-card .user-header .avatar img {
            width: 55px;
            height: 55px;
            object-fit: cover;
        }

        .user-card .user-header .avatar.farmer { background: #2e7d32; }
        .user-card .user-header .avatar.buyer { background: #1565c0; }
        .user-card .user-header .avatar.logistics { background: #e65100; }
        .user-card .user-header .avatar.admin { background: #6a1b9a; }

        .user-card .user-header .user-name {
            font-size: 18px;
            font-weight: bold;
            color: #1b263b;
        }

        .user-card .user-header .user-email {
            font-size: 13px;
            color: #888;
        }

        .user-card .user-details {
            padding: 10px 0;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }

        .user-card .user-details .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 14px;
        }

        .user-card .user-details .detail-row .label {
            color: #888;
        }

        .user-card .user-details .detail-row .value {
            font-weight: 500;
            color: #1b263b;
        }

        .user-card .user-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .user-card .user-footer .role-badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .role-badge.farmer { background: #e8f5e9; color: #2e7d32; }
        .role-badge.buyer { background: #e3f2fd; color: #1565c0; }
        .role-badge.logistics { background: #fff3e0; color: #e65100; }
        .role-badge.admin { background: #f3e5f5; color: #6a1b9a; }

        .user-card .user-footer .user-date {
            font-size: 12px;
            color: #888;
        }

        .user-card .user-footer .delete-btn {
            background: #ffebee;
            color: #c62828;
            padding: 4px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: 0.3s;
        }

        .user-card .user-footer .delete-btn:hover {
            background: #c62828;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }

        .empty-state h3 {
            color: #1b263b;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #888;
        }

        .footer {
            text-align: center;
            color: #888;
            font-size: 12px;
            padding: 20px 0 10px 0;
            border-top: 1px solid #e0e0e0;
            margin-top: 25px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: zoomIn 0.3s ease;
        }

        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-content .modal-image {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 10px;
        }

        .modal-content .modal-name {
            font-size: 18px;
            font-weight: bold;
            color: #1b263b;
            margin-top: 10px;
        }

        .modal-content .modal-close {
            margin-top: 15px;
            padding: 8px 25px;
            background: #c62828;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            font-weight: 600;
        }

        .modal-content .modal-close:hover {
            background: #b71c1c;
        }

        @media (max-width: 768px) {
            .simple-header {
                flex-wrap: wrap;
                gap: 10px;
                padding: 12px 15px;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-bar .search-box {
                margin-left: 0;
            }
            .filter-bar .search-box input {
                min-width: 100%;
                flex: 1;
            }
            .page-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .users-grid {
                grid-template-columns: 1fr;
            }
            .filter-bar .role-filter {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <div class="simple-header">
        <div class="brand">
            <i class="fas fa-leaf"></i>
            <h2>Soko <span>Fresh</span></h2>
        </div>
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> <?php echo t('back_to_dashboard'); ?>
        </a>
    </div>

    <div class="container">

        <div class="page-title">
            <h1><i class="fas fa-users"></i> <?php echo t('manage_users'); ?></h1>
            <span class="total-badge"><?php echo t('total'); ?>: <?php echo count($users); ?> <?php echo t('users'); ?></span>
        </div>

        <div class="filter-bar">
            <div class="role-filter">
                <a href="manage_users.php" class="<?php echo empty($role_filter) ? 'active' : ''; ?>"><?php echo t('all'); ?></a>
                <a href="?role=farmer" class="<?php echo $role_filter == 'farmer' ? 'active' : ''; ?>">🚜 <?php echo t('farmers'); ?> <span class="count"><?php echo $farmer_count; ?></span></a>
                <a href="?role=buyer" class="<?php echo $role_filter == 'buyer' ? 'active' : ''; ?>">🛒 <?php echo t('buyers'); ?> <span class="count"><?php echo $buyer_count; ?></span></a>
                <a href="?role=logistics" class="<?php echo $role_filter == 'logistics' ? 'active' : ''; ?>">🚚 <?php echo t('logistics'); ?> <span class="count"><?php echo $logistics_count; ?></span></a>
                <a href="?role=admin" class="<?php echo $role_filter == 'admin' ? 'active' : ''; ?>">⚙️ <?php echo t('admins'); ?> <span class="count"><?php echo $admin_count; ?></span></a>
            </div>

            <div class="search-box">
                <form method="GET">
                    <?php if(!empty($role_filter)): ?>
                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($role_filter); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="<?php echo t('search_users'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                    <?php if(!empty($search)): ?>
                        <a href="manage_users.php<?php echo !empty($role_filter) ? '?role='.$role_filter : ''; ?>" class="clear-btn"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-item">
                <div class="number"><?php echo $total_users; ?></div>
                <div class="label"><i class="fas fa-users"></i> <?php echo t('total'); ?></div>
            </div>
            <div class="stat-item">
                <div class="number"><?php echo $farmer_count; ?></div>
                <div class="label"><i class="fas fa-tractor"></i> <?php echo t('farmers'); ?></div>
            </div>
            <div class="stat-item">
                <div class="number"><?php echo $buyer_count; ?></div>
                <div class="label"><i class="fas fa-shopping-bag"></i> <?php echo t('buyers'); ?></div>
            </div>
            <div class="stat-item">
                <div class="number"><?php echo $logistics_count; ?></div>
                <div class="label"><i class="fas fa-truck"></i> <?php echo t('logistics'); ?></div>
            </div>
            <div class="stat-item">
                <div class="number"><?php echo $admin_count; ?></div>
                <div class="label"><i class="fas fa-user-shield"></i> <?php echo t('admins'); ?></div>
            </div>
        </div>

        <?php if(count($users) > 0): ?>
            <div class="users-grid">
                <?php foreach($users as $user): 
                    $initials = strtoupper(substr($user['full_name'], 0, 1));
                    
                    $role_icon = '';
                    if ($user['user_type'] == 'farmer') $role_icon = '🚜';
                    elseif ($user['user_type'] == 'buyer') $role_icon = '🛒';
                    elseif ($user['user_type'] == 'logistics') $role_icon = '🚚';
                    elseif ($user['user_type'] == 'admin') $role_icon = '⚙️';
                ?>
                <div class="user-card">
                    <div class="user-header">
                        <div class="avatar <?php echo $user['user_type']; ?>" onclick="openModal('<?php echo $user['profile_photo']; ?>', '<?php echo htmlspecialchars($user['full_name']); ?>')">
                            <?php if(!empty($user['profile_photo']) && file_exists('uploads/profiles/'.$user['profile_photo'])): ?>
                                <img src="uploads/profiles/<?php echo $user['profile_photo']; ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo $initials; ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="user-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                    </div>

                    <div class="user-details">
                        <div class="detail-row">
                            <span class="label"><i class="fas fa-phone"></i> <?php echo t('phone'); ?></span>
                            <span class="value"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label"><i class="fas fa-map-marker-alt"></i> <?php echo t('location'); ?></span>
                            <span class="value"><?php echo htmlspecialchars($user['location']); ?></span>
                        </div>
                        <?php if(!empty($user['business_name'])): ?>
                        <div class="detail-row">
                            <span class="label"><i class="fas fa-store"></i> <?php echo t('business'); ?></span>
                            <span class="value"><?php echo htmlspecialchars($user['business_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($user['farm_name'])): ?>
                        <div class="detail-row">
                            <span class="label"><i class="fas fa-tractor"></i> <?php echo t('farm'); ?></span>
                            <span class="value"><?php echo htmlspecialchars($user['farm_name']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="user-footer">
                        <span class="role-badge <?php echo $user['user_type']; ?>">
                            <?php echo $role_icon; ?> <?php echo ucfirst($user['user_type']); ?>
                        </span>
                        <span class="user-date">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                        </span>
                        <?php if($user['user_type'] != 'admin'): ?>
                            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="delete-btn" onclick="return confirm('<?php echo t('delete_user_confirm'); ?>')">
                                <i class="fas fa-trash"></i> <?php echo t('delete'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3><?php echo t('no_users_found'); ?></h3>
                <p><?php echo t('no_users_found_msg'); ?></p>
            </div>
        <?php endif; ?>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo t('brand'); ?> - <?php echo t('footer_sub'); ?>
        </div>
    </div>

    <div class="modal" id="photoModal">
        <div class="modal-content">
            <img src="" alt="Profile Photo" class="modal-image" id="modalImage">
            <div class="modal-name" id="modalName"></div>
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i> <?php echo t('close'); ?></button>
        </div>
    </div>

    <script>
        function openModal(photo, name) {
            if (photo && photo !== '') {
                document.getElementById('modalImage').src = 'uploads/profiles/' + photo;
                document.getElementById('modalName').textContent = name;
                document.getElementById('photoModal').classList.add('show');
            } else {
                alert('<?php echo t('no_photo_uploaded'); ?> ' + name);
            }
        }

        function closeModal() {
            document.getElementById('photoModal').classList.remove('show');
        }

        document.getElementById('photoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

</body>
</html>