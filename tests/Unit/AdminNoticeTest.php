<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\AdminNotice;

it('Can instantiate', function() {
    $admin_notice = new AdminNotice('message');
    assertInstanceOf(AdminNotice::class, $admin_notice);
});
