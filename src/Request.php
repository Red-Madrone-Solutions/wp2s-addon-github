<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Request {
    private $token;
    private $url;
    private $type;
    private $body;

    public function __construct($token, $url, $type = 'GET') {
        $this->token   = $token;
        $this->url     = $url;
        $this->type    = $type;
        $this->headers = [];
        $this->body    = null;
    }

    public function exec() : Response {
        $ch = curl_init();

        $response = new Response();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'RMS WP2S Addon - GitHub v1');
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, [ $response, 'collect_headers' ]);

        if ( strtoupper($this->type) === 'POST' ) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        if ( strtoupper($this->type) === 'DELETE' ) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if ( $this->body ) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->body));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $this->token,
        ]);

        $response->body(curl_exec($ch));
        curl_close($ch);

        return $response;
    }

    public function body($value = null) {
        if ( !is_null($value) ) {
            $this->body = $value;
        }

        return $this->body;
    }
}

