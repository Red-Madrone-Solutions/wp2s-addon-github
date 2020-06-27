<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class File {
    protected static $processed_site_path;
    protected static $processed_site_path_len;
    protected static $mime_type;

    private $file_path       = null;
    private $commit_path     = null;
    private $stored_sha      = null;
    private $size            = null;
    private $is_cached       = false;
    private $needs_delete    = false;
    private $path_hash       = null;
    private $file_status     = null;
    private $file_hash       = null;
    private $local_file_hash = null;

    public static function setup($processed_site_path) {
        self::$processed_site_path = $processed_site_path;
        // if ( substr($processed_site_path, -1) !== '/' ) {
        //     self::$processed_site_path .= '/';
        // }
        self::$processed_site_path_len = strlen($processed_site_path);
        self::$mime_type = new \finfo(FILEINFO_MIME);
    }

    private function __construct($filepath, $needs_delete = false) {
        $this->file_path    = $filepath;
        $this->is_cached    = DeployCache::fileIsCached($this->cache_key());
        $this->needs_delete = $needs_delete;

        $this->stored_sha
            = DeployCache::getFileMetaValue(MetaName::SHA)
            ?: null
        ;

        $this->file_status
            = DeployCache::getFileMetaValue(MetaName::FILE_STATUS)
            ?: FileStatus::LOCAL_ONLY
        ;

        $this->file_hash
            = DeployCache::getFileMetaValue(MetaName::FILE_HASH)
            ?: null
        ;
    }

    public function stored($sha) {
        $this->stored_sha  = $sha;
        $this->file_status = FileStatus::BLOB_CREATED;
        $this->file_hash   = $this->local_file_hash();

        DeployCache::upsertMetaInfo(
            $file,
            [
                MetaName::SHA         => $this->stored_sha,
                MetaName::FILE_STATUS => $this->file_status,
                MetaName::FILE_HASH   => $this->file_hash,
            ]
        );
    }

    public function path_hash() : string {
        if ( is_null($this->path_hash) ) {
            $this->path_hash = md5($this->file_path);
        }
        return $this->path_hash;
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

    public function cache_key() : string {
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

    public static function pathForCacheKey($cache_key) : string {
        return self::$processed_site_path . $cache_key;
    }

    public function sha($value = null) {
        if ( !is_null($value) ) {
            $this->sha = $value;
            DeployCache::persistFileSha($this, $value);
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
        return $this->file_status !== FileStatus::LOCAL_ONLY
            && $this->file_hash === $this->local_file_hash()
            && !is_null($this->stored_sha)
        ;
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

    public function needs_update() : bool {
        return $this->needs_update;
    }

    public function needs_delete() : bool {
        return $this->needs_delete;
    }

    public function already_deployed() : bool {
        return DeployCache::fileIsCached($this->cache_key());
    }

    public function mark_deployed() {
        Log::info('Add to deploy cache: ' . $this->cache_key());
        return DeployCache::addFile($this->cache_key());
    }

    public function local_file_hash($refresh = false) {
        if ( is_null($this->local_file_hash) || $refresh ) {
            $this->local_file_hash = md5($this->contents());
        }

        return $this->local_file_hash;
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

        if ( $this->needs_delete ) {
            $payload['sha'] = null;
        } elseif ( $this->sha ) {
            $payload['sha'] = $this->sha;
        } else {
            $payload['content'] = $this->contents();
        }

       return $payload;
    }
}
