<?php

namespace RMS\WP2S\GitHub;

class Deployer {
    private $processed_site_path;
    private $processed_site_path_len;

    public function setup(string $processed_site_path) : void {
        $this->processed_site_path = $processed_site_path;
        $this->processed_site_path_len = strlen($processed_site_path);

    }

    public function execute() : void {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->processed_site_path,
                \RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ( $iterator as $filename => $file_object ) {
            if ( !is_string($filename) ) {
                continue;
            }

            $basename = basename($filename);
            if ( $basename == '.' || $basename == '..' ) {
                continue;
            }

            $real_filepath = realpath($filename);
            if ( !$real_filepath ) {
                continue;
            }

            // Standardize on forward slash for dir separator
            $filename = str_replace('\\', '/', $filename);

            $cache_filename = $this->normalize_filename_for_cache($filename);
            if ( \WP2Static\DeployCache::fileisCached($cache_filename, 'GitHub') ) {
                continue;
            }

            // Commit file...
            error_log("commit: $filename");

            // Add to deploy cache
            // \WP2Static\DeployCache::addFile($filename);
        }
    }

    private function normalize_filename_for_cache($filename) {
        if (
            substr($filename, 0, $this->processed_site_path_len)
            === $this->processed_site_path
        ) {
            $filename = substr($filename, $this->processed_site_path_len);
        }
        return $filename;
    }
}

