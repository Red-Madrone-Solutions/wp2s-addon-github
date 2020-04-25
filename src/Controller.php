<?php

namespace RMS\WP2S\GitHub;

class Controller {
    public function run() : void {
        add_filter('wp2static_add_menu_items', [ 'RMS\WP2S\GitHub\Controller', 'addSubMenuPage' ]);
    }

    public static function addSubMenuPage(array $submenu_pages) : array {
        $submenu_pages['GitHub'] = [ 'RMS\WP2S\GitHub\Controller', 'renderOptionsPage' ];

        return $submenu_pages;
    }

    public static function renderOptionsPage() : void {
        require_once RMS_WP2S_GH_PATH . 'views/options-page.php';
    }

    public static function activate() : void {
    }

    public static function deactivate() : void {
    }
}
