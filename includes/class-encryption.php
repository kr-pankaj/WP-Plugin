<?php
if (!defined('ABSPATH')) exit;

class AI_Admin_Boost_Encryption {
    private static $encryption_key;

    private static function get_encryption_key() {
        if (!self::$encryption_key) {
            $key_option = 'ai_admin_boost_encryption_key';
            self::$encryption_key = get_option($key_option);
            if (!self::$encryption_key) {
                self::$encryption_key = bin2hex(random_bytes(32)); // 256-bit key
                update_option($key_option, self::$encryption_key);
            }
        }
        return hex2bin(self::$encryption_key); // Use raw binary key
    }

    public static function encrypt($data) {
        if (empty($data)) return '';
        $key = self::get_encryption_key();
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public static function decrypt($data) {
        if (empty($data)) return '';
        $key = self::get_encryption_key();
        $decoded = base64_decode($data);
        if (!$decoded || !str_contains($decoded, '::')) return '';
        list($encrypted_data, $iv) = explode('::', $decoded, 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    }

    public static function generate_key_on_activation() {
        $key_option = 'ai_admin_boost_encryption_key';
        if (!get_option($key_option)) {
            $key = bin2hex(random_bytes(32));
            update_option($key_option, $key);
        }
    }
}
