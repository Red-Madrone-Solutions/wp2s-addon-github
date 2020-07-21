<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class Branch {
    private $node_id;
    private $url;
    private $ref;
    private $name = null;
    private $object;
    private $hash = null;
    private $files;
    private $client = null;
    private $ancestors;
    private $merged = false;

    public function __construct($node_id, $url, $ref, $object = []) {
        $this->node_id   = $node_id;
        $this->url       = $url;
        $this->ref       = $ref;
        $this->object    = $object;
        $this->file_list = new FileList();
        $this->ancestors = [];
    }

    public function client($client = null) {
        if ( !is_null($client) && is_a($client, '\RMS\WP2S\GitHub\Client') ) {
            $this->client = $client;
        }
        return $this->client;
    }

    public function clone($deep = false) {
        return new self($this->node_id, $this->url, $this->ref, $this->object);
    }

    protected function update(Branch $branch) {
        $this->ancestors[]= $this->clone(); // phpcs:ignore

        $this->node_id = $branch->node_id();
        $this->url     = $branch->url();
        $this->ref     = $branch->ref();
        $this->object  = $branch->object();
    }

    public function node_id() : string {
        return $this->node_id;
    }

    public function url() : string {
        return $this->url;
    }

    public function ref() : string {
        return $this->ref;
    }

    public function object() : array {
        return $this->object;
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

    public function hash() : string {
        if ( is_null($this->hash) ) {
            $this->hash = Util::pluck($this->object, ['sha']) ?? '';
        }
        return $this->hash;
    }

    public function addFiles(FileList $file_list) {
        $this->file_list->merge($file_list);
    }

    public function addFile(File $file) {
        $this->file_list->add($file);
    }

    public function files() {
        return $this->file_list->allFiles();
    }

    public function empty() : bool {
        return count($this->updated_files()) === 0;
    }

    public function updated_files() : array {
        return $this->file_list->updatableFiles();
    }

    public function commit() {
        if ( $this->empty() ) {
            Log::l('No files to deploy');
            $this->delete();
            return;
        }

        Log::l( sprintf('Deploying %d files', $this->file_list->count()) );
        $binary_files = $this->file_list->binaryFiles();
        foreach ($binary_files as $file) {
            $this->client->create_blob($file);
        }

        $large_files = $this->file_list->largeFiles();
        foreach ($large_files as $file) {
            $this->client->create_blob($file);
        }

        // $deleted_files = DeployCache::findDeleted($this->file_list);
        // $this->file_list->merge($deleted_files);

        $tree = array_map(
            function($file) {
                return $file->tree_payload();
            },
            $this->file_list->updatableFiles()
        );

        $tree_hash = $this->client->create_tree($this->hash(), array_values($tree));
        // error_log("tree_hash: " . $tree_hash);
        $commit_hash = $this->client->create_commit($tree_hash, $this->hash());
        // TODO Mark files in commit
        // error_log("commit_hash: " . $commit_hash);

        $this->update_to_hash($commit_hash);
        $pr = $this->client->create_pull_request($this);
        if ( $pr->merge() ) {
            // TODO check merged response from PR request
            $this->merged(true);
            $this->delete();
        }
    }

    private function merged($value = null) {
        if ( !is_null($value) ) {
            $this->merged = $value;
        }

        return $this->merged;
    }

    private function update_to_hash($commit_hash) {
        $branch = $this->client->update_reference($this->ref, $commit_hash);
        $this->update($branch);
    }

    /**
     * Delete branch after merge
     */
    private function delete() {
        if ( $this->merged() || $this->empty() ) {
            Log::l('Deleted branch for GH deploy: ' . $this->name());
            $this->client->delete_branch($this);
        }
    }
}
