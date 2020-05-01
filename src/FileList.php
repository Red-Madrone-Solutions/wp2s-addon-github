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
}
