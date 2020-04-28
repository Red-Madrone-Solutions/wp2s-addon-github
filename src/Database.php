<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Database {
    private $db_version     = '0.6.0';
    private $db_version_key = 'rms_wp2s_gh_db_version';

    private $option_set = null;

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

            if ( $this->option_set_needs_seeding() ) {
                $this->seed_option_set();
            }
            update_option($this->db_version_key, $this->db_version);
        }
    }

    private function setup_db() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $options_table_sql = <<< EOSQL
CREATE TABLE {$this->options_table_name} (
    `name` VARCHAR(255) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`name`)
) $charset_collate
EOSQL;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta([$options_table_sql]);
    }

    private function optionSet() : OptionSet {
        if ( is_null($this->option_set) ) {
            $this->option_set = new OptionSet();
        }

        return $this->option_set;
    }

    private function option_set_needs_seeding() : bool {
        global $wpdb;

        $query_vals = [];
        $placeholders = [];

        $option_set = $this->optionSet();
        foreach ( $option_set as $option ) {
            $placeholders[]= '%s';
            $query_vals[]= $option->name();
        }

        $prepare_sql = sprintf(
            "SELECT count(*) FROM %s WHERE `name` IN (%s)",
            $this->options_table_name,
            implode(', ', $placeholders)
        );


        $seeded_count = (int) $wpdb->get_var(
            $wpdb->prepare($prepare_sql, $query_vals)
        );

        return $seeded_count !== $option_set->count();
    }

    private function seed_option_set() {
        $option_set = $this->optionSet();

        foreach ($option_set as $option) {
            $this->seed_option($option);
        }
    }

    public function get_option_value($option) : string {
        global $wpdb;

        return (string) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `value` FROM {$this->options_table_name} WHERE `name` = %s",
                $option->name()
            )
        );
    }

    private function seed_option($option) : bool {
        global $wpdb;

        // Check for existing option
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT count(*) FROM {$this->options_table_name} WHERE `name` = %s", $option->name()
            )
        );

        // Seed option if no existing
        if ( $count === 0 ) {
            $result = $wpdb->insert(
                $this->options_table_name,
                [ 'name' => $option->name(), 'value' => $option->default_value() ],
                [ '%s', '%s' ]
            );
            return $result === 1;
        }

        return false;
    }

    public function updateOptions($option_set) {
        foreach ( $option_set->changedOptions() as $option ) {
            $this->update_option($option);
        }
    }

    private function update_option($option) {
        global $wpdb;

        return $wpdb->update(
            $this->options_table_name,
            [ 'value' => $option->value() ], // data
            [ 'name' => $option->name() ], // where
            [ '%s' ], // data format
            [ '%s' ] // where format
        );
    }
}
