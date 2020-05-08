<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class File {
    protected static $processed_site_path;
    protected static $processed_site_path_len;
    protected static $mime_type;

    private $file_path;
    private $commit_path;
    private $sha;
    private $size;

    public static function setup($processed_site_path) {
        self::$processed_site_path = $processed_site_path;
        // if ( substr($processed_site_path, -1) !== '/' ) {
        //     self::$processed_site_path .= '/';
        // }
        self::$processed_site_path_len = strlen($processed_site_path);
        self::$mime_type = new \finfo(FILEINFO_MIME);
    }

    private function __construct($filepath) {
        $this->file_path   = $filepath;
        $this->commit_path = null;
        $this->cache_key   = null;
        $this->sha         = null;
        $this->size        = null;
    }

    public function commit_path() : string {
        if ( is_null($this->commit_path) ) {
            if (
                substr($this->file_path, 0, self::$processed_site_path_len)
                === self::$processed_site_path
            ) {
                $this->commit_path = substr($this->file_path, self::$processed_site_path_len);
            } else {
                $this->commit_path = $this->file_path;
            }

            if ( substr($this->commit_path, 0, 1) === '/' ) {
                $this->commit_path = substr($this->commit_path, 1);
            }

            // TODO Figure out how to handle windows
            // $this->commit_path = str_replace('\\', '/', $this->commit_path);
        }
        return $this->commit_path;
    }

    private function cache_key() : string {
        if ( is_null($this->cache_key) ) {
            if (
                substr($this->file_path, 0, self::$processed_site_path_len)
                === self::$processed_site_path
            ) {
                $this->cache_key = substr($this->file_path, self::$processed_site_path_len);
            } else {
                $this->cache_key = $this->file_path;
            }
        }
        return $this->cache_key;
    }

    public function sha($value = null) {
        if ( !is_null($value) ) {
            $this->sha = $value;
        }

        return $this->sha;
    }

    public function size() : int {
        if ( is_null($this->size) ) {
            $this->size = filesize($this->file_path);
        }
        return $this->size;
    }

    public function blob_exists() : bool {
        return !is_null($this->sha);
    }

    public static function create($filepath) {
        if ( self::is_valid($filepath) ) {
            return new self($filepath);
        }

        return null;
    }

    protected static function is_valid($filepath) : bool {
        if ( !is_string($filepath) ) {
            return false;
        }

        $basename = basename($filepath);
        if ( $basename == '.' || $basename == '..' ) {
            return false;
        }

        $real_filepath = realpath($filepath);
        if ( !$real_filepath ) {
            return false;
        }

        return true;
    }

    private function mime_type() : string {
        return self::$mime_type->file($this->file_path);
    }

    public function is_text() : bool {
        return 'text' === substr($this->mime_type(), 0, 4);
    }

    public function is_binary() : bool {
        return !$this->is_text();
    }

    public function already_deployed() : bool {
        return DeployCache::fileIsCached($this->cache_key());
    }

    public function mark_deployed() {
        return DeployCache::addFile($this->cache_key());
    }

    public function contents($encoding = 'none') {
        $contents = file_get_contents($this->file_path);

        return $encoding === 'base64' ? base64_encode($contents) : $contents;
    }

    public function tree_payload() : array {
        $payload = [
            'path' => $this->commit_path(),
            'mode' => '100644',
            'type' => 'blob',
        ];

        if ( $this->sha ) {
            $payload['sha'] = $this->sha;
        } else {
            $payload['content'] = $this->contents();
        }

       return $payload;
    }
}
