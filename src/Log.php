<?php

namespace RMS\WP2S\GitHub;

class Log {
    const ERROR = 0;
    const WARN = 5;
    const INFO = 10;
    const DEBUG = 20;
    const DEBUG2 = 30;
    const DEBUG3 = 40;

    public static function setup() {
        add_action('init', function() {
            if ( !defined('RMS_WP2S_GITHUB_LOG_LEVEL') ) {
                define(
                    'RMS_WP2S_GITHUB_LOG_LEVEL',
                    /**
                     * ```
                     * add_action('rms/wp2s/github/log-level', function($log_level) {
                     *   return \RMS\WP2S\GitHub\Log::INFO;
                     * });
                     * ```
                     */
                    apply_filters('rms/wp2s/github/log-level', self::INFO)
                );
            }
        });
    }

    private static function format_message_for_browser(
        string $message,
        array $message_args
    ) {
        return vsprintf(
            $message,
            array_map(
                function($obj) {
                    return '<pre><code>'
                        . print_r($obj, 1)
                        . '</pre></code>'
                        ;
                },
                $message_args
            )
        );
    }

    private static function format_message_for_cli(
        string $message,
        array $message_args
    ) {
        return vsprintf(
            $message,
            array_map(
                function($obj) { return print_r($obj, 1); },
                $message_args
            )
        );
    }

    // TODO add support for limiting logging by levels
    public static function l($message, int $level = self::INFO, ...$message_args) {
        if ( $level > RMS_WP2S_GITHUB_LOG_LEVEL) {
            return;
        }

        if ( count($message_args) > 0 ) {
            if ( defined('WP_CLI') ) {
                $message = self::format_message_for_cli($message, $message_args);
            } else {
                $message = self::format_message_for_browser($message, $message_args);
            }
        }

        $log_template =
            defined('WP_CLI')
            ? '[%s] %s'
            : '<code>[%s]</code> %s'
        ;

        $log_message = sprintf(
            $log_template,
            self::levelLabel($level),
            $message
        );

        if ( defined('WP_CLI') ) {
            \WP_CLI::line($log_message);
        } else {
            \WP2Static\WsLog::l($log_message);
        }
    }

    public static function debug($message, ...$message_args) {
        self::l($message, self::DEBUG, ...$message_args);
    }

    public static function debug2($message, ...$message_args) {
        self::l($message, self::DEBUG2, ...$message_args);
    }

    public static function debug3($message, ...$message_args) {
        self::l($message, self::DEBUG3, ...$message_args);
    }

    public static function error($message, ...$message_args) {
        self::l($message, self::ERROR, ...$message_args);
    }

    public static function warn($message, ...$message_args) {
        self::l($message, self::WARN, ...$message_args);
    }

    public static function info($message, ...$message_args) {
        self::l($message, self::INFO, ...$message_args);
    }

    protected static function levelLabel($level) : string {
        $lookup = [
            self::ERROR => 'Error',
            self::WARN  => 'Warn',
            self::INFO  => 'Info',
            self::DEBUG => 'Debug',
            self::DEBUG2 => 'Debug-2',
            self::DEBUG3 => 'Debug-3',
        ];

        return isset($lookup[$level])
            ? $lookup[$level]
            : 'Unknown'
        ;
    }
}
