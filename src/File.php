<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class File {
    /**
     * Path to processed site
     *
     * @var string
     */
    protected static $processed_site_path;

    /**
     * Length of path to processed site
     *
     * For string manipulation
     *
     * @var int
     */
    protected static $processed_site_path_len;

    /**
     * `finfo` reference for getting mime info for a file
     *
     * @var finfo
     */
    protected static $mime_type;

    /**
     * Path to the file on the filesystem
     *
     * @var mixed
     */
    protected $file_path = null;

    protected static $file_mapper = null;

    /**
     * Path to file in target repo
     *
     * Should only access through the accessor function.
     *
     * @used_by self::commit_path()
     *
     * @var mixed
     */
    private $commit_path       = null;
    private $sha               = null;
    private $size              = null;
    private $path_hash         = null;
    private $status            = null;
    private $local_content_hash = null;
    private $content_hash      = null;
    private $cache_key = null;

    /**
     * Setup File class for future usage
     *
     * @since 1.0
     *
     * @param string $processed_site_path
     */
    public static function setup(string $processed_site_path, FileMapper $file_mapper) : void {
        self::$processed_site_path = $processed_site_path;
        // if ( substr($processed_site_path, -1) !== '/' ) {
        //     self::$processed_site_path .= '/';
        // }
        self::$processed_site_path_len = strlen($processed_site_path);
        self::$mime_type               = new \finfo(FILEINFO_MIME);
        self::$file_mapper             = $file_mapper;
    }

    /**
     * Create a new File instance
     *
     * @since 1.0
     *
     * @uses DeployCache::fileIsCached()
     * @uses DeployCache::getFileMetaValue()
     * @uses self::cache_key()
     *
     * @param string $filepath
     * @param bool $needs_delete
     *
     * @return self
     */
    private function __construct(string $filepath) {
        $this->file_path = $filepath;
        $this->load();
    }

    private function load() {
        $this->sha                 = self::$file_mapper->get($this->path_hash(), 'sha');
        $this->stored_content_hash = self::$file_mapper->get($this->path_hash(), 'content_hash');
        $this->state               = self::$file_mapper->get($this->path_hash(), 'state', FileStatus::LOCAL_ONLY);
    }

    public function storedContentHash() {
        return $this->stored_content_hash;
    }

    public function state() {
        return $this->state;
    }

    public function needsUpdate() {
        // If file doesn't exist in git
        if ( empty($this->sha) ) {
            return true;
        }

        // If contents of file have changed
        if ( $this->stored_content_hash != $this->localContentHash() ) {
            return true;
        }

        return false;
    }

    /**
     * Mark file stored in DeployCache
     *
     * @since 1.0
     *
     * @uses FileStatus::BLOB_CREATED
     * @uses MetaName::SHA
     * @uses MetaName::FILE_STATUS
     * @uses MetaName::FILE_HASH
     * @uses DeployCache::upsertMetaInfo()
     * @uses self::contentHash()
     *
     * @param string $sha
     *
     * @return void
     */
    public function stored(string $sha) {
        $this->sha                 = $sha;
        $this->state               = DeployState::BLOB_CREATED;
        $this->stored_content_hash = $this->localContentHash();

        self::$file_mapper->set(
            $this->path_hash(),
            [
                'sha'          => $this->sha,
                'state'        => $this->state,
                'content_hash' => $this->stored_content_hash,
                'path'         => $this->commit_path(),
            ]
        );
    }

    public function committed() {
        $this->state = DeployState::IN_COMMIT;
        self::$file_mapper->set(
            $this->path_hash(),
            [
                'state' => $this->state,
            ]
        );
    }

    public function pr_created() {
        $this->state = DeployState::IN_PULL_REQUEST;
        self::$file_mapper->set(
            $this->path_hash(),
            [
                'state' => $this->state,
            ]
        );
    }

    public function pr_merged() {
        $this->state = DeployState::IN_TARGET_BRANCH;
        self::$file_mapper->set(
            $this->path_hash(),
            [
                'state' => $this->state,
            ]
        );
    }

    /**
     * Lazily generate a hash of file path
     *
     * @since 1.0
     *
     * @return string
     */
    public function path_hash() : string {
        if ( is_null($this->path_hash) ) {
            $this->path_hash = hash('sha256', $this->file_path);
        }
        return $this->path_hash;
    }

    /**
     * Get the commit path for the file.
     *
     * Lazy loads the commit path which is essentially the `$file_path` without
     * the `$processed_site_path` or a leading directory separator.
     *
     * @since 1.0
     *
     * @return string $commit_path
     */
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

    /**
     * Generate a key for cache reference
     *
     * @since 1.0
     *
     * @returm string
     */
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
        // phpcs:disable Squiz.WhiteSpace.SemicolonSpacing.Incorrect
        return $this->state !== DeployState::LOCAL_ONLY
            && $this->stored_content_hash === $this->localContentHash()
            && !is_null($this->sha) // phpcs:ignore
        ;
        // phpcs:enable Squiz.WhiteSpace.SemicolonSpacing.Incorrect
    }

    /**
     * Create a new File object if valid.
     *
     * @uses File::is_valid to check if file is valid
     *
     * @param string $filepath
     *
     * @return File|null
     */
    public static function create(string $filepath) {
        if ( self::is_valid($filepath) ) {
            return new static($filepath);
        }

        return null;
    }

    /**
     * Check if file "is valid" and should be included in deploy
     *
     * "is valid" means it's not a directory or link
     *
     * @since 1.0
     *
     * @param mixed $filepath
     *
     * @return bool
     */
    protected static function is_valid($filepath) : bool {
        if ( !is_string($filepath) ) {
            return false;
        }

        $basename = basename($filepath);
        if ( $basename === '.' || $basename === '..' ) {
            return false;
        }

        $real_filepath = realpath($filepath);
        if ( !$real_filepath ) {
            return false;
        }

        return true;
    }

    /**
     * Get the mime type
     *
     * @since 1.0
     *
     * @return string
     */
    private function mime_type() : string {
        return self::$mime_type->file($this->file_path);
    }

    public function is_text() : bool {
        return 'text' === substr($this->mime_type(), 0, 4);
    }

    public function is_binary() : bool {
        return !$this->is_text();
    }

    public function needs_delete() : bool {
        // TODO actually test for delete
        return false;
        return $this->needs_delete;
    }

    public function already_deployed() : bool {
        return DeployCache::fileIsCached($this->cache_key());
    }

    public function mark_deployed() {
        Log::info('Add to deploy cache: ' . $this->cache_key());
        return DeployCache::addFile($this->cache_key());
    }

    /**
     * Get the hash for local file contents.
     *
     * Lazy loads and caches the hash value so multiple calls will not
     * recalculate the hash value.
     *
     * @since 1.0
     *
     * @param bool $refresh
     *
     * @return string hash value
     */
    public function localContentHash(bool $refresh = false) : string {
        if ( is_null($this->local_content_hash) || $refresh ) {
            $this->local_content_hash = md5($this->contents());
        }

        return $this->local_content_hash;
    }

    public function contents($encoding = 'none') {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $contents = file_get_contents($this->file_path);

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        return $encoding === 'base64' ? base64_encode($contents) : $contents;
    }

    public function tree_payload() : array {
        $payload = [
            'path' => $this->commit_path(),
            'mode' => '100644',
            'type' => 'blob',
        ];

        if ( $this->needs_delete() ) {
            $payload['sha'] = null;
        } elseif ( $this->sha ) {
            $payload['sha'] = $this->sha;
        } else {
            $payload['content'] = $this->contents();
        }

        return $payload;
    }
}
