<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class FileList {
    private $files;

    public function __construct() {
        $this->files = [];
    }

    public function add(File $file) {
        $this->files[$file->cache_key()] = $file;
    }

    public function binaryFiles($filter_for_update = true) : array {
        return array_filter(
            array_values($this->files),
            function($file) {
                return
                    $file->is_binary()
                    &&
                    ($filter_for_upate ? $file->needs_update() : true)
                ;
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
            function($file) use ($fifty_k) {
                return
                    ($file->size() > $fifty_k)
                    &&
                    ($filter_for_update ? $file->needs_update() : true)
                ;
            }
        );
    }

    public function isEmpty() : bool {
        return $this->count() === 0;
    }

    public function count() : int {
        return count($this->files);
    }
}
