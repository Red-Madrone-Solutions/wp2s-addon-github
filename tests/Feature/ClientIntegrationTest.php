<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\Client;

$option_set;
$client_integration_test_temp_dir;

beforeAll(function() {
    global $option_set;
    $option_set = new TestOptionSet();

    $_ENV['RMS_WP2S_GitHub_Encryption_Key'] = 'encryption key';
    $_ENV['RMS_WP2S_GitHub_Salt']           = 'salt';
});

beforeEach(function() {
    global $client_integration_test_temp_dir;
    $client_integration_test_temp_dir = tempdir();
    $file_mapper = new TestFileMapper();
    \RMS\WP2S\GitHub\File::setup($client_integration_test_temp_dir, $file_mapper);
});

afterEach(function() {
    global $client_integration_test_temp_dir;
    cleanupFiles($client_integration_test_temp_dir);
});

it('Sets state on blob create', function() {
    global $option_set, $client_integration_test_temp_dir;
    $client = new Client($option_set, '\Tests\TestRequest');
    $file = setupTestFile('content', $client_integration_test_temp_dir . '/test.txt');
    assertTrue($file->needsUpdate());
    $client->create_blob($file);
    assertNotEmpty($file->sha());
    assertNotEmpty($file->storedContentHash());
    assertFalse($file->needsUpdate());
});
