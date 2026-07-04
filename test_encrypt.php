<?php
require_once 'config/encryption.php';

// Test encryption and decryption
$test_data = "Tomatoes";
echo "Original: " . $test_data . "<br>";

$encrypted = Encryption::encrypt($test_data);
echo "Encrypted: " . $encrypted . "<br>";

$decrypted = Encryption::decrypt($encrypted);
echo "Decrypted: " . $decrypted . "<br>";

if ($decrypted === $test_data) {
    echo "✅ Encryption and decryption work!<br>";
} else {
    echo "❌ Encryption and decryption failed!<br>";
}

// Test with a longer string
$test_data2 = "This is a test crop name with spaces";
echo "<br>Original 2: " . $test_data2 . "<br>";
$encrypted2 = Encryption::encrypt($test_data2);
echo "Encrypted 2: " . $encrypted2 . "<br>";
$decrypted2 = Encryption::decrypt($encrypted2);
echo "Decrypted 2: " . $decrypted2 . "<br>";

// Check if decryption returns the same
echo "<br>✅ Test completed!";
?>