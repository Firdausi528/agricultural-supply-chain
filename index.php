<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/language.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Farmer.php';
require_once __DIR__ . '/classes/Buyer.php';
require_once __DIR__ . '/classes/Logistics.php';
require_once __DIR__ . '/classes/Admin.php';

// Create database connection using OOP
$db = new Database();
$pdo = $db->getConnection();

// Get stats using OOP
$admin = new Admin($db);
$total_users = $admin->getTotalUsers();
$total_crops = $admin->getTotalCrops();
$total_orders = $admin->getTotalOrders();
$total_farmers = $admin->getTotalFarmers();

// Get current language
$current_lang = getCurrentLang();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soko Fresh - <?php echo t('brand'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        html { scroll-behavior: smooth; }
        body { background: #f0f4f0; overflow-x: hidden; }

        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fadeDown { animation: fadeInDown 1s ease; }
        .animate-fadeUp { animation: fadeInUp 1s ease; }
        .animate-fadeLeft { animation: fadeInLeft 1s ease; }
        .animate-fadeRight { animation: fadeInRight 1s ease; }
        .animate-zoom { animation: zoomIn 1s ease; }
        .animate-float { animation: float 3s ease-in-out infinite; }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, #1a472a, #2e7d32);
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
            gap: 10px;
        }

        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            font-size: 22px;
            font-weight: bold;
        }

        .navbar .brand i {
            font-size: 26px;
            color: #4caf50;
            animation: pulse 2s infinite;
        }

        .navbar .brand span { color: #4caf50; }

        .navbar .nav-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .navbar .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 13px;
            transition: 0.3s;
            padding: 6px 12px;
            border-radius: 8px;
        }

        .navbar .nav-links a:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-2px);
        }

        .navbar .nav-links .btn-login {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .navbar .nav-links .btn-login:hover { background: rgba(255,255,255,0.3); }

        .navbar .nav-links .btn-register {
            background: #4caf50;
            border: none;
        }

        .navbar .nav-links .btn-register:hover { background: #388e3c; }

        .navbar .nav-links .btn-dashboard {
            background: #ff9800;
            border: none;
        }

        .navbar .nav-links .btn-dashboard:hover { background: #f57c00; }

        /* Language Switcher */
        .language-switcher {
            display: flex;
            gap: 4px;
            margin-right: 5px;
        }

        .language-switcher .lang-btn {
            padding: 4px 10px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            background: transparent;
            color: white;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
        }

        .language-switcher .lang-btn:hover {
            background: rgba(255,255,255,0.15);
        }

        .language-switcher .lang-btn.active {
            background: #4caf50;
            border-color: #4caf50;
            color: white;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #e8f5e9, #a5d6a7);
            padding: 60px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero::before {
            content: '🌾🌿🌱🍃🌾🌿🌱🍃';
            position: absolute;
            font-size: 50px;
            opacity: 0.1;
            top: 20px;
            right: 20px;
            letter-spacing: 20px;
            transform: rotate(15deg);
        }

        .hero::after {
            content: '🚜🌾🌿🌱🍃🌾🌿';
            position: absolute;
            font-size: 40px;
            opacity: 0.1;
            bottom: 20px;
            left: 20px;
            letter-spacing: 15px;
            transform: rotate(-10deg);
        }

        .hero-content {
            max-width: 700px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 40px;
            color: #1a472a;
            margin-bottom: 15px;
            animation: fadeInDown 1s ease;
        }

        .hero h1 i { color: #2e7d32; }

        .hero p {
            font-size: 17px;
            color: #3a5a3a;
            max-width: 600px;
            margin: 0 auto 25px auto;
            animation: fadeInUp 1.2s ease;
        }

        .hero .btn-get-started {
            padding: 14px 40px;
            background: linear-gradient(135deg, #1a472a, #2e7d32);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
            animation: fadeInUp 1.4s ease;
        }

        .hero .btn-get-started:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 40px rgba(46,125,50,0.4);
        }

        .hero .hero-icons {
            font-size: 35px;
            margin-top: 25px;
            animation: fadeInUp 1.6s ease;
        }

        .hero .hero-icons span {
            display: inline-block;
            margin: 0 12px;
            animation: float 3s ease-in-out infinite;
        }

        .hero .hero-icons span:nth-child(2) { animation-delay: 0.5s; }
        .hero .hero-icons span:nth-child(3) { animation-delay: 1s; }

        /* Container */
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .section-title h2 {
            font-size: 30px;
            color: #1a472a;
            position: relative;
            display: inline-block;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 4px;
            background: #2e7d32;
            border-radius: 2px;
        }

        .section-title p {
            color: #888;
            font-size: 15px;
            margin-top: 12px;
        }

        /* Features Banner */
        .features-banner {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            max-width: 850px;
            margin: -25px auto 25px auto;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        .features-banner .feature-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            transition: 0.3s;
            border-bottom: 4px solid #2e7d32;
        }

        .features-banner .feature-box:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .features-banner .feature-box .icon { font-size: 35px; display: block; margin-bottom: 6px; }
        .features-banner .feature-box h4 { font-size: 16px; color: #1a472a; }
        .features-banner .feature-box p { font-size: 12px; color: #888; margin-top: 2px; }

        /* About Section */
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: center;
            background: white;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .about-grid .about-text h3 {
            font-size: 28px;
            color: #1a472a;
            margin-bottom: 12px;
        }

        .about-grid .about-text h3 i { color: #2e7d32; }

        .about-grid .about-text p {
            color: #555;
            line-height: 1.8;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .about-grid .about-text .mission-box {
            background: #e8f5e9;
            padding: 18px 22px;
            border-radius: 10px;
            border-left: 5px solid #2e7d32;
            margin-top: 12px;
        }

        .about-grid .about-text .mission-box strong {
            color: #1a472a;
            font-size: 15px;
        }

        .about-grid .about-text .mission-box p { margin-bottom: 4px; font-size: 13px; }

        .about-grid .about-image {
            text-align: center;
            font-size: 100px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            padding: 30px;
            border-radius: 18px;
            animation: float 4s ease-in-out infinite;
        }

        /* Services */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
        }

        .service-card {
            background: white;
            padding: 30px 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: 0.4s;
            border: 2px solid transparent;
        }

        .service-card:hover {
            transform: translateY(-8px);
            border-color: #2e7d32;
            box-shadow: 0 12px 40px rgba(46,125,50,0.12);
        }

        .service-card .icon-wrap {
            width: 70px;
            height: 70px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            font-size: 30px;
            color: #2e7d32;
            transition: 0.3s;
        }

        .service-card:hover .icon-wrap {
            background: #2e7d32;
            color: white;
            transform: rotate(10deg) scale(1.1);
        }

        .service-card h4 {
            font-size: 18px;
            color: #1a472a;
            margin-bottom: 6px;
        }

        .service-card p {
            color: #888;
            font-size: 13px;
            line-height: 1.6;
        }

        /* Tips Section */
        .tips-section {
            background: white;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .tip-card {
            background: #f8faf8;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #2e7d32;
            transition: 0.3s;
            cursor: pointer;
        }

        .tip-card:hover {
            transform: translateX(6px);
            background: #e8f5e9;
        }

        .tip-card .tip-icon { font-size: 28px; margin-bottom: 6px; }
        .tip-card h4 { color: #1a472a; font-size: 17px; margin-bottom: 4px; }
        .tip-card p { color: #666; font-size: 13px; line-height: 1.6; }
        .tip-card .tip-tag {
            display: inline-block;
            padding: 2px 10px;
            background: #2e7d32;
            color: white;
            border-radius: 20px;
            font-size: 10px;
            margin-top: 6px;
            font-weight: 600;
        }

        .tip-card .read-more-btn {
            display: inline-block;
            margin-top: 8px;
            color: #2e7d32;
            font-weight: 600;
            font-size: 12px;
            text-decoration: none;
            transition: 0.3s;
        }

        .tip-card .read-more-btn:hover {
            color: #1a472a;
            text-decoration: underline;
        }

        /* Modal */
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
            animation: fadeInDown 0.3s ease;
        }

        .modal.show { display: flex; }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 18px;
            max-width: 550px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideDown 0.4s ease;
        }

        .modal-content .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .modal-content .modal-header h3 {
            color: #1a472a;
            font-size: 22px;
        }

        .modal-content .modal-header .close-modal {
            font-size: 28px;
            cursor: pointer;
            color: #888;
            transition: 0.3s;
            background: none;
            border: none;
        }

        .modal-content .modal-header .close-modal:hover { color: #c62828; }

        .modal-content .modal-body p {
            color: #555;
            line-height: 1.8;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .modal-content .modal-body .modal-tag {
            display: inline-block;
            padding: 3px 12px;
            background: #2e7d32;
            color: white;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .modal-content .modal-body ul {
            margin: 8px 0 8px 18px;
            color: #555;
            line-height: 1.8;
        }

        /* Steps */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .step-card {
            background: white;
            padding: 30px 15px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: 0.4s;
            position: relative;
            overflow: hidden;
        }

        .step-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1a472a, #4caf50);
            transform: scaleX(0);
            transition: 0.4s;
        }

        .step-card:hover::before { transform: scaleX(1); }
        .step-card:hover { transform: translateY(-6px); box-shadow: 0 12px 40px rgba(0,0,0,0.1); }

        .step-card .step-number {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1a472a, #2e7d32);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: bold;
            margin: 0 auto 12px auto;
        }

        .step-card h4 { font-size: 17px; color: #1a472a; }
        .step-card p { color: #888; font-size: 13px; margin-top: 4px; line-height: 1.6; }

        /* CTA */
        .cta-section {
            background: linear-gradient(135deg, #1a472a, #2e7d32);
            padding: 50px 30px;
            border-radius: 18px;
            text-align: center;
            color: white;
        }

        .cta-section h2 { font-size: 30px; margin-bottom: 8px; }
        .cta-section p { font-size: 16px; opacity: 0.9; margin-bottom: 20px; }

        .cta-section .btn-cta {
            padding: 14px 35px;
            background: #ff9800;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .cta-section .btn-cta:hover {
            background: #f57c00;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 30px rgba(255,152,0,0.4);
        }

        /* Footer */
        .footer {
            background: #0d1b2a;
            color: white;
            padding: 30px 25px 15px 25px;
            margin-top: 30px;
        }

        .footer .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 25px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .footer .footer-grid h4 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #4caf50;
        }

        .footer .footer-grid p {
            color: #a5d6a7;
            font-size: 13px;
            line-height: 1.7;
        }

        .footer .footer-grid a {
            color: #a5d6a7;
            text-decoration: none;
            display: block;
            margin: 4px 0;
            font-size: 13px;
            transition: 0.3s;
        }

        .footer .footer-grid a:hover {
            color: white;
            padding-left: 5px;
        }

        .footer .footer-bottom {
            text-align: center;
            padding-top: 15px;
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
            font-size: 12px;
            color: #a5d6a7;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 8px; padding: 10px 15px; }
            .navbar .nav-links { width: 100%; justify-content: center; }
            .navbar .nav-links a { font-size: 11px; padding: 4px 8px; }
            .language-switcher .lang-btn { font-size: 10px; padding: 2px 8px; }
            .hero h1 { font-size: 26px; }
            .hero p { font-size: 14px; }
            .about-grid { grid-template-columns: 1fr; padding: 20px; }
            .features-banner { grid-template-columns: 1fr 1fr; margin-top: -15px; }
            .about-grid .about-image { font-size: 70px; padding: 20px; }
            .tips-section { padding: 20px; }
            .cta-section { padding: 25px 15px; }
            .cta-section h2 { font-size: 22px; }
            .footer .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .modal-content { padding: 20px; }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <a href="index.php" class="brand">
            <i class="fas fa-leaf"></i>
            <?php echo t('brand'); ?>
        </a>
        <div class="nav-links">
            <!-- Language Switcher - ONLY ON HOMEPAGE -->
            <div class="language-switcher">
                <a href="?lang=en" class="lang-btn <?php echo $current_lang == 'en' ? 'active' : ''; ?>">🇬🇧 EN</a>
                <a href="?lang=sw" class="lang-btn <?php echo $current_lang == 'sw' ? 'active' : ''; ?>">🇹🇿 SW</a>
            </div>
            <a href="#about"><?php echo t('nav_about'); ?></a>
            <a href="#services"><?php echo t('nav_services'); ?></a>
            <a href="#tips"><?php echo t('nav_tips'); ?></a>
            <a href="#how-it-works"><?php echo t('nav_how'); ?></a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="btn-dashboard"><i class="fas fa-tachometer-alt"></i> <?php echo t('nav_dashboard'); ?></a>
            <?php else: ?>
                <a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> <?php echo t('nav_login'); ?></a>
                <a href="register.php" class="btn-register"><i class="fas fa-user-plus"></i> <?php echo t('nav_register'); ?></a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>🌾 <?php echo t('hero_title'); ?></h1>
            <p><?php echo t('hero_subtitle'); ?></p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="btn-get-started"><i class="fas fa-tachometer-alt"></i> <?php echo t('nav_dashboard'); ?></a>
            <?php else: ?>
                <a href="register.php" class="btn-get-started"><?php echo t('hero_btn'); ?></a>
            <?php endif; ?>
            <div class="hero-icons">
                <span>🚜</span>
                <span>🌾</span>
                <span>📦</span>
                <span>🚚</span>
                <span>🌿</span>
            </div>
        </div>
    </section>

    <!-- Features Banner -->
    <div class="features-banner">
        <div class="feature-box animate-zoom">
            <span class="icon">🌱</span>
            <h4><?php echo t('feature1_title'); ?></h4>
            <p><?php echo t('feature1_desc'); ?></p>
        </div>
        <div class="feature-box animate-zoom" style="animation-delay:0.2s;">
            <span class="icon">💰</span>
            <h4><?php echo t('feature2_title'); ?></h4>
            <p><?php echo t('feature2_desc'); ?></p>
        </div>
        <div class="feature-box animate-zoom" style="animation-delay:0.4s;">
            <span class="icon">🚚</span>
            <h4><?php echo t('feature3_title'); ?></h4>
            <p><?php echo t('feature3_desc'); ?></p>
        </div>
        <div class="feature-box animate-zoom" style="animation-delay:0.6s;">
            <span class="icon">🤝</span>
            <h4><?php echo t('feature4_title'); ?></h4>
            <p><?php echo t('feature4_desc'); ?></p>
        </div>
    </div>

    <!-- Container -->
    <div class="container">

        <!-- About Section -->
        <div id="about" class="about-grid animate-fadeUp">
            <div class="about-text">
                <h3><i class="fas fa-info-circle"></i> <?php echo t('about_title'); ?></h3>
                <p><?php echo t('about_p1'); ?></p>
                <p><?php echo t('about_p2'); ?></p>
                <p><?php echo t('about_p3'); ?></p>

                <div class="mission-box">
                    <strong>🌍 <?php echo t('mission'); ?></strong>
                    <p><?php echo t('mission_text'); ?></p>
                    <br>
                    <strong>👁️ <?php echo t('vision'); ?></strong>
                    <p><?php echo t('vision_text'); ?></p>
                    <br>
                    <strong>💡 <?php echo t('values'); ?></strong>
                    <p><?php echo t('values_text'); ?></p>
                </div>
            </div>
            <div class="about-image animate-float">
                <i class="fas fa-tractor"></i>
            </div>
        </div>

        <!-- Services -->
        <div id="services" style="margin-top: 40px;">
            <div class="section-title animate-fadeDown">
                <h2><i class="fas fa-cogs" style="color:#2e7d32;"></i> <?php echo t('services_title'); ?></h2>
                <p><?php echo t('services_sub'); ?></p>
            </div>
            <div class="services-grid">
                <div class="service-card animate-fadeLeft">
                    <div class="icon-wrap"><i class="fas fa-tractor"></i></div>
                    <h4><?php echo t('service1_title'); ?></h4>
                    <p><?php echo t('service1_desc'); ?></p>
                </div>
                <div class="service-card animate-fadeUp">
                    <div class="icon-wrap"><i class="fas fa-shopping-bag"></i></div>
                    <h4><?php echo t('service2_title'); ?></h4>
                    <p><?php echo t('service2_desc'); ?></p>
                </div>
                <div class="service-card animate-fadeRight">
                    <div class="icon-wrap"><i class="fas fa-truck"></i></div>
                    <h4><?php echo t('service3_title'); ?></h4>
                    <p><?php echo t('service3_desc'); ?></p>
                </div>
            </div>
        </div>

        <!-- Tips & Learning -->
        <div id="tips" style="margin-top: 40px;">
            <div class="section-title animate-fadeDown">
                <h2><i class="fas fa-lightbulb" style="color:#2e7d32;"></i> <?php echo t('tips_title'); ?></h2>
                <p><?php echo t('tips_sub'); ?></p>
            </div>
            <div class="tips-section">
                <div class="tips-grid">
                    <div class="tip-card">
                        <div class="tip-icon">🌱</div>
                        <h4><?php echo t('tip1_title'); ?></h4>
                        <p><?php echo t('tip1_desc'); ?></p>
                        <span class="tip-tag"><?php echo t('tip_tag_farmer'); ?></span>
                        <br>
                        <a href="#" class="read-more-btn" onclick="openTip('modern-farming')"><?php echo t('read_more'); ?></a>
                    </div>
                    <div class="tip-card">
                        <div class="tip-icon">💧</div>
                        <h4><?php echo t('tip2_title'); ?></h4>
                        <p><?php echo t('tip2_desc'); ?></p>
                        <span class="tip-tag"><?php echo t('tip_tag_farmer'); ?></span>
                        <br>
                        <a href="#" class="read-more-btn" onclick="openTip('irrigation')"><?php echo t('read_more'); ?></a>
                    </div>
                    <div class="tip-card">
                        <div class="tip-icon">🧪</div>
                        <h4><?php echo t('tip3_title'); ?></h4>
                        <p><?php echo t('tip3_desc'); ?></p>
                        <span class="tip-tag"><?php echo t('tip_tag_farmer'); ?></span>
                        <br>
                        <a href="#" class="read-more-btn" onclick="openTip('pest-control')"><?php echo t('read_more'); ?></a>
                    </div>
                    <div class="tip-card">
                        <div class="tip-icon">🛒</div>
                        <h4><?php echo t('tip4_title'); ?></h4>
                        <p><?php echo t('tip4_desc'); ?></p>
                        <span class="tip-tag"><?php echo t('tip_tag_buyer'); ?></span>
                        <br>
                        <a href="#" class="read-more-btn" onclick="openTip('quality-produce')"><?php echo t('read_more'); ?></a>
                    </div>
                    <div class="tip-card">
                        <div class="tip-icon">💰</div>
                        <h4><?php echo t('tip5_title'); ?></h4>
                        <p><?php echo t('tip5_desc'); ?></p>
                        <span class="tip-tag"><?php echo t('tip_tag_buyer'); ?></span>
                        <br>
                        <a href="#" class="read-more-btn" onclick="openTip('smart-buying')"><?php echo t('read_more'); ?></a>
                    </div>
                    <div class="tip-card">
                        <div class="tip-icon">📦</div>
                        <h4><?php echo t('tip6_title'); ?></h4>
                        <p><?php echo t('tip6_desc'); ?></p>
                        <span class="tip-tag"><?php echo t('tip_tag_farmer'); ?></span>
                        <br>
                        <a href="#" class="read-more-btn" onclick="openTip('post-harvest')"><?php echo t('read_more'); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div id="how-it-works" style="margin-top: 40px;">
            <div class="section-title animate-fadeDown">
                <h2><i class="fas fa-route" style="color:#2e7d32;"></i> <?php echo t('steps_title'); ?></h2>
                <p><?php echo t('steps_sub'); ?></p>
            </div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h4><?php echo t('step1'); ?></h4>
                    <p><?php echo t('step1_desc'); ?></p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h4><?php echo t('step2'); ?></h4>
                    <p><?php echo t('step2_desc'); ?></p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h4><?php echo t('step3'); ?></h4>
                    <p><?php echo t('step3_desc'); ?></p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h4><?php echo t('step4'); ?></h4>
                    <p><?php echo t('step4_desc'); ?></p>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div style="margin-top: 40px;">
            <div class="cta-section animate-zoom">
                <h2><?php echo t('cta_title'); ?></h2>
                <p><?php echo t('cta_desc'); ?></p>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn-cta"><i class="fas fa-tachometer-alt"></i> <?php echo t('nav_dashboard'); ?></a>
                <?php else: ?>
                    <a href="register.php" class="btn-cta"><?php echo t('cta_btn'); ?></a>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Modal for Tip Details -->
    <div class="modal" id="tipModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Tip Title</h3>
                <button class="close-modal" onclick="closeTip()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <p>Content goes here...</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div>
                <h4><i class="fas fa-leaf"></i> Soko Fresh</h4>
                <p><?php echo t('footer_about'); ?></p>
            </div>
            <div>
                <h4><?php echo t('footer_links'); ?></h4>
                <a href="index.php"><?php echo t('footer_home'); ?></a>
                <a href="#about"><?php echo t('footer_about_link'); ?></a>
                <a href="#services"><?php echo t('footer_services'); ?></a>
                <a href="#tips"><?php echo t('footer_tips'); ?></a>
                <a href="#how-it-works"><?php echo t('footer_how'); ?></a>
            </div>
            <div>
                <h4><?php echo t('footer_getstarted'); ?></h4>
                <a href="register.php"><?php echo t('footer_create'); ?></a>
                <a href="login.php"><?php echo t('footer_login'); ?></a>
            </div>
            <div>
                <h4><?php echo t('footer_contact'); ?></h4>
                <p><i class="fas fa-envelope"></i> info@sokofresh.com</p>
                <p><i class="fas fa-phone"></i> +255 123 456 789</p>
                <p><i class="fas fa-map-marker-alt"></i> Dar es Salaam, Tanzania</p>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> Soko Fresh. <?php echo t('footer_copyright'); ?> | Made with ❤️ in Tanzania
        </div>
    </footer>

    <script>
        // Tip Data for the popup
        const tipData = {
            'modern-farming': {
                titleEn: '🌱 Modern Farming Methods',
                titleSw: '🌱 Mbinu za Kilimo cha Kisasa',
                contentEn: `
                    <p><strong>Modern farming methods are essential for increasing productivity and sustainability.</strong></p>
                    <p>Here are some key techniques every farmer should know:</p>
                    <ul>
                        <li><strong>Crop Rotation:</strong> Planting different crops in sequence to maintain soil fertility and reduce pest buildup.</li>
                        <li><strong>Organic Farming:</strong> Using natural fertilizers and pest control methods to produce healthy, chemical-free food.</li>
                        <li><strong>Soil Management:</strong> Testing soil regularly, adding compost, and using cover crops to maintain soil health.</li>
                        <li><strong>Precision Farming:</strong> Using technology to monitor crops and apply inputs precisely where needed.</li>
                        <li><strong>Intercropping:</strong> Growing two or more crops together to maximize land use and reduce pest problems.</li>
                    </ul>
                    <p><strong>💡 Tip:</strong> Start small by trying one new method each season and observe the results.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">For Farmers</span></p>
                `,
                contentSw: `
                    <p><strong>Mbinu za kisasa za kilimo ni muhimu kwa kuongeza tija na uendelevu.</strong></p>
                    <p>Hapa kuna mbinu muhimu ambazo kila mkulima anapaswa kujua:</p>
                    <ul>
                        <li><strong>Mzunguko wa Mazao:</strong> Kupanda mazao tofauti kwa mlolongo ili kudumisha rutuba ya udongo na kupunguza mkusanyiko wa wadudu.</li>
                        <li><strong>Kilimo Hai:</strong> Kutumia mbolea asilia na mbinu za kudhibiti wadudu ili kuzalisha chakula chenye afya, kisichokuwa na kemikali.</li>
                        <li><strong>Usimamizi wa Udongo:</strong> Kupima udongo mara kwa mara, kuongeza mboji, na kutumia mazao ya kufunika ili kudumisha afya ya udongo.</li>
                        <li><strong>Kilimo cha Usahihi:</strong> Kutumia teknolojia kufuatilia mazao na kutumia pembejeo mahali panapohitajika.</li>
                        <li><strong>Kilimo Mchanganyiko:</strong> Kupanda mazao mawili au zaidi pamoja ili kuongeza matumizi ya ardhi na kupunguza matatizo ya wadudu.</li>
                    </ul>
                    <p><strong>💡 Kidokezo:</strong> Anza kidogo kwa kujaribu mbinu moja mpya kila msimu na uangalie matokeo.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">Kwa Wakulima</span></p>
                `
            },
            'irrigation': {
                titleEn: '💧 Irrigation & Water Management',
                titleSw: '💧 Umwagiliaji na Usimamizi wa Maji',
                contentEn: `
                    <p><strong>Efficient water management is crucial for successful farming.</strong></p>
                    <p>Here are some effective irrigation techniques:</p>
                    <ul>
                        <li><strong>Drip Irrigation:</strong> Delivers water directly to plant roots, reducing water waste by up to 50%.</li>
                        <li><strong>Rainwater Harvesting:</strong> Collecting and storing rainwater for dry seasons.</li>
                        <li><strong>Mulching:</strong> Covering soil with organic material to retain moisture and reduce evaporation.</li>
                        <li><strong>Scheduled Irrigation:</strong> Watering at the right time (early morning or evening) to minimize water loss.</li>
                        <li><strong>Soil Moisture Monitoring:</strong> Using simple tools to check when plants actually need water.</li>
                    </ul>
                    <p><strong>💡 Tip:</strong> Invest in a simple drip irrigation system - it saves water and increases yields.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">For Farmers</span></p>
                `,
                contentSw: `
                    <p><strong>Usimamizi bora wa maji ni muhimu kwa kilimo chenye mafanikio.</strong></p>
                    <p>Hapa kuna mbinu bora za umwagiliaji:</p>
                    <ul>
                        <li><strong>Umwagiliaji kwa Matone:</strong> Hupeleka maji moja kwa moja kwenye mizizi ya mimea, kupunguza upotevu wa maji hadi 50%.</li>
                        <li><strong>Uvunaji wa Maji ya Mvua:</strong> Kukusanya na kuhifadhi maji ya mvua kwa ajili ya misimu ya kiangazi.</li>
                        <li><strong>Kufunika Udongo:</strong> Kufunika udongo kwa vitu vya asili ili kuhifadhi unyevu na kupunguza uvukizi.</li>
                        <li><strong>Umwagiliaji kwa Ratiba:</strong> Kumwagilia kwa wakati unaofaa (asubuhi mapema au jioni) ili kupunguza upotevu wa maji.</li>
                        <li><strong>Ufuatiliaji wa Unyevu wa Udongo:</strong> Kutumia vyombo rahisi kuangalia wakati mimea inahitaji maji.</li>
                    </ul>
                    <p><strong>💡 Kidokezo:</strong> Wekeza katika mfumo rahisi wa umwagiliaji kwa matone - unaokoa maji na kuongeza mavuno.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">Kwa Wakulima</span></p>
                `
            },
            'pest-control': {
                titleEn: '🧪 Pest & Disease Control',
                titleSw: '🧪 Udhibiti wa Wadudu na Magonjwa',
                contentEn: `
                    <p><strong>Protecting crops from pests and diseases is essential for food security.</strong></p>
                    <p>Here are effective and sustainable methods:</p>
                    <ul>
                        <li><strong>Integrated Pest Management (IPM):</strong> Combining biological, cultural, and chemical methods.</li>
                        <li><strong>Natural Predators:</strong> Encouraging beneficial insects like ladybugs that eat pests.</li>
                        <li><strong>Neem Oil:</strong> A natural pesticide that is safe for beneficial insects.</li>
                        <li><strong>Crop Diversity:</strong> Planting different crops reduces pest outbreaks.</li>
                        <li><strong>Regular Monitoring:</strong> Checking crops weekly to catch problems early.</li>
                    </ul>
                    <p><strong>💡 Tip:</strong> Prevention is better than cure - start with healthy seeds and good soil.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">For Farmers</span></p>
                `,
                contentSw: `
                    <p><strong>Kulinda mazao kutokana na wadudu na magonjwa ni muhimu kwa usalama wa chakula.</strong></p>
                    <p>Hapa kuna mbinu bora na endelevu:</p>
                    <ul>
                        <li><strong>Usimamizi Jumuishi wa Wadudu:</strong> Kuchanganya mbinu za kibaolojia, kitamaduni, na kemikali.</li>
                        <li><strong>Wadudu Waharibifu wa Asili:</strong> Kuhimiza wadudu wanaofaa kama vile kumbukumbu wanaokula wadudu waharibifu.</li>
                        <li><strong>Mafuta ya Mwarobaini:</strong> Dawa ya asili ya kudhibiti wadudu ambayo ni salama kwa wadudu wanaofaa.</li>
                        <li><strong>Mseto wa Mazao:</strong> Kupanda mazao tofauti hupunguza milipuko ya wadudu.</li>
                        <li><strong>Ufuatiliaji wa Mara kwa Mara:</strong> Kukagua mazao kila wiki ili kugundua matatizo mapema.</li>
                    </ul>
                    <p><strong>💡 Kidokezo:</strong> Kinga ni bora kuliko tiba - anza na mbegu zenye afya na udongo mzuri.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">Kwa Wakulima</span></p>
                `
            },
            'quality-produce': {
                titleEn: '🛒 How to Choose Quality Produce',
                titleSw: '🛒 Jinsi ya Kuchagua Mazao Bora',
                contentEn: `
                    <p><strong>Knowing how to choose fresh, quality produce is a valuable skill for buyers.</strong></p>
                    <p>Here are some tips:</p>
                    <ul>
                        <li><strong>Color:</strong> Vibrant, consistent colors indicate freshness.</li>
                        <li><strong>Texture:</strong> Firm, crisp produce is usually fresher.</li>
                        <li><strong>Smell:</strong> Fresh produce has a natural, pleasant smell.</li>
                        <li><strong>Weight:</strong> Heavy for size means juicy and fresh.</li>
                        <li><strong>Seasonality:</strong> Buy produce that is in season - it's fresher and cheaper.</li>
                    </ul>
                    <p><strong>💡 Tip:</strong> Build relationships with farmers for consistent quality and fair prices.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">For Buyers</span></p>
                `,
                contentSw: `
                    <p><strong>Kujua jinsi ya kuchagua mazao safi na bora ni ujuzi muhimu kwa wanunuzi.</strong></p>
                    <p>Hapa kuna vidokezo:</p>
                    <ul>
                        <li><strong>Rangi:</strong> Rangi angavu na thabiti zinaonyesha ubichi.</li>
                        <li><strong>Umbile:</strong> Mazao magumu na magumu kwa kawaida ni mabichi zaidi.</li>
                        <li><strong>Harufu:</strong> Mazao mabichi yana harufu ya asili na ya kupendeza.</li>
                        <li><strong>Uzito:</strong> Uzito kwa ukubwa unamaanisha maji mengi na ubichi.</li>
                        <li><strong>Msimu:</strong> Nunua mazao yaliyo katika msimu - ni mabichi na ya bei nafuu.</li>
                    </ul>
                    <p><strong>💡 Kidokezo:</strong> Jenga uhusiano na wakulima kwa ubora thabiti na bei nzuri.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">Kwa Wanunuzi</span></p>
                `
            },
            'smart-buying': {
                titleEn: '💰 Smart Buying Strategies',
                titleSw: '💰 Mikakati ya Kununua kwa Akili',
                contentEn: `
                    <p><strong>Smart buying can save you money and ensure quality.</strong></p>
                    <p>Here are some strategies:</p>
                    <ul>
                        <li><strong>Bulk Buying:</strong> Buy larger quantities at lower prices when possible.</li>
                        <li><strong>Direct Relationships:</strong> Buy directly from farmers to eliminate middlemen.</li>
                        <li><strong>Negotiate Fairly:</strong> Aim for win-win pricing that benefits both buyer and farmer.</li>
                        <li><strong>Seasonal Purchasing:</strong> Buy what's in season for the best prices.</li>
                        <li><strong>Quality Over Quantity:</strong> Sometimes paying more for quality is worth it.</li>
                    </ul>
                    <p><strong>💡 Tip:</strong> Use the Soko Fresh platform to find reliable farmers and compare prices.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">For Buyers</span></p>
                `,
                contentSw: `
                    <p><strong>Kununua kwa akili kunaweza kukuokoa pesa na kuhakikisha ubora.</strong></p>
                    <p>Hapa kuna mikakati:</p>
                    <ul>
                        <li><strong>Kununua kwa Wingi:</strong> Nunua kiasi kikubwa kwa bei ya chini inapowezekana.</li>
                        <li><strong>Uhusiano wa Moja kwa Moja:</strong> Nunua moja kwa moja kutoka kwa wakulima ili kuondoa wapatanishi.</li>
                        <li><strong>Jadiliana Kwa Haki:</strong> Lengo ni bei ya ushindi kwa pande zote mbili.</li>
                        <li><strong>Ununuzi wa Msimu:</strong> Nunua vilivyo katika msimu kwa bei bora.</li>
                        <li><strong>Ubora Dhidi ya Kiasi:</strong> Wakati mwingine kulipa zaidi kwa ubora ni thamani yake.</li>
                    </ul>
                    <p><strong>💡 Kidokezo:</strong> Tumia jukwaa la Soko Fresh kupata wakulima wa kuaminika na kulinganisha bei.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">Kwa Wanunuzi</span></p>
                `
            },
            'post-harvest': {
                titleEn: '📦 Post-Harvest Handling',
                titleSw: '📦 Utunzaji wa Mazao Baada ya Mavuno',
                contentEn: `
                    <p><strong>Proper post-harvest handling reduces food loss and increases profits.</strong></p>
                    <p>Here are key practices:</p>
                    <ul>
                        <li><strong>Proper Storage:</strong> Store produce in clean, ventilated areas with proper temperature.</li>
                        <li><strong>Packaging:</strong> Use appropriate packaging to protect produce during transport.</li>
                        <li><strong>Cooling:</strong> Cool produce quickly after harvest to extend shelf life.</li>
                        <li><strong>Cleaning:</strong> Remove dirt and damaged produce before storage.</li>
                        <li><strong>Transportation:</strong> Handle produce gently and avoid stacking heavy items.</li>
                    </ul>
                    <p><strong>💡 Tip:</strong> Good post-harvest handling can increase profits by up to 30%.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">For Farmers</span></p>
                `,
                contentSw: `
                    <p><strong>Utunzaji sahihi baada ya mavuno hupunguza upotevu wa chakula na kuongeza faida.</strong></p>
                    <p>Hapa kuna mbinu muhimu:</p>
                    <ul>
                        <li><strong>Uhifadhi Sahihi:</strong> Hifadhi mazao katika maeneo safi, yenye uingizaji hewa na halijoto inayofaa.</li>
                        <li><strong>Ufungaji:</strong> Tumia ufungaji unaofaa kulinda mazao wakati wa usafirishaji.</li>
                        <li><strong>Upoaji:</strong> Poa mazao haraka baada ya mavuno ili kuongeza muda wa uhifadhi.</li>
                        <li><strong>Usafishaji:</strong> Ondoa uchafu na mazao yaliyoharibika kabla ya kuhifadhi.</li>
                        <li><strong>Usafirishaji:</strong> Shughulikia mazao kwa upole na epuka kuweka vitu vizito juu.</li>
                    </ul>
                    <p><strong>💡 Kidokezo:</strong> Utunzaji mzuri baada ya mavuno unaweza kuongeza faida hadi 30%.</p>
                    <p style="margin-top:10px;"><span class="modal-tag">Kwa Wakulima</span></p>
                `
            }
        };

        function openTip(id) {
            event.preventDefault();
            const tip = tipData[id];
            if (tip) {
                // Get current language from session via PHP
                const lang = '<?php echo getCurrentLang(); ?>';
                if (lang === 'sw') {
                    document.getElementById('modalTitle').textContent = tip.titleSw;
                    document.getElementById('modalBody').innerHTML = tip.contentSw;
                } else {
                    document.getElementById('modalTitle').textContent = tip.titleEn;
                    document.getElementById('modalBody').innerHTML = tip.contentEn;
                }
                document.getElementById('tipModal').classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeTip() {
            document.getElementById('tipModal').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        document.getElementById('tipModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTip();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTip();
            }
        });
    </script>

</body>
</html>