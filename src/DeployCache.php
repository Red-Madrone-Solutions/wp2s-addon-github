<?php

namespace RMS\WP2S\GitHub;

class DeployCache {
    const CACHE_NAMESPACE = 'GitHub';

    public static function addFile(string $local_path) {
        \WP2Static\DeployCache::addFile($local_path, self::CACHE_NAMESPACE);
    }

    public static function fileIsCached(string $local_path) {
        return \WP2Static\DeployCache::fileisCached($local_path, self::CACHE_NAMESPACE);
    }

    public static function findDeleted(FileList $filesystem_files) {
        $deleted_files = new FileList();
        $deploy_cache_files = \WP2Static\DeployCache::getPaths();
        foreach ( $deploy_cache_files as $cache_key ) {
            if ( !$filesystem_files->cacheKeyExists($cache_key) ) {
                $filepath = File::pathForCacheKey($cache_key);
                $deleted_files->addFile(new File($filepath, $needs_delete = true));
            }
        }
        return $deleted_files;
    }
}
