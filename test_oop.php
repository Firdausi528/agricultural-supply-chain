<?php
// Include files
include 'classes/Database.php';
include 'classes/User.php';
include 'classes/Farmer.php';
include 'config/encryption.php';

// Create database connection
$db = new Database();

// Test Farmer class
$farmer = new Farmer($db);

// Load farmer with ID 1 (change to your actual farmer ID)
$farmer->load(1);

// Get dashboard data
$dashboard = $farmer->getDashboard();

echo "<h2>Farmer Dashboard Data (OOP Test)</h2>";
echo "<pre>";
print_r($dashboard);
echo "</pre>";

echo "<h3>Farmer Name: " . $farmer->getFullName() . "</h3>";
echo "<h3>User Type: " . $farmer->getUserType() . "</h3>";
?>