<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class DeployState {
    public static $NAMESPACE = 'GitHub';

    const LOCAL_ONLY       = 0;
    const BLOB_CREATED     = 1;
    const IN_COMMIT        = 2;
    const IN_TARGET_BRANCH = 3;
    const IN_PULL_REQUEST  = 4;
    const TO_BE_DELETED    = 5;
    const NEEDS_UPDATE     = 6;

    public static function fileIsCurrent(File $file) {
    }

    public static function stateForFile(File $file) {
    }

    private static function detailsForFile(File $file) {
        $cache_key = self::build_cache_key($file);
    }

    private static function build_cache_key(File $file) {
    }

}
