<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\FileList;
use RMS\WP2S\GitHub\File;

beforeAll(function() {
    File::setup('/tmp', new TestFileMapper());
});

it('Allows instantiation', function() {
    $file_list = new FileList();
    assertInstanceOf(FileList::class, $file_list);
});

it('Has no files initially', function() {
    $file_list = new FileList();
    assertEquals(0, $file_list->count());
});

it('Adds files', function() {
    $file_list = new FileList();
    $file = setupTestFile();
    $file_list->add($file);
    assertEquals(1, $file_list->count());
});

it('Is empty at first', function() {
    $file_list = new FileList();
    assertTrue($file_list->empty());
});
