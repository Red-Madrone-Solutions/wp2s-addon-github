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

        curl_setopt($ch, CURLOPT_PROXY, 'localhost:8888');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_PROXY_SSL_VERIFYPEER, false);

        if ( strtoupper($this->type) === 'POST' ) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }

        $custom_types = [ 'DELETE', 'PATCH', 'PUT' ];
        if ( in_array(strtoupper($this->type), $custom_types) ) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->type));
        }

        $request_headers = [
            'Authorization: token ' . $this->token,
            'Accept-Encoding: gzip, deflate',
            'Content-Type: application/json; charset=utf-8',
        ];

        if ( $this->body ) {
            $body = json_encode($this->body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $request_headers[]= 'Content-Length: ' . strlen($body);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);

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

