<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class DeployCache {
    public static $CACHE_NAMESPACE = 'GitHub';

    /**
     * Initial setup for DeployCache
     *
     * Enables display of deploy cache totals by namespace
     *
     * @since 1.0
     */
    public static function setup() : void {
        add_filter(
            'wp2static_deploy_cache_totals_by_namespace',
            '__return_true'
        );
    }

    /**
     * Add a file to the WP2Static Deploy Cache
     *
     * @since 1.0
     *
     * @param string $local_path
     */
    public static function addFile(File $file) : void {

        \WP2Static\DeployCache::addFile($local_path, self::$CACHE_NAMESPACE);
    }

    /**
     * Check if a file is already cached.
     *
     * @since 1.0
     *
     * @param File $file
     *
     * @return bool
     */
    public static function fileIsCached(File $file) : bool {
        $path_hash = Database::instance()->getPathHash(
            $file->path_hash(),
            $file->contentHash(),
            self::$CACHE_NAMESPACE
        );
        return (bool) $path_hash;
    }

    /**
     * Find files in the cache that have been deleted.
     *
     * Iterates over the list of files from the deploy cache and checks to see
     * if they exist in a provided list of filesystem files.
     *
     * @since 1.0
     *
     * @uses \WP2Static\DeployCache::getPaths()
     * @uses File::pathForCacheKey()
     *
     * @param FileList $filesystem_files
     *
     * @return FileList $deleted_files
     */
    public static function findDeleted(FileList $filesystem_files) : FileList {
        $deleted_files      = new FileList();
        $deploy_cache_files = \WP2Static\DeployCache::getPaths();
        foreach ( $deploy_cache_files as $cache_key ) {
            if ( !$filesystem_files->cacheKeyExists($cache_key) ) {
                $filepath = File::pathForCacheKey($cache_key);
                $deleted_files->addFile(new File($filepath, $needs_delete = true));
            }
        }
        return $deleted_files;
    }

    public static function seedFrom(string $namespace) {
        Database::instance()->truncateAndSeedDeployCache($namespace, self::$CACHE_NAMESPACE);
    }


    public static function persistFileMetaData(
        File $file,
        array $meta
    ) : void {
        Database::instance()->upsertMetaInfo(
            $file->path_hash(),
            self::$CACHE_NAMESPACE,
            $meta
        );
    }

    public static function getFileMetaValue($file, $meta_name) : string {
        return Database::instance()->getMetaInfo(
            $file->path_hash(),
            self::$CACHE_NAMESPACE,
            $meta_name
        );
    }

}
