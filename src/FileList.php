<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class FileList {
    private $files;

    public function __construct() {
        $this->files = [];
    }

    public function add(File $file) {
        // TODO check for duplicates?
        $this->files[]= $file;
    }

    public function binaryFiles() : array {
        return array_filter($this->files, function($file) {
            return $file->is_binary();
        });
    }

    public function allFiles() : array {
        return $this->files;
    }

    public function largeFiles() : array {
        $fifty_k = 1024 * 50;
        return array_filter($this->files, function($file) use ($fifty_k) {
            return $file->size() > $fifty_k;
        });
    }

    public function isEmpty() : bool {
        return $this->count() === 0;
    }

    public function count() : int {
        return count($this->files);
    }
}
