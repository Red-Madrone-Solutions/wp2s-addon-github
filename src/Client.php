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

    public function deploySetup() {
        $hash = $this->get_latest_commit_hash();
        if ( is_null($hash) ) {
            return new NullBranch();
        }

        // TODO handle generating unique branch name
        $branch = $this->create_branch($hash, 'rms-wp2s-gh-deploy-branch-30');
        $branch->client($this);
        return $branch;
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
        return $this->branchFromResponse($response);
    }

    public function create_tree($hash, $tree) {
        error_log("create_tree('$hash', tree..)");
        $url = sprintf(
            // https://api.github.com/repos/:owner/:repo/git/trees
            '%s/repos/%s/%s/git/trees',
            $this->api_base,
            $this->account(),
            $this->repo()
        );

        $request_body = [
            'base_tree' => $hash,
            'tree'      => $tree,
        ];

        $request = new Request($this->token(), $url, 'POST');
        $request->body($request_body);

        $response = $request->exec();
        return $response->pluck('sha');
    }

    public function create_commit($tree_hash, $parent_hash) {
        $url = sprintf(
            // https://api.github.com/repos/:owner/:repo/git/commits
            '%s/repos/%s/%s/git/commits',
            $this->api_base,
            $this->account(),
            $this->repo()
        );

        $request_body = [
            'message' => 'Test commit', // TODO replace with actual commit message
            'tree'    => $tree_hash,
            'parents' => [ $parent_hash ],
            // TODO Does it make sense to set the committer to a WP user?
            // TODO Does it make sense to set the author to be for someone in WP? Should we group commits by WP author?
        ];

        $request = new Request($this->token(), $url, 'POST');
        $request->body($request_body);

        $response = $request->exec();
        return $response->pluck('sha');
    }

    public function update_reference($ref, $hash) {
        if ( substr($ref, 0, 5) === 'refs/' ) {
            $ref = substr($ref, 5);
        }

        $url = sprintf(
            // https://api.github.com/repos/:owner/:repo/git/refs/:ref
            '%s/repos/%s/%s/git/refs/%s',
            $this->api_base,
            $this->account(),
            $this->repo(),
            $ref
        );

        $request_body = [ 'sha' => $hash ];

        $request = new Request($this->token(), $url, 'PATCH');
        $request->body($request_body);

        $response = $request->exec();
        return $this->branchFromResponse($response);
    }

    protected function branchFromResponse($response) {
        list($ref, $node_id, $url, $object) = $response->pluckAll(
            ['ref', 'node_id', 'url', 'object']
        );
        if ( $node_id ) {
            $branch = new Branch($node_id, $url, $ref, $object);
            $branch->client($this);
            return $branch;
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
        // error_log("commit: $filename");
    }

    public function create_blob(File $file) : void {
        $url = sprintf(
            // https://api.github.com/repos/:owner/:repo/git/blobs
            '%s/repos/%s/%s/git/blobs',
            $this->api_base,
            $this->account(),
            $this->repo()
        );

        $encoding = 'base64';
        $request_body = [
            'content'  => $file->contents($encoding),
            'encoding' => $encoding,
        ];

        $request = new Request($this->token(), $url, 'POST');
        $request->body($request_body);
        $response = $request->exec();
        unset($request);

        $file->sha($response->pluck('sha'));
    }
}
