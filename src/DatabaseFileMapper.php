<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class DatabaseFileMapper extends FileMapper {
    private $database = null;

    public function __construct($database) {
        $this->database = $database;
        parent::__construct();
    }

    protected function load_details(string $path_hash) {
        return Database::instance()->getFileDetails(
            $path_hash,
            $this->$namespace
        );
    }
}

