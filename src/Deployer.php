<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class Deployer {
    private $processed_site_path;
    private $processed_site_path_len;
    protected $file_list;

    public function setup(string $processed_site_path) : void {
        $this->processed_site_path     = $processed_site_path;
        $this->processed_site_path_len = strlen($processed_site_path);
        $this->file_list               = new FileList();
    }

    /**
     * Execute a deploy
     *
     * Entrypoint for the process.
     *
     * @since 1.0
     *
     * @uses Client::deploySetup()
     * @uses File::setup()
     *
     * @return void
     */
    public function execute() : void {
        Log::l('Starting GitHub deploy');
        File::setup($this->processed_site_path, new DatabaseFileMapper() );
        $this->build_file_list();

        $deployable_files = $this->deployableFiles();
        if ( $deployable_files->empty() ) {
            Log::l('No files to deploy');
            return;
        }

        $branch = $this->setup_branch();
        // Add updated files to branch
        $branch->addFiles( $deployable_files );
        $branch->deleteFiles( $this->deletableFiles() );
        $branch->commit();
        // foreach ( $branch->updated_files() as $file ) {
        //     $file->mark_deployed();
        // }
        Log::l('Finished GitHub deploy');
    }

    protected function build_file_list() {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->processed_site_path,
                \RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

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

            // Collect file for deploy
            $this->addFile($file);

            // if ( $count++ > 5 ) {
            //     break;
            // }
        }
    }

    protected function deployableFiles() : FileList {
        // TODO Build new FileList with files that can be deployed
        return $this->file_list->filter(
            function($file) {
                if ( $file->needsUpdate() ) {
                    return true;
                }
            }
        );
    }

    private function addFile(File $file) {
        $this->file_list->add($file);
    }

    private function setup_branch() : Branch {
        $option_set = new OptionSet($load_from_db = 1);
        $client     = new Client($option_set);

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

        return $branch;
    }
}

