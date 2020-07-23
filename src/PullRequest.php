<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

class PullRequest {
    protected $client;
    private $pull_number;

    public function __construct(int $pull_number, ClientInterface $client) {
        $this->pull_number = $pull_number;
        $this->client      = $client;
    }

    public function merge() {
        return $this->client->merge_pull_request($this);
    }

    public function pull_number() {
        return $this->pull_number;
    }
}
