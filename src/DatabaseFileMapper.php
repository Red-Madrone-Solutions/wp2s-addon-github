<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class DatabaseFileMapper extends FileMapper {
    protected function load_details(string $path_hash) {
        return Database::instance()->getFileDetails(
            $path_hash,
            $this->$namespace
        );
    }
}

