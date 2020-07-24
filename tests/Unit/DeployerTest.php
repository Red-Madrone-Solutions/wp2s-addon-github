<?php

// phpcs:disable

namespace Tests;

use RMS\WP2S\GitHub\Deployer;
use RMS\WP2S\GitHub\FileList;

$deployer_test_temp_dir = '/tmp';
$deployer = null;

beforeAll(function() {
});

beforeEach(function() {
    global $deployer_test_temp_dir, $deployer;
    $deployer_test_temp_dir = tempdir();
    file_put_contents($deployer_test_temp_dir . '/file1.html', 'file 1');
    file_put_contents($deployer_test_temp_dir . '/file2.html', 'file 2');
    $option_set = new TestOptionSet();
    $client = new \RMS\WP2S\GitHub\Client($option_set, '\Tests\TestRequest');
    $deployer = new TestDeployer();
    $deployer->setup($deployer_test_temp_dir, new TestFileMapper(), $option_set, $client);
    $deployer->build_file_list();
});

afterEach(function() {
    global $deployer_test_temp_dir;
    cleanupFiles($deployer_test_temp_dir);
});

it('Builds a file list', function() {
    global $deployer;
    assertInstanceOf(FileList::class, $deployer->file_list());
    assertEquals(2, $deployer->file_list()->count());
});

it('Returns a deployable files list', function() {
    global $deployer;
    $deployable_files = $deployer->deployableFiles();
    assertInstanceOf(FileList::class, $deployable_files);
    assertEquals(2, $deployable_files->count());
});
