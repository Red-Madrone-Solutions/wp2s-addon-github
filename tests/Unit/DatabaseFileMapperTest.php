<?php

// phpcs:disable
namespace Test;

use RMS\WP2S\GitHub\DatabaseFileMapper;

it('Can instantiate', function() {
    $file_mapper = new DatabaseFileMapper();
    assertInstanceOf(DatabaseFileMapper::class, $file_mapper);
});
