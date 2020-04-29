<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Branch {
    private $node_id;
    private $url;
    private $ref;
    private $name = null;

    public function __construct($node_id, $url, $ref) {
        $this->node_id = $node_id;
        $this->url     = $url;
        $this->ref     = $ref;
    }

    public function node_id() : string {
        return $this->node_id;
    }

    public function url() : string {
        return $this->url;
    }

    public function name() : string {
        if ( is_null($this->name) ) {
            $idx = strrpos($this->ref, '/');
            if ( $idx !== false ) {
                $this->name = substr($this->ref, $idx + 1);
            }
        }
        return $this->name;
    }

    public function is_valid() : bool {
        return $this->node_id !== '';
    }
}
