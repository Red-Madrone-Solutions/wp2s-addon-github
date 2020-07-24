<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\Controller;

it('Can instantiate', function() {
    $controller = new Controller();
    assertInstanceOf(Controller::class, $controller);
});

