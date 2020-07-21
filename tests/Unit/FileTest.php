<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\File;
use RMS\WP2S\GitHub\FileStatus;

beforeAll(function() {
    File::setup('/tmp', new TestFileMapper);
});

it('asserts true is true', function() {
    assertTrue(true);
});

it('Can create file', function() {
    $file = setupTestFile();
    assertInstanceOf(File::class, $file);
});

it('Returns null for non-existing file', function() {
    $file = File::create('/tmp/no-index.html');
    assertNull($file);
});

it('Has no `sha` by default', function() {
    $file = setupTestFile();
    assertEmpty($file->sha());
});

it('Has no `stored_content_hash` by default', function() {
    $file = setupTestFile();
    assertEmpty($file->storedContentHash());
});

it('Has local file state by default', function() {
    $file = setupTestFile();
    assertEquals(FileStatus::LOCAL_ONLY, $file->state());
});

it('Calculates sha-256 for local content hash', function() {
    $content = 'Some sample content';
    $hash = hash('sha256', $content);
    $file = setupTestFile($content);
    assertEquals($hash, $file->localContentHash());
});

