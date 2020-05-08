<?php

namespace RMS\WP2S\GitHub;

class DryRunBranch extends Branch {
    public function __construct() {
        $this->file_list = new FileList();
    }
}
