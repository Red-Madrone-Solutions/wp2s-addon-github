<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

interface ClientInterface {
    public function deploySetup();
    public function create_blob(File $file);
    public function create_tree(string $hash, array $tree_values);
    public function create_commit($tree_hash, $parent_hash);
    public function update_reference($ref, $hash) : Branch;
    public function create_pull_request(Branch $source_branch) : PullRequest;
    public function merge_pull_request(PullRequest $pr) : bool;
    public function delete_branch(Branch $branch);
}
