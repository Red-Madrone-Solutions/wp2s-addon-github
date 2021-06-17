<?php

namespace RMS\WP2S\GitHub;

class Controller {
    private static $options_action = 'rms_wp2s_gh_options';
    private static $options_nonce_name = 'rms_options_security_nonce';
    private static $test_action = 'rms_wp2s_gh_test';
    private static $test_nonce_name = 'rms_test_security_nonce';
    private static $slug = 'rms-wp2s-addon-gh';

    /**
     * Add-on initialization routine
     */
    public function run() : void {
        // register add-on option page
        add_action(
            'admin_menu',
            [ $this, 'addOptionsPage' ],
            15,
            1
        );

        // Ensure WP2Static > Options is active menu when in Add-on's options view
        add_filter( 'parent_file', [ $this, 'setActiveParentMenu' ] );

        add_filter( 'wp2static_deploy_cache_totals_by_namespace', '__return_true' );

        // Handler to trigger during deploy phase
        add_action('wp2static_deploy', [ $this, 'deploy' ], 15, 2);

        // Handler to trigger after deploy phase
        add_action('wp2static_post_deploy_trigger', [ $this, 'postDeploy' ], 15, 2);

        // add_filter('wp2static_add_menu_items', [ 'RMS\WP2S\GitHub\Controller', 'addSubMenuPage' ]);

        add_action('admin_post_' . self::$options_action, [ $this, 'saveOptionsFromUi' ]);
        add_action('admin_post_' . self::$test_action, [ $this, 'testGitHubIntegration' ]);

        self::setup();

        Database::instance()->update_db();

        if ( defined( 'WP_CLI' ) ) {
            \WP_CLI::add_command(
                'wp2static github',
                [ 'RMS\WP2S\GitHub\CLI', 'github' ]
            );
        }
    }

    public static function setup() {
        Log::setup();
        AdminNotice::setup();
        EncryptedOption::setup();
        Request::setup();
        Response::setup();
    }

    /**
     * Register Add-on options page
     *
     * Will be linked to from WP2Static > Add-ons page
     */
    public function addOptionsPage() : void {
        add_submenu_page(
            'null', // don't add within WP2Static menu directly
            'RMS GitHub Deployment Options', // page name / title
            'RMS GitHUb Deployment Options', // page name / title
            'manage_options', // user level required to access page
            self::$slug, // page slug
            [ $this, 'renderOptionsPage' ] // function to render page
        );
    }

    public function renderOptionsPage() : void {
        $view_params = [
            'action'          => self::$options_action,
            'nonce_name'      => self::$options_nonce_name,
            'option_set'      => new OptionSet($load_from_db = true),
            'test_action'     => self::$test_action,
            'test_nonce_name' => self::$test_nonce_name,
        ];
        require_once RMS_WP2S_GH_PATH . 'views/options-page.php';
    }

    public static function activate() : void {
        EncryptedOption::activate();

        // Tell WP2Static about this addon
        do_action(
            'wp2static_register_addon',
            self::$slug,
            'deploy',
            'RMS WP2Static GitHub Deploy',
            '',
            'Deploy static site scrape to GitHub',
        );
    }

    public static function deactivate() : void {
        EncryptedOption::teardown();
        Database::teardown();
    }

    public function saveOptionsFromUi() : void {
        check_admin_referer(self::$options_action, self::$options_nonce_name);

        $option_set = new OptionSet($load_from_db = 1, $_POST);
        Database::instance()->updateOptions($option_set);
        ( new AdminNotice('Options saved') )->save();
        wp_safe_redirect( admin_url('admin.php?page=' . self::$slug) );
        exit;
    }

    public function testGitHubIntegration() : void {
        check_admin_referer(self::$test_action, self::$test_nonce_name);

        $option_set = new OptionSet($load_from_db = 1);
        Log::l('Starting check GitHub integration');

        try {
            $client = new Client($option_set);

            if ( $client->canAccess() ) {
                Log::l('GitHub Integration test succeeded');
                ( new AdminNotice('GitHub Integration Test Succeeded') )->save();
            } else {
                Log::l('GitHub Integration test failed');
                ( new AdminNotice('GitHub Integration Test Failed', 'error') )->save();
            }
        } catch (TokenException $e) {
            ( new AdminNotice('Cannot read token from database, please re-enter', 'error') )->save();
        }
        Log::l('Finished check GitHub integration');

        wp_safe_redirect( admin_url('admin.php?page=' . self::$slug) );
        exit;
    }

    public static function deploy(string $processed_site_path) : void {
        $deployer = new Deployer();
        $deployer->setup($processed_site_path);
        try {
            $deployer->execute();
        } catch (DeployException $e) {
            Log::error('Deploy Error: ' . $e->getMessage());
        }
    }

    public function postDeploy() : void {
    }

    public static function dryRun(string $processed_site_path) : void {
        $dry_run = new DryRun();
        $dry_run->setup($processed_site_path);
        $dry_run->execute();
    }

    public function setActiveParentMenu() : void {
        global $plugin_page;

        if ( self::$slug === $plugin_page ) {
            $plugin_page = 'wp2static-options';
        }
    }
}
