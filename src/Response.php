<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Response {
    private $headers;
    private $body;
    private $body_json;

    public function __construct() {
        $this->body = '';
        $this->body_json = '';
        $this->headers = [];
    }

    public function body($value = null) : string {
        if ( !is_null($value) ) {
            $this->body = $value;
            $this->body_json = json_decode($value, $assoc = true);
            error_log("response body: " . print_r($this->body_json, 1));
        }

        return $this->body;
    }

    public function pluckAll($keys) {
        return array_map( function($key) {
            return $this->pluck($key);
        }, $keys );
    }

    public function pluck($keys) {
        // Allow passing single arg for convenience
        if ( !is_array($keys) ) {
            $keys = [ $keys ];
        }
        return Util::pluck($this->body_json, $keys);
    }

    public function collect_headers($curl, $header) : int {
        $len = strlen($header);
        $parts = explode(':', $header, 2);
        if ( count($parts) < 2 ) {
            return $len; // ignore invalid headers
        }

        $this->headers[strtolower(trim($parts[0]))][]= trim($parts[1]);

        return $len;
    }

    public function headers() : array {
        return $this->headers;
    }
}
