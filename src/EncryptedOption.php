<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class EncryptedOption extends Option {
    public function type() {
        return 'password';
    }
}
