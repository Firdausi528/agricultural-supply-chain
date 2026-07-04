<?php
// Language Helper

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// If language is changed via GET
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'sw'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Load language file
$lang_file = __DIR__ . '/../languages/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include __DIR__ . '/../languages/en.php';
}

// Helper function to get translation
function t($key) {
    global $lang;
    return isset($lang[$key]) ? $lang[$key] : $key;
}

// Helper function to get current language
function getCurrentLang() {
    return isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
}

// Helper function to switch language
function switchLang($lang) {
    if (in_array($lang, ['en', 'sw'])) {
        $_SESSION['lang'] = $lang;
    }
}
?>