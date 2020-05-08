<?php

namespace RMS\WP2S\GitHub;

class DryRun {
    private $processed_site_path;
    private $processed_site_path_len;

    public function setup(string $processed_site_path) : void {
        $this->processed_site_path = $processed_site_path;
        $this->processed_site_path_len = strlen($processed_site_path);
        DeployCache::$CACHE_NAMESPACE = 'GitHub Dry Run';
    }

    public function execute() : void {
        Log::l('Starting GitHUb dry-run');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->processed_site_path,
                \RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        $branch = new DryRunBranch();

        File::setup($this->processed_site_path);

        foreach ( $iterator as $filename => $file_object ) {
            $file = File::create($filename);
            if ( is_null($file) ) {
                continue;
            }

            $branch->addFile($file);
        }

        $branch->commit();
        foreach ( $branch->files() as $file ) {
            // $file->mark_deployed();
        }
        Log::l('Finished GitHub dry-run');
        // Log::l('Looking at deleted files');
        // $deleted_file = DeployCache::findDeleted($branch->fileList());
    }
}
