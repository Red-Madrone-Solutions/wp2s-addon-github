<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Database {
    private $db_version     = '0.5.0';
    private $db_version_key = 'rms_wp2s_gh_db_version';

    private $options_table_name = '';

    public static function instance() {
        static $instance = null;
        if ( $instance == null ) {
            $instance = new static();
        }
        return $instance;
    }

    private function __construct() {
        global $wpdb;
        $this->options_table_name = $wpdb->prefix . 'rms_wp2s_addon_github_options';
    }

    public function update_db() {
        if ( get_option($this->db_version_key) != $this->db_version ) {
            $this->setup_db();
        }
    }

    private function setup_db() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $options_table_sql = <<< EOSQL
CREATE TABLE {$this->options_table_name} (
    `id` MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    `label` VARCHAR(255) NULL,
    PRIMARY KEY (`id`)
) $charset_collate
EOSQL;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta([$options_table_sql]);
        update_option($this->db_version_key, $this->db_version);
    }
}
