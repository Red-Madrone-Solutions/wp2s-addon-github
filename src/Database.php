<?php

namespace RMS\WP2S\GitHub;

class Database {
    protected function options_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'rms_wp2s_addon_github_options';
    }



}
