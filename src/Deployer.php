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

        $option_set = new OptionSet($load_from_db = 1);
        $client = new Client($option_set);
        // Use the hash for this branch when creating files
        $branch = $client->deploySetup();
        if ( !$branch->is_valid() ) {
            throw new \Exception('Error getting branch from git');
        }

        File::setup($this->processed_site_path);

        $count = 0;
        foreach ( $iterator as $filename => $file_object ) {
            $file = File::create($filename);
            if ( is_null($file) ) {
                continue;
            }

            if ( $file->already_deployed() ) {
                continue;
            }


            if ( $file->is_text() ) {
                continue;
            }

            // Collect file for commit
            $branch->addFile($file);

            if ( $count++ > 5 ) {
                break;
            }
            // Add to deploy cache
            // \WP2Static\DeployCache::addFile($filename);
        }
        $branch->commit();
        foreach ( $branch->files() as $file ) {
            $file->mark_deployed();
        }
    }
}

