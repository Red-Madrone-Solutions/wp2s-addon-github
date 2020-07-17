<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class DeployCache {
    public static $CACHE_NAMESPACE = 'GitHub';

    public static function setup() : void {
        add_filter(
            'wp2static_deploy_cache_totals_by_namespace',
            '__return_true'
        );
    }

    public static function addFile(string $local_path) {
        \WP2Static\DeployCache::addFile($local_path, self::$CACHE_NAMESPACE);
    }

    public static function fileIsCached(string $local_path) {
        return \WP2Static\DeployCache::fileisCached($local_path, self::$CACHE_NAMESPACE);
    }

    public static function findDeleted(FileList $filesystem_files) {
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

    public static function getFileMetaValue($meta_name) : string {
        return Database::instance()->getMetaInfo(
            $file->path_hash(),
            self::$CACHE_NAMESPACE,
            $meta_name
        );
    }

}
