<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class Params {
    private $params;
    private $missing_keys;
    private $default_params;

    public function __construct($params = []) {
        $this->params         = $params;
        $this->missing_keys   = [];
        $this->default_params = [];
    }

    public function is_valid(array $required_keys) : bool {
        foreach ( $required_keys as $key ) {
            if ( !isset($this->params[$key]) || empty($this->params[$key]) ) {
                $this->missing_keys[]= $key; // phpcs:ignore
            }
        }

        return count($this->missing_keys) > 0 ? false : true;
    }

    public function missing_keys() : array {
        return $this->missing_keys;
    }

    public function set_default(array $default_params = []) {
        $this->default_params = $default_params;
    }

    public function get($key, $default = '') {
        if ( isset($this->params[$key]) ) {
            return $this->params[$key];
        }

        if ( isset($this->default_params[$key]) ) {
            return $this->default_params[$key];
        }

        return $default;
    }

}
