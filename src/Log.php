<?php

namespace RMS\WP2S\GitHub;

class Log {
    const ERROR = 0;
    const WARN = 5;
    const INFO = 10;
    const DEBUG = 20;

    // TODO add support for limiting logging by levels
    public static function l($message, int $level = self::INFO, ...$message_args) {
        if ( count($message_args) > 0 ) {
            $message = vsprintf(
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
