<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

abstract class FileMapper {
    protected $namespace = 'GitHub';

    protected $map;

    public function __construct() {
        $this->map = [];
    }

    public function get(
        string $path_hash,
        string $key = null,
        string $default = null
    ) {
        $details = $this->get_details($path_hash);
        if ( !$key ) {
            return $details;
        }

        if ( isset($details[$key]) ) {
            return $details[$key];
        }

        return $default;
    }

    abstract protected function load_details(string $path_hash);

    protected function get_details(string $path_hash) {
        $map_key = $this->build_map_key($path_hash);

        if ( !isset($this->map[$map_key]) ) {
            $this->map[$map_key] = $this->load_details($path_hash, $this->namespace);
        }

        return $this->map[$map_key];
    }

    private function build_map_key(string $path_hash) {
        return $this->namespace . '-' . $path_hash;
    }

    public function set(
        string $path_hash,
        array $new_details
    ) {

        // Gets details and primes map
        $existing_details = $this->get_details($path_hash);

        // Save new details
        $updated_details = array_merge($existing_details, $new_details);
        $this->set_details($path_hash, $updated_details);

        // Update map
        $map_key             = $this->build_map_key($path_hash);
        $this->map[$map_key] = $updated_details;
    }

    abstract protected function set_details(
        string $path_hash,
        array $params
    );
}
