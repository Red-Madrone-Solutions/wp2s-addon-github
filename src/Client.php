<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class Client {
    private $option_set;
    private $api_base = 'https://api.github.com';
    private $account  = null;
    private $repo     = null;
    private $request_class;

    /**
     * __construct
     *
     * @param OptionSet $option_set
     */
    public function __construct(
        OptionSet $option_set,
        string $request_class = null
    ) {
        $this->option_set    = $option_set;
        $this->request_class = $request_class ?: 'Request';
    }

    public function canAccess() : bool {
        $hash = $this->get_latest_commit_hash();
        if ( is_null($hash) ) {
            Log::warn("Didn't get good hash for latest commit");
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

    /**
     * Setup to deploy site
     *
     * @since 1.0
     *
     * @uses self::get_latest_commit_hash()
     * @uses self::create_branch()
     *
     * @return Branch $branch branch created for deploying to
     */
    public function deploySetup() {
        $hash = $this->get_latest_commit_hash();
        if ( is_null($hash) ) {
            return new NullBranch();
        }

        // TODO Allow user to change name of deploy branch
        $branch = $this->create_branch($hash, 'rms-wp2s-gh-deploy-branch-' . time());
        $branch->client($this);
        return $branch;
    }

    private function token() {
        // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found
        if ( $token_option = $this->option_set->findByName('personal_access_token') ) {
            try {
                $clear_token = $token_option->value($decrypt = true);
            } catch (DecryptionErrorException $e) {
                throw new TokenException('Unable to decrypt token', $e->getCode(), $e);
            }
            return $clear_token;
        }
        throw new TokenException('Cannot find token');
    }

    private function account() : string {
        if ( is_null($this->account) ) {
            // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found
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
            // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found
            if ( $repo_option = $this->option_set->findByName('repository') ) {
                $this->repo =$repo_option->value();
            } else {
                throw new \Exception('Cannot find repo');
            }
        }
        return $this->repo;
    }

    /**
     * Get the latest commit hash for the base repo
     *
     * @since 1.0
     *
     * @uses Request::exec()
     * @uses Util::pluck()
     *
     * @return string $sha
     */
    protected function get_latest_commit_hash() {
        // TODO use GraphQL (v4) instead of v3 query
        $url = sprintf(
            // https://api.github.com/repos/<AUTHOR>/<REPO>/git/refs/heads
            '%s/repos/%s/%s/git/refs/heads',
            $this->api_base,
            $this->account(),
            $this->repo()
        );

        $request  = new $this->request_class($this->token(), $url);
        $response = $request->exec();

        $entry = $response->find('ref', sprintf('refs/heads/%s', $this->source_branch()));
        if ( is_null($entry) ) {
            Log::warn(
                sprintf('Cannot find branch: ', $this->source_branch())
            );
            return null;
        }
        return Util::pluck($entry, ['object', 'sha']);
    }

    public function source_branch() {
        return $this->option_value('source_branch')
            ?: apply_filters('rms/wp2s/github/default-source-branch', 'master');
    }

    public function target_branch() {
        return $this->option_value('target_branch')
            ?: apply_filters('rms/wp2s/github/default-target-branch', 'master');
    }

    /**
     * Create a new branch in GitHub
     *
     * @since 1.0
     *
     * @param string $hash Commit hash to base the new branch on
     * @param string $name Name of the new aranch
     *
     * @return Branch $branch
     */
    protected function create_branch(string $hash, string $name) {
        $url = sprintf(
            // https://api.github.com/repos/<AUTHOR>/<REPO>/git/refs
            '%s/repos/%s/%s/git/refs',
            $this->api_base,
            $this->account(),
            $this->repo()
        );

        $request_body = [
            'ref' => "refs/heads/$name",
            'sha' => $hash,
        ];

        $request = new $this->request_class($this->token(), $url, 'POST');
        $request->body($request_body);

        $response = $request->exec();
        return $this->branchFromResponse($response);
    }

    public function create_tree($hash, $tree) {
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

        $request = new $this->request_class($this->token(), $url, 'POST');
        $request->body($request_body);

        $response = $request->exec();
        if ( !$response->is_success() ) {
            throw new DeployException('Error creating tree: ' . $response->pluck('message'));
        }

        return $response->pluck('sha');
    }

    public function commit_message() {
        return $this->option_value('commit_message')
            ?: apply_filters('rms/wp2s/github/default-commit-message', 'WP2Static commit');
    }

    private function option_value($name) {
        // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found
        if ( $option = $this->option_set->findByName($name) ) {
            return $option->value();
        }
        return null;
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
            'message' => $this->commit_message(),
            'tree'    => $tree_hash,
            'parents' => [ $parent_hash ],
            // TODO Does it make sense to set the committer to a WP user?
            // TODO Does it make sense to set the author to be for someone in WP? Should we group commits by WP author?
        ];

        $request = new $this->request_class($this->token(), $url, 'POST');
        $request->body($request_body);

        $response = $request->exec();
        return $response->pluck('sha');
    }

    public function update_reference($ref, $hash) : Branch {
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

        $request = new $this->request_class($this->token(), $url, 'PATCH');
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

    public function delete_branch(Branch $branch) {
        $url = sprintf(
            // https://api.github.com/repos/:owner/:repo/git/refs/:ref
            '%s/repos/%s/%s/git/refs/heads/%s',
            $this->api_base,
            $this->account(),
            $this->repo(),
            $branch->name()
        );

        $request = new $this->request_class($this->token(), $url, 'DELETE');
        $request->exec();
        unset($request);
    }

    public function commit($filename) {
        Log::stub("commit: $filename");
    }

    public function create_blob(File $file) {
        // Don't try to create blob twice on same file
        if ( $file->blob_exists() ) {
            return;
        }

        Log::l('Creating blob: ' . $file->commit_path());
        $url = sprintf(
            // https://api.github.com/repos/:owner/:repo/git/blobs
            '%s/repos/%s/%s/git/blobs',
            $this->api_base,
            $this->account(),
            $this->repo()
        );

        $encoding     = 'base64';
        $request_body = [
            'content'  => $file->contents($encoding),
            'encoding' => $encoding,
        ];

        $request = new $this->request_class($this->token(), $url, 'POST');
        $request->body($request_body);
        $response = $request->exec();
        unset($request);

        $file->stored($response->pluck('sha'));
    }

    public function create_pull_request(Branch $source_branch) : PullRequest {
        $url = sprintf(
            // https://api.github.com/repos/:owner/:repo/pulls
            '%s/repos/%s/%s/pulls',
            $this->api_base,
            $this->account(),
            $this->repo()
        );

        $request_body = [
            'title' => $this->pr_title(),
            'head'  => $source_branch->name(),
            'base'  => $this->target_branch(),
            'body'  => $this->pr_body(),
        ];

        $request = new $this->request_class($this->token(), $url, 'POST');
        $request->body($request_body);
        $response = $request->exec();
        unset($request);

        $pull_number = (int) $response->pluck('number');
        return new PullRequest($pull_number, $this);
    }

    private function should_auto_merge_pr() : bool {
        return (bool) $this->option_value('merge_pr');
    }

    public function pr_title() : string {
        return $this->option_value('pr_title')
            ?: apply_filters('rms/wp2s/github/default-pr-title', 'WP2Static PR');
    }

    public function pr_body() : string {
        return $this->option_value('pr_body')
            ?: apply_filters('rms/wp2s/github/default-pr-body', '');
    }

    public function pr_merge_title() : string {
        return $this->option_value('pr_merge_title')
            ?: apply_filters('rms/wp2s/github/default-pr-merge-title', 'WP2Static Auto-merge PR');
    }

    public function pr_merge_body() : string {
        return $this->option_value('pr_merge_body')
            ?: apply_filters('rms/wp2s/github/default-pr-merge-body', '');
    }

    public function merge_pull_request(PullRequest $pr) : bool {
        if ( !$this->should_auto_merge_pr() ) {
            Log::l('Skipping auto-merge of PR per option setting');
            return false;
        }

        $url = sprintf(
            // https://api.github.com/repos/:owner/:repo/pulls/:pull_number/merge
            '%s/repos/%s/%s/pulls/%s/merge',
            $this->api_base,
            $this->account(),
            $this->repo(),
            $pr->pull_number()
        );

        $request_body = [
            'commit_title'   => $this->pr_merge_title(),
            'commit_message' => $this->pr_merge_body(),
        ];

        $request = new $this->request_class($this->token(), $url, 'PUT');
        $request->body($request_body);
        $response = $request->exec();
        unset($request);

        if ( !$response->is_success() ) {
            Log::warn('PR was not merged: ' . $response->pluck('message'));
            return false;
        }

        return true;
    }
}
