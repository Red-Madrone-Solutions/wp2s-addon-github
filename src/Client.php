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
        $hash = $this->get_latest_commit_hash();
        if ( is_null($hash) ) {
            return false;
        }

        $branch = $this->create_branch($hash, 'rms-wp2s-gh-test-branch');
        if ( !$branch->is_valid() ) {
            return false;
        }

        // cleanup
        $this->delete_branch($branch);
        return true;
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

    protected function get_latest_commit_hash() {
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

    protected function create_branch($hash, $name) {
        $url = sprintf(
            // https://api.github.com/repos/<AUTHOR>/<REPO>/git/refs
            '%s/repos/%s/%s/git/refs',
            $this->api_base,
            $this->account(),
            $this->repo()
        );

        $request_body = [
            "ref" => "refs/heads/$name",
            "sha" => $hash,
        ];

        $request = new Request($this->token(), $url, 'POST');
        $request->body($request_body);

        $response = $request->exec();

        list($ref, $node_id, $url) = $response->pluckAll(['ref', 'node_id', 'url']);
        if ( $node_id ) {
            return new Branch($node_id, $url, $ref);
        }
        return new NullBranch();
    }

    protected function delete_branch($branch) {
        $url = sprintf(
            // https://api.github.com/repos/:owner/:repo/git/refs/:ref
            '%s/repos/%s/%s/git/refs/heads/%s',
            $this->api_base,
            $this->account(),
            $this->repo(),
            $branch->name()
        );

        $request = new Request($this->token(), $url, 'DELETE');
        $request->exec();
        unset($request);
    }

    public function commit($filename) {
        error_log("commit: $filename");
    }
}
