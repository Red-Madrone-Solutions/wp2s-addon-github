<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\Deployer;

$temp_dir = null;
beforeAll(function() {
    global $temp_dir;
    $temp_dir = tempdir();
    setupFiles($temp_dir, 'files.json');
});

afterAll(function() {
    global $temp_dir;
    cleanupFiles($temp_dir);
});

it('Deploys', function() {
    global $temp_dir;
    $deployer = new Deployer();
    $deployer->setup($temp_dir, new TestFileMapper());
    $deployer->execute();
});
