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
        \WP2Static\WsLog::l($message);
    }
}
