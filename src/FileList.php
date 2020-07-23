<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class FileList {
    private $files;

    public function __construct() {
        $this->files = [];
    }

    public function add(File $file) {
        $this->files[$file->cache_key()] = $file;
    }

    public function empty() : bool {
        return $this->count() === 0;
    }

    public function binaryFiles($filter_for_update = true) : array {
        return array_filter(
            array_values($this->files),
            function($file) use ($filter_for_update) {
                return $file->is_binary()
                    && ($filter_for_update ? $file->needs_update() : true);
            }
        );
    }

    public function deletedFiles() : array {
        return array_filter(
            array_values($this->files),
            function($file) {
                return $file->needs_delete();
            }
        );
    }

    public function allFiles() : array {
        return $this->files;
    }

    public function largeFiles($filter_for_update = true) : array {
        $fifty_k = 1024 * 50;
        return array_filter(
            array_values($this->files),
            function($file) use ($fifty_k, $filter_for_update) {
                return ($file->size() > $fifty_k)
                    && ($filter_for_update ? $file->needs_update() : true);
            }
        );
    }

    public function isEmpty() : bool {
        return $this->count() === 0;
    }

    public function deployableFiles() {

    }

    public function updatableFiles() {
        // TODO Make sure that we are taking into account all state information
        return array_filter(
            array_values($this->files),
            function($file) {
                return $file->needs_update() || $file->needs_delete();
            }
        );
    }

    public function deletableFiles() {
        return array_filter(
            array_values($this->files),
            function($file) {
                return $file->needs_delete();
            }
        );
    }

    public function count() : int {
        return count($this->files);
    }

    public function filter($callable) : FileList {
        if ( !is_callable($callable) ) {
            throw new InvalidArgumentException('Must provide a valid callable');
        }

        $new_list = new self();
        foreach ( $this->files as $file ) {
            if ( $callable($file) ) {
                $new_list->add($file);
            }
        }

        return $new_list;
    }

    public function cacheKeyExists($cache_key) : bool {
        return isset($this->files[$cache_key]);
    }

    public function merge(FileList $other_list) : void {
        $this->files = array_merge($this->files, $other_list->allFiles());
    }
}
