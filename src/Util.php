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

        if ( $encoded ) {
            $message = base64_decode($message, true);
            if ( $message === false ) {
                throw new Exception('Encryption failure');
            }
        }
    }

    protected static function hash($message, $key, $salt) {
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
