<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\DryRun;

it('Can instantiate', function() {
    $dry_run = new DryRun('message');
    assertInstanceOf(DryRun::class, $dry_run);
});

