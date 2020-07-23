<?php

// phpcs:disable
namespace Tests;

use RMS\WP2S\GitHub\File;
use RMS\WP2S\GitHub\DeployState;


$file_mapper = null;
$file_temp_dir;
beforeAll(function() {
    global $file_temp_dir, $file_mapper;
    $file_temp_dir = tempdir();
    $file_mapper = new TestFileMapper();
    File::setup('/tmp', $file_mapper);
});

beforeEach(function() {
    global $file_mapper;
    $file_mapper->clear_map();
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
    assertEquals(DeployState::LOCAL_ONLY, $file->state());
});

it('Calculates md5 for local content hash', function() {
    $content = 'Some sample content';
    $hash = md5($content);
    $file = setupTestFile($content);
    assertEquals($hash, $file->localContentHash());
});

it('Identifies a new file as needing update', function() {
    $file = setupTestFile();
    assertTrue($file->needsUpdate());
});

it('Calculates sha-256 for path hash', function() {
    $path = '/tmp/sample-path.txt';
    $hash = hash('sha256', $path);
    $file = setupTestFile('contents', $path);
    assertEquals($hash, $file->path_hash());
});

it('Identifies an existing file as not needing update', function() {
    $file = setupExistingTestFile();
    // debug("file: " . print_r($file, 1));
    assertFalse($file->needsUpdate());
});

it('Identifies an existing file that is updated as needing update', function() {
    $file = setupExistingTestFile();
    file_put_contents($file->file_path(), 'updated content');
    assertTrue($file->needsUpdate());
});

it('Updates data on store', function() {
    global $file_temp_dir;
    $contents = 'contents';
    $file_path = $file_temp_dir . '/update-test.txt';
    $file = setupTestFile($contents, $file_path);
    $sha = sha1('git hash');
    $file->stored($sha);
    assertEquals($sha                     , $file->sha());
    assertEquals(DeployState::BLOB_CREATED, $file->state());
    assertEquals(md5($contents)           , $file->storedContentHash());

    $file2 = TestFile::create($file_path);
    assertEquals($sha                     , $file2->sha());
    assertEquals(DeployState::BLOB_CREATED, $file2->state());
    assertEquals(md5($contents)           , $file2->storedContentHash());
});


it('Updates state on commit', function() {
    $file = setupTestFile();
    $sha = sha1('git hash');
    $file->stored($sha);
    $file->committed();
    assertEquals(DeployState::IN_COMMIT, $file->state());

    $file2 = TestFile::create($file->file_path());
    assertEquals(DeployState::IN_COMMIT, $file2->state());
});

it('Updates state on PR create', function() {
    $file = setupTestFile();
    $sha = sha1('git hash');
    $file->stored($sha);
    $file->committed();
    $file->pr_created();
    assertEquals(DeployState::IN_PULL_REQUEST, $file->state());

    $file2 = TestFile::create($file->file_path());
    assertEquals(DeployState::IN_PULL_REQUEST, $file2->state());
});

it('Updates state on PR merge', function() {
    $file = setupTestFile();
    $sha = sha1('git hash');
    $file->stored($sha);
    $file->committed();
    $file->pr_created();
    $file->pr_merged();
    assertEquals(DeployState::IN_TARGET_BRANCH, $file->state());

    $file2 = TestFile::create($file->file_path());
    assertEquals(DeployState::IN_TARGET_BRANCH, $file2->state());
});

it('Confirms blob does not exist for new file', function() {
    $file = setupTestFile();
    assertFalse($file->blob_exists());
});

it('Confirms blob exists for existing file', function() {
    $file = setupExistingTestFile();
    assertTrue($file->blob_exists());
});

