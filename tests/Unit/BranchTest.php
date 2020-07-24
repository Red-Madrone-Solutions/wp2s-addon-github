<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\Branch;

it('Can instantiate', function() {
    $branch = new Branch('node_id', 'url', 'ref');
    assertInstanceOf(Branch::class, $branch);
});

