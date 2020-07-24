<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class Request {
    private $token;
    private $url;
    private $type;
    private $body;

    private static $RETRY_COUNT = 0;
    private static $RETRY_MAX;
    private static $RETRIABLE_STATUS_CODES;

    public static function setup() {
        add_action(
            'init',
            function() {
                self::$RETRY_MAX = apply_filters(
                    'rms/wp2s/github/retry-max',
                    10
                );

                self::$RETRIABLE_STATUS_CODES = apply_filters(
                    'rms/wp2s/github/retriable-status-codes',
                    [ 502 ]
                );
            }
        );
    }

    public function __construct($token, $url, $type = 'GET') {
        $this->token   = $token;
        $this->url     = $url;
        $this->type    = $type;
        $this->headers = [];
        $this->body    = null;
    }

    public function exec() : Response {
        // TODO consider using `wp_remote_get()` or `WP_Http` class instead of curl

        if ( empty($this->token) ) {
            throw new \UnexpectedValueException('Token must not be empty');
        }

        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_init
        $ch = curl_init();

        $response = new Response();
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'RMS WP2S Addon - GitHub v1');
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, [ $response, 'collect_headers' ]);

        // Allow updates to curl setup (such as proxy configuration)
        do_action('rms/wp2s/github/curl-setup', $ch);

        if ( strtoupper($this->type) === 'POST' ) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        $custom_types = [ 'DELETE', 'PATCH', 'PUT' ];
        if ( in_array(strtoupper($this->type), $custom_types, true) ) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->type));
        }

        $request_headers = [
            'Authorization: token ' . $this->token,
            'Accept-Encoding: gzip, deflate',
            'Content-Type: application/json; charset=utf-8',
        ];

        $log_template = 'Request: %s';
        $log_args     = [
            '[' . $this->type . '] ' . $this->url,
        ];
        if ( $this->body ) {
            // TODO consider using `wp_json_encode()` instead of `json_encode()`
            $body = json_encode($this->body); // phpcs:ignore WordPress.WP.AlternativeFunctions
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

            // phpcs:disable Generic.Formatting.MultipleStatementAlignment
            $request_headers[]= 'Content-Length: ' . strlen($body);
            $log_template .= ' %s';
            $log_args[]= $this->body;
            // phpcs:enable Generic.Formatting.MultipleStatementAlignment
        }

        Log::debug3($log_template, ...$log_args);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
        $response->body(curl_exec($ch));
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
        curl_close($ch);
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_setopt
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_init

        if ( $response->is_error() && $request->should_retry($response) ) {
            Log::info('Retrying request after error');
            return $this->exec();
        }

        return $response;
    }

    public function body($value = null) {
        if ( !is_null($value) ) {
            $this->body = $value;
        }

        return $this->body;
    }

    protected function should_retry(Response $response) : bool {
        // TODO implement some kind of "back-off" by time - reset/reduce the retry count as we get further from last retry time
        if ( in_array($response->status_code(), self::$RETRIABLE_STATUS_CODES, true) ) {
            if ( self::$RETRY_COUNT++ <= self::$RETRY_MAX ) {
                return true;
            }
        }
        return false;
    }
}

