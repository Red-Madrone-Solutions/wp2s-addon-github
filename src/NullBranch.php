<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class NullBranch {
    public function is_valid() : bool {
        return false;
    }
}
