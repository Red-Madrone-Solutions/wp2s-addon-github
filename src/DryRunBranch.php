<?php

namespace RMS\WP2S\GitHub;

class DryRunBranch extends Branch {
    public function __construct() {
        $this->file_list = new FileList();
    }

    public function fileList() {
        return $this->file_list;
    }

    public function commit() {
        if ( $this->empty() ) {
            Log::l('No files to deploy');
            return;
        }

        $binary_files = $this->file_list->binaryFiles();
        Log::l('Binary files to update: ' . count($binary_files));

        $large_files = $this->file_list->largeFiles();
        Log::l('Large files to update: ' . count($large_files));

        $deleted_files_list = DeployCache::findDeleted($this->file_list);
        $this->file_list->merge($deleted_files_list);

        $deletable_files = $this->file_list->deletableFiles();
        Log::l('Deletable files count: ' . count($deletable_files));

        $updatable_files = $this->file_list->updatableFiles();
        Log::l('Updatable files count: ' . count($updatable_files));
    }
}
