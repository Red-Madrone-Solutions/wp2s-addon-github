<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Client {
    private $option_set;
    private $account = null;
    private $repo = null;

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

    private function account() : string {
        if ( is_null($this->account) ) {
            if ( $account_option = $this->option_set->findByName('account') ) {
                $this->account =$account_option->value();
            } else {
                throw new \Exception('Cannot find account');
            }
        }
        return $this->account;
    }

    private function repo() : string {
        if ( is_null($this->repo) ) {
            if ( $repo_option = $this->option_set->findByName('repository') ) {
                $this->repo =$repo_option->value();
            } else {
                throw new \Exception('Cannot find repo');
            }
        }
        return $this->repo;
    }
}
