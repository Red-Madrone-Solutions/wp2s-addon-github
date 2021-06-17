<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Response {
    private $headers;
    private $body;
    private $body_json;
    private $status_code = null;
    private $curl_handle = null;

    public static function setup() {
    }

    public function __construct($curl_handle = null) {
        $this->body = '';
        $this->body_json = '';
        $this->headers = [];
        $this->curl_handle = $curl_handle;
    }

    public function body($value = null) : string {
        if ( !is_null($value) ) {
            if ( $this->is_gzipped() ) {
                $value = gzdecode($value);
            } else if ( $this->is_deflated() ) {
                $value = gzinflate($value);
            }
            $this->body = $value;
            $this->body_json = json_decode($value, $assoc = true);
            if ( !is_null($this->curl_handle) ) {
                $this->status_code = (int) curl_getinfo($this->curl_handle, CURLINFO_RESPONSE_CODE);
            }
            Log::debug2('Response: %s', $this->simpleBody());
            Log::debug2('Status Code: %s', $this->status_code);
        }

        return $this->body;
    }

    private function simpleBody() {
        $simple_body = $this->body_json;

        // Gracefully handle empty JSON response
        if ( is_null($simple_body) ) {
            return [];
        }

        $wanted_keys = [
            # Common Keys
            'url', 'node_id',

            # PR keys
            'id', 'diff_url', 'issue_url', 'state', 'number', 'title', 'body',
            'html_url', 'tree', 'message',

            # Branch keys
            'ref', 'object', 'sha', 'merged'
        ];

        foreach ( array_keys($simple_body) as $key ) {
            if ( !in_array($key, $wanted_keys) ) {
                unset($simple_body[$key]);
            }
        }

        return $simple_body;
    }

    private function is_gzipped() {
        return $this->is_encoded_as('gzip');
    }

    private function is_deflated() {
        return $this->is_encoded_as('deflate');
    }

    private function is_encoded_as($encoding) {
        if ( isset($this->headers['content-encoding']) ) {
           return in_array($encoding, $this->headers['content-encoding']);
        }
        return false;
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

    public function find($key, $value) {
        foreach( $this->body_json as $entry ) {
            if ( isset($entry[$key]) && $entry[$key] === $value ) {
                return $entry;
            }
        }
        return null;
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

    public function header($name) : string {
        return isset($this->headers[$name]) ? implode(',', $this->headers[$name]) : '';
    }

    public function status_code() : int {
        if ( is_null($this->status_code) ) {
            Log::debug2('Raw status: "' . $this->header('status') . '"');
            $this->status_code = (int) $this->header('status');
        }
        Log::debug2('Response status code: ' . $this->status_code);
        return $this->status_code;
    }

    public function is_success() : bool {
        return $this->status_code() >= 200 && $this->status_code() < 300;
    }

    public function is_error() : bool {
        return $this->status_code() >= 400;
    }

    public function headers() : array {
        return $this->headers;
    }
}
