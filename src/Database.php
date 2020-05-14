<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Database {
    private $db_version     = '0.7.0';
    private $db_version_key = 'rms_wp2s_gh_db_version';

    private $option_set = null;

    private $options_table_name      = '';
    private $meta_table_name         = '';
    private $deploy_cache_table_name = '';

    public static function instance() {
        static $instance = null;
        if ( $instance == null ) {
            $instance = new static();
        }
        return $instance;
    }

    public static function teardown() {
        $self = self::instance();
        $self->teardown_db();
    }

    private function teardown_db() {
        global $wpdb;

        $sql = "DROP TABLE IF EXISTS {$this->options_table_name}";
        $wpdb->query($sql);

        $sql = "DROP TABLE IF EXISTS {$this->meta_table_name}";
        $wpdb->query($sql);

        delete_option($this->db_version_key);
    }

    private function __construct() {
        global $wpdb;
        $this->options_table_name      = $wpdb->prefix . 'rms_wp2s_addon_github_options';
        $this->deploy_cache_table_name = $wpdb->prefix . 'wp2static_deploy_cache';
        $this->meta_table_name         = $wpdb->prefix . 'rms_wp2s_addon_github_deploymeta';
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

        $meta_table_sql = <<< EOSQL
CREATE TABLE {$this->meta_table_name} (
    `path_hash` CHAR(32) NOT NULL,
    `namespace` VARCHAR(128) NOT NULL,
    `meta_name` VARCHAR(128) NOT NULL,
    `meta_value` VARCHAR(1024),
    PRIMARY KEY (`path_hash`, `namespace`, `meta_name`)
) $charset_collate
EOSQL;
        error_log("meta_table_sql:\n$meta_table_sql");

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta([$options_table_sql, $meta_table_sql]);
    }

    private function optionSet() : OptionSet {
        if ( is_null($this->option_set) ) {
            $this->option_set = new OptionSet();
        }

        return $this->option_set;
    }

    private function option_set_needs_seeding() : bool {
        global $wpdb;

        $query_vals   = [];
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

    public function upsertMetaInfo(
        string $path_hash,
        string $namespace,
        array  $meta
    ) : void {
        global $wpdb;

        $sql_template = <<< EOSQL
INSERT INTO {$this->meta_table_name}
    (path_hash, namespace, meta_name, meta_value)
VALUES
    (%s, %s, %s, %s)
ON DUPLICATE KEY UPDATE
    path_hash = %s,
    namespace = %s,
    meta_name = %s
EOSQL;

        foreach ( $meta as $name => $value ) {
            $sql = $wpdb->prepare(
                $sql_template,

                // Insert values
                $path_hash,
                $namespace,
                $name,
                $value,

                // Duplicte key values
                $path_hash,
                $namespace,
                $meta_name
            );
            $wpdb->query($sql);
        }
    }

    public function getMetaValue(
        string $path_hash,
        string $namespace,
        string $meta_name
    ) : string {
        global $wpdb;

        $sql = <<< EOSQL
SELECT meta_value
FROM {$this->meta_table_name}
WHERE path_hash = %s AND namespace = %s AND meta_name = %s
LIMIT 1
EOSQL;
        $sql = $wpdb->prepare($sql, $path_hash, $namespace, $meta_name);
        return $wpdb->get_var($sql);
    }

    public function truncateAndSeedDeployCache(string $from_namespace, string $to_namespace) {
        global $wpdb;

        // Clear entries from target namespace
        $wpdb->delete(
            $this->deploy_cache_table_name,
            [ 'namespace' => $to_namespace ]
        );

        // Populate target from source
        $sql = <<< EOSQL
INSERT
    INTO {$this->deploy_cache_table_name}
    (`path_hash`, `path`, `file_hash`, `namespace`)
SELECT path_hash, path, file_hash, %s AS namespace
FROM {$this->deploy_cache_table_name}
WHERE namespace = %s
EOSQL;

        $sql = $wpdb->prepare($sql, $to_namespace, $from_namespace);
        $wpdb->query($sql);
    }
}
