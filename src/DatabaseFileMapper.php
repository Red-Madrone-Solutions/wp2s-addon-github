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

    protected function set_details(string $path_hash, array $details) {
        $params = array_merge(
            [
                'path_hash' => $path_hash,
                'namespace' => $this->namespace,
            ],
            $details
        );

        $params = new Params($params);
        return Database::instance()->setFileDetails($params);
    }
}

