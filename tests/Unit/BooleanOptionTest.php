<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\BooleanOption;

it('Can instantiate', function() {
    $boolean_option = new BooleanOption('name', 'label');
    assertInstanceOf(BooleanOption::class, $boolean_option);
});

