<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class View {
    public static function render(string $partial) {
        require self::partialPath($partial);
    }

    public static function partialPath(string $partial) {
        return RMS_WP2S_GH_PATH . sprintf('views/partials/%s.php', $partial);
    }
}
