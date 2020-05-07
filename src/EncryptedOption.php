<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class EncryptedOption extends Option {
    // Don't detect private files
    public static function setup() {
        add_filter('wp2static_filenames_to_ignore', function($filenames_to_ignore) {
            return array_merge(
                $filenames_to_ignore,
                [
                    self::encryption_key_file(),
                    self::hash_salt_file()
                ]
            );
        });
    }

    private static function encryption_key_file() {
        $bits = wp_get_upload_dir();
        return $bits['basedir'] . '/.rms-wp2s-gh-enc-key';
    }

    private static function key() {
        $key_file = self::encryption_key_file();
        if ( !file_exists($key_file) ) {
            self::create_encryption_key_file();
        }

        return base64_decode(
            file_get_contents($key_file)
        );
    }

    private static function hash_salt_file() {
        $bits = wp_get_upload_dir();
        return $bits['basedir'] . '/.rms-wp2s-gh-hash-salt';
    }

    private static function salt() {
        $salt_file = self::hash_salt_file();
        if ( !file_exists($salt_file) ) {
            self::create_hash_salt_file();
        }

        return base64_decode(
            file_get_contents($salt_file)
        );
    }

    public static function activate($overwrite = false) {
        self::create_encryption_key_file($overwrite);
        self::create_hash_salt_file($overwrite);
    }

    private static final function create_encryption_key_file($overwrite = false) {
        $key_file = self::encryption_key_file();
        if ( !file_exists($key_file) || $overwrite ) {
            $key = Util::random_bytes(32);
            file_put_contents($key_file, base64_encode($key));
            chmod($key_file, 0400);
        }
    }

    private static final function create_hash_salt_file($overwrite = false) {
        $salt_file = self::hash_salt_file();
        if ( !file_exists($salt_file) || $overwrite ) {
            $salt = Util::random_bytes(16);
            file_put_contents($salt_file, base64_encode($salt));
            chmod($salt_file, 0400);
        }
    }

    public static function teardown() {
        unlink( self::encryption_key_file() );
        unlink( self::hash_salt_file() );
    }

    public function type() {
        return 'password';
    }

    public function update($value) {
        if ( $value !== '' ) {
            $sanitized_value = $this->sanitize($value);
            // Always update encrypted values
            $this->value = Util::encrypt($sanitized_value, self::key(), self::salt(), $encode = true);
            $this->value_changed = true;
        }
    }

    public function ui_value() {
        return '';
    }

    public function value($decrypt = false) {
        return $decrypt ? Util::decrypt($this->value, self::key(), self::salt(), $encoded = true) : $this->value;
    }
}
