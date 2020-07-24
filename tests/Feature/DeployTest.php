<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\Deployer;

$deploy_test_temp_dir = null;
beforeAll(function() {
    global $deploy_test_temp_dir;
    $deploy_test_temp_dir = tempdir();
    setupFiles($deploy_test_temp_dir, 'files.json');
});

afterAll(function() {
    global $deploy_test_temp_dir;
    cleanupFiles($deploy_test_temp_dir);
});

it('Deploys', function() {
    global $deploy_test_temp_dir;
    $_ENV['RMS_WP2S_GitHub_Encryption_Key'] = 'encryption key';
    $_ENV['RMS_WP2S_GitHub_Salt']           = 'salt';
    $option_set = new TestOptionSet();
    $client = new \RMS\WP2S\GitHub\Client($option_set, '\Tests\TestRequest');
    $deployer = new TestDeployer();
    $deployer->setup($deploy_test_temp_dir, new TestFileMapper(), $option_set, $client);
    $deployer->execute();
    assertEquals(3, $deployer->file_list()->count());
    assertEquals(0, $deployer->deployableFiles()->count());
    assertTrue($deployer->deployableFiles()->empty());
})->group('integration');
