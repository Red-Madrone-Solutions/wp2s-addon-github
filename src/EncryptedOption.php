<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class EncryptedOption extends Option {
    private static function encryption_key_file() {
        $bits = wp_get_upload_dir();
        return $bits['basedir'] . '/.rms-wp2s-gh-enc-key';
    }

    private static function hash_salt_file() {
        $bits = wp_get_upload_dir();
        return $bits['basedir'] . '/.rms-wp2s-gh-hash-salt';
    }

    public static function setup($overwrite = false) {
        $key_file = self::encryption_key_file();
        if ( !file_exists($key_file) || $overwrite ) {
            $key = Util::random_bytes(32);
            file_put_contents($outfile, base64_encode($key));
        }

        $salt_file = self::hash_salt_file();
        if ( !file_exists($salt_file) || $overwrite ) {
            $salt = Util::random_bytes(16);
            file_put_contents($outfile, base64_encode($salt));
        }
    }

    public function type() {
        return 'password';
    }
}
