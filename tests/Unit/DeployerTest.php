<?php

// phpcs:disable

namespace Tests;

use RMS\WP2S\GitHub\Deployer;
use RMS\WP2S\GitHub\FileList;

$temp_dir = '/tmp';
$deployer = null;

beforeAll(function() {
    global $temp_dir;
    $temp_dir = tempdir();
    TestFile::setup($temp_dir, new TestFileMapper);
});

beforeEach(function() {
    global $temp_dir, $deployer;
    file_put_contents($temp_dir . '/file1.html', 'file 1');
    file_put_contents($temp_dir . '/file2.html', 'file 2');
    $deployer = new TestDeployer();
    $deployer->setup($temp_dir);
    $deployer->build_file_list();
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
