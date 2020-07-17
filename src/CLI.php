<?php

namespace RMS\WP2S\GitHub;

use WP_CLI;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

/**
 * WP2StaticZip WP-CLI commands
 *
 * Registers WP-CLI commands for RMS WP2S GitHub add-on under main wp2static cmd
 *
 * Usage: wp wp2static github dry_run
 */
class CLI {

    /**
     * GitHub commands
     *
     * @param string[] $args CLI args
     * @param string[] $assoc_args CLI args
     */
    public function github(
        array $args,
        array $assoc_args
    ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;

        if ( empty( $action ) ) {
            // TODO update error message for proper action args
            WP_CLI::error( 'Missing required argument: <get_path|get_url>' );
        }

        if ( $action === 'dry_run' ) {
            $path = \WP2Static\ProcessedSite::getPath();
            Controller::dryRun($path);
            WP_CLI::line( 'dry run...' );
        }

        if ( $action === 'site_path' ) {
            $path = \WP2Static\ProcessedSite::getPath();
            WP_CLI::line( $path );
        }
    }
}


