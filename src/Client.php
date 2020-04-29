<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Client {
    private $option_set;

    public function __construct($option_set) {
        $this->option_set = $option_set;
    }

    public function canAccess() : bool {
        return false;
    }

    private function token() {
        if ( $token_option = $this->option_set->findByName('personal_access_token') ) {
            return $token_option->value($decrypt = true);
        }
        throw new \Exception('Cannot find token');
    }
}
