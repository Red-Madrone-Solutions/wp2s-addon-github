<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\Client;


it('Can instantiate', function() {
    $client = new Client(new TestOptionSet());
    assertInstanceOf(Client::class, $client);
});

