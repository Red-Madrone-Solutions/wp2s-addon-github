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
    $_ENV['RMS_WP2S_GitHub_Encryption_Key'] = 'encryption key';
    $_ENV['RMS_WP2S_GitHub_Salt']           = 'salt';
    $deployer = new TestDeployer();
    $deployer->setup($temp_dir, new TestFileMapper(), new TestOptionSet(), new TestClient());
    $deployer->execute();
    assertEquals(3, $deployer->file_list()->count());
})->group('integration');
