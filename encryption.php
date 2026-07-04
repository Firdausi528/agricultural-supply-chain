<?php
/**
 * Encryption Helper Class
 * Handles encryption and decryption of sensitive data
 */

if (!class_exists('Encryption')) {

class Encryption {
    private static $key;
    private static $cipher = 'AES-256-CBC';

    private static function getKey() {
        if (self::$key === null) {
            $key = 'soko_fresh_encryption_key_2024_secure_32';
            self::$key = hash('sha256', $key, true);
        }
        return self::$key;
    }

    private static function getIV() {
        $iv_length = openssl_cipher_iv_length(self::$cipher);
        return openssl_random_pseudo_bytes($iv_length);
    }

    public static function encrypt($data) {
        if ($data === null || $data === '') {
            return $data;
        }

        $iv = self::getIV();
        $encrypted = openssl_encrypt(
            $data,
            self::$cipher,
            self::getKey(),
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            return false;
        }

        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($data) {
        if ($data === null || $data === '') {
            return $data;
        }

        try {
            $decoded = base64_decode($data);
            if ($decoded === false) {
                return $data;
            }

            $iv_length = openssl_cipher_iv_length(self::$cipher);
            
            if (strlen($decoded) < $iv_length) {
                return $data;
            }
            
            $iv = substr($decoded, 0, $iv_length);
            $encrypted = substr($decoded, $iv_length);

            $decrypted = openssl_decrypt(
                $encrypted,
                self::$cipher,
                self::getKey(),
                OPENSSL_RAW_DATA,
                $iv
            );

            return $decrypted !== false ? $decrypted : $data;
        } catch (Exception $e) {
            return $data;
        }
    }
}

}
?>