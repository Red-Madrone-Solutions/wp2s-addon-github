<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class DisabledOption extends Option {
    public function attrs() {
        return [
            'disabled',
        ];
    }
}
