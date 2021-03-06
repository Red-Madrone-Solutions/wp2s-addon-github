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
        Log::l('Starting GitHub deploy');
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
            $message = sprintf(
                'Error getting branch (`%s`) from git',
                $client->source_branch()
            );
            Log::error($message);
            throw new DeployException($message);
        }
        Log::l('Created branch for GH deploy: ' . $branch->name());

        File::setup($this->processed_site_path);

        $count = 0;
        foreach ( $iterator as $filename => $file_object ) {
            $file = File::create($filename);
            if ( is_null($file) ) {
                continue;
            }

            // if ( $file->already_deployed() ) {
            //     $already_deployed_files->addFile($file);
            //     continue;
            // }


            // Collect file for commit
            $branch->addFile($file);

            // if ( $count++ > 5 ) {
            //     break;
            // }
        }
        $branch->commit();
        foreach ( $branch->updated_files() as $file ) {
            $file->mark_deployed();
        }
        Log::l('Finished GitHub deploy');
    }
}

