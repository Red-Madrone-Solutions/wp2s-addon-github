<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class NullBranch {
    public function is_valid() : bool {
        return false;
    }
}
