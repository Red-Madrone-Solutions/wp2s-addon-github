<?php

namespace RMS\WP2S\GitHub;

class Controller {
    private static $options_action = 'rms_wp2s_gh_options';
    private static $options_nonce_name = 'rms_options_security_nonce';

    public function run() : void {
        add_filter('wp2static_add_menu_items', [ 'RMS\WP2S\GitHub\Controller', 'addSubMenuPage' ]);

        add_action('admin_post_' . self::$options_action, [ $this, 'saveOptionsFromUi' ]);

        Database::instance()->update_db();
    }

    public static function addSubMenuPage(array $submenu_pages) : array {
        $submenu_pages['GitHub'] = [ 'RMS\WP2S\GitHub\Controller', 'renderOptionsPage' ];

        return $submenu_pages;
    }

    public static function renderOptionsPage() : void {
        $view_params = [
            'action' => self::$options_action,
            'nonce_name' => self::$options_nonce_name,
            'option_set' => new OptionSet($load_from_db = true),
        ];
        require_once RMS_WP2S_GH_PATH . 'views/options-page.php';
    }

    public static function activate() : void {
    }

    public static function deactivate() : void {
    }

    public function saveOptionsFromUi() : void {
        check_admin_referer(self::$options_action, self::$options_nonce_name);

        $option_set = new OptionSet($load_from_db = 1, $_POST);
        Database::instance()->updateOptions($option_set);
    }
}
