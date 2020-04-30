<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

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

    public function __construct($node_id, $url, $ref, $object = []) {
        $this->node_id = $node_id;
        $this->url     = $url;
        $this->ref     = $ref;
        $this->object  = $object;
        $this->files   = [];
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
        $this->ancestors[]= $this->clone();

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

    public function addFile(File $file) {
        $this->files[]= $file;
    }

    public function commit() {
        $tree = array_map(function($file) {
            return $file->tree_payload();
        }, $this->files);

        $tree_hash = $this->client->create_tree($this->hash(), $tree);
        error_log("tree_hash: " . $tree_hash);
        $commit_hash = $this->client->create_commit($tree_hash, $this->hash());
        error_log("commit_hash: " . $commit_hash);

        $this->update_to_hash($commit_hash);
    }

    private function update_to_hash($commit_hash) {
        $branch = $this->client->update_reference($this->ref, $commit_hash);
        $this->update($branch);
    }
}
