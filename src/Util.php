<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Util {
    const HASH_ALGO = 'sha256';
    const ENCRYPT_METHOD = 'aes-256-ctr';

    public static function random_bytes($length = 32) {
        if ( function_exists('random_bytes') ) {
            return random_bytes($length);
        }

        if ( function_exists('openssl_random_pseudo_bytes') ) {
            return openssl_random_pseudo_bytes($length);
        }

        // TODO warn about unsafe key generation
        $return = '';
        for ( $i=0; $i<$length; $i++ ) {
            $return .= chr(mt_rand(0, 255));
        }
        return $return;
    }

    public static function encrypt($message, $key, $salt, $encode = false) {
        list($enc_key, $auth_key) = self::splitKeys($key, $salt);

        $iv_size = openssl_cipher_iv_length(self::ENCRYPT_METHOD);
        $iv = self::random_bytes($iv_size);

        $cipher_text = $iv . openssl_encrypt($message, self::ENCRYPT_METHOD, $enc_key, OPENSSL_RAW_DATA, $iv);

        $mac = self::hash($cipher_text, $auth_key, $salt);
        $return = $mac . $cipher_text;
        return $encode ? base64_encode($return) : $return;
    }

    public static function decrypt($message, $key, $salt, $encoded = false) {
        list($enc_key, $auth_key) = self::splitKeys($key, $salt);

        if ( $encoded ) {
            $message = base64_decode($message, true);
            if ( $message === false ) {
                throw new \Exception('Decryption failure');
            }
        }

        $hash_size = mb_strlen(self::hash('', $auth_key, $salt), '8bit');
        $msg_mac = mb_substr($message, 0, $hash_size, '8bit');
        $iv_and_cipher_text = mb_substr($message, $hash_size, null, '8bit');

        $calc_mac = self::hash($iv_and_cipher_text, $auth_key, $salt);

        if ( !self::hashEquals($msg_mac, $calc_mac) ) {
            throw new \Exception('Decryption failure');
        }

        $iv_size = openssl_cipher_iv_length(self::ENCRYPT_METHOD);
        $iv = mb_substr($iv_and_cipher_text, 0, $iv_size, '8bit');
        $cipher_text = mb_substr($iv_and_cipher_text, $iv_size, null, '8bit');

        return openssl_decrypt($cipher_text, self::ENCRYPT_METHOD, $enc_key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Compare two strings without leaking timing information
     */
    protected static function hashEquals($a, $b) {
        if ( function_exists('hash_equals') ) {
            return hash_equals($a, $b);
        }

        $compare_key = self::random_bytes(32);
        return self::hash($a, $compare_key) === self::hash($b, $compare_key);
    }

    protected static function hash($message, $key, $salt = '') {
        if ( function_exists('hash_hkdf') ) {
            return hash_hkdf(self::HASH_ALGO, $key, 32, $message, $salt);
        }

        return hash_hmac(self::HASH_ALGO, $message, $key, true);
    }

    protected static function splitKeys($key, $salt) {
        return [
            self::hash('ENCRYPTION', $key, $salt),
            self::hash('AUTHENTICATION', $key, $salt),
        ];
    }

}
