<?php
if (!defined('ABSPATH')) exit;

class AI_Admin_Boost_Encryption {
    private static $encryption_key;

    // Get or generate the encryption key from the database
    private static function get_encryption_key() {
        if (!self::$encryption_key) {
            $key_option = 'ai_admin_boost_encryption_key';
            self::$encryption_key = get_option($key_option);
            if (!self::$encryption_key) {
                // Generate a 256-bit (32-byte) key if it doesn’t exist
                self::$encryption_key = bin2hex(random_bytes(32));
                update_option($key_option, self::$encryption_key);
            }
        }
        return self::$encryption_key;
    }

    // Encrypt the API key
    public static function encrypt($data) {
        if (empty($data)) return '';
        $key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    // Decrypt the API key
    public static function decrypt($data) {
        if (empty($data)) return '';
        $key = self::get_encryption_key();
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    }

    // Optional: Generate key on activation (called from main plugin file)
    public static function generate_key_on_activation() {
        $key_option = 'ai_admin_boost_encryption_key';
        if (!get_option($key_option)) {
            $key = bin2hex(random_bytes(32));
            update_option($key_option, $key);
        }
    }
}