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
}
