<?php

namespace RMS\WP2S\GitHub;

class Log {
    const ERROR = 0;
    const WARN = 5;
    const INFO = 10;
    const DEBUG = 20;

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

    // TODO add support for limiting logging by levels
    public static function l($message, int $level = self::INFO, ...$message_args) {
        if ( $level > RMS_WP2S_GITHUB_LOG_LEVEL) {
            return;
        }

        if ( count($message_args) > 0 ) {
            $message = self::format_message_for_browser($message, $message_args);
        }

        \WP2Static\WsLog::l(
            sprintf(
                '<code>[%s]</code> %s',
                self::levelLabel($level),
                $message
            )
        );
    }

    public static function debug($message, ...$message_args) {
        self::l($message, self::DEBUG, ...$message_args);
    }

    public static function error($message, ...$message_args) {
        self::l($message, self::ERROR, ...$message_args);
    }

    protected static function levelLabel($level) : string {
        $lookup = [
            self::ERROR => 'Error',
            self::WARN  => 'Warn',
            self::INFO  => 'Info',
            self::DEBUG => 'Debug',
        ];

        return isset($lookup[$level])
            ? $lookup[$level]
            : 'Unknown'
        ;
    }
}
