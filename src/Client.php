<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Client {
    private $option_set;
    private $api_base = 'https://api.github.com';
    private $account = null;
    private $repo = null;

    public function __construct($option_set) {
        $this->option_set = $option_set;
    }

    public function canAccess() : bool {
        $sha = $this->get_latest_commit_hash();
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

    protected function get_latest_commit_hash() : string {
        $url = sprintf(
            // https://api.github.com/repos/<AUTHOR>/<REPO>/git/refs/heads
            '%s/repos/%s/%s/git/refs/heads',
            $this->api_base,
            $this->account(),
            $this->repo()
        );
        $request = new Request($this->token(), $url);
        $response = $request->exec();

        return $response->pluck([0, 'object', 'sha']);
    }
}
