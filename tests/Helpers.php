<?php


// phpcs:disable
namespace Tests;

// So that code doesn't exit
define('ABSPATH', __DIR__);

/**
 * Creates a random unique temporary directory, with specified parameters,
 * that does not already exist (like tempnam(), but for dirs).
 *
 * Created dir will begin with the specified prefix, followed by random
 * numbers.
 *
 * @link https://php.net/manual/en/function.tempnam.php
 *
 * @param string|null $dir Base directory under which to create temp dir.
 *     If null, the default system temp dir (sys_get_temp_dir()) will be
 *     used.
 * @param string $prefix String with which to prefix created dirs.
 * @param int $mode Octal file permission mask for the newly-created dir.
 *     Should begin with a 0.
 * @param int $maxAttempts Maximum attempts before giving up (to prevent
 *     endless loops).
 * @return string|bool Full path to newly-created dir, or false on failure.
 */
function tempdir($dir = null, $prefix = 'tmp_', $mode = 0700, $maxAttempts = 1000)
{
    /* Use the system temp dir by default. */
    if (is_null($dir))
    {
        $dir = sys_get_temp_dir();
    }

    /* Trim trailing slashes from $dir. */
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);

    /* If we don't have permission to create a directory, fail, otherwise we will
     * be stuck in an endless loop.
     */
    if (!is_dir($dir) || !is_writable($dir))
    {
        return false;
    }

    /* Make sure characters in prefix are safe. */
    if (strpbrk($prefix, '\\/:*?"<>|') !== false)
    {
        return false;
    }

    /* Attempt to create a random directory until it works. Abort if we reach
     * $maxAttempts. Something screwy could be happening with the filesystem
     * and our loop could otherwise become endless.
     */
    $attempts = 0;
    do
    {
        $path = sprintf('%s%s%s%s', $dir, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
    } while (
        !mkdir($path, $mode) &&
        $attempts++ < $maxAttempts
    );

    return $path;
}

function setupFiles($temp_dir) {
    $fixtures_dir = DIRNAME(__FILE__) . '/Fixtures';
    $data_dir = $fixtures_dir . '/data/';
    $dh = opendir($data_dir);
    while ( ($file = readdir($dh)) !== false ) {
        if ( $file === '.' || $file === '..' ) {
            continue;
        }
        copy($data_dir . $file, $temp_dir . '/' . $file);
    }
    closedir($dh);
}

function cleanupFiles($temp_dir) {
    $dh = opendir($temp_dir);
    while ( ($file = readdir($dh)) !== false ) {
        if ( $file === '.' || $file === '..' ) {
            continue;
        }
        unlink($temp_dir . '/' . $file);
    }
    rmdir($temp_dir);
}

use RMS\WP2S\GitHub\DeployState;

function setupTestFile($content = 'foo', $filename = '/tmp/index.html') {
    file_put_contents($filename, $content);
    return TestFile::create($filename);
}

function setupExistingTestFile($content = 'existing content', $filename = '/tmp/existing_file.txt') {
    return setupTestFile($content, $filename);
}

function debug($msg) {
    die($msg);
}

class TestFileMapper extends \RMS\WP2S\GitHub\FileMapper {
    protected function load_details(string $path_hash) {
        if ( $path_hash == hash('sha256', '/tmp/existing_file.txt') ) {
            return [
                'sha'          => hash('sha256', 'git sha'),
                'content_hash' => md5('existing content'),
                'state'        => DeployState::IN_TARGET_BRANCH,
            ];
        }
        return [];
    }

    protected function set_details(
        string $path_hash,
        array $params
    ) {
        // Stub
    }

    public function clear_map() {
        $this->map = [];
    }
}

class TestFile extends \RMS\WP2S\GitHub\File {
    public function file_path() {
        return $this->file_path;
    }
}

class TestDeployer extends \RMS\WP2S\GitHub\Deployer {
    public function build_file_list() {
        parent::build_file_list();
    }

    public function file_list() {
        return $this->file_list;
    }

    public function deployableFiles() : \RMS\WP2S\GitHub\FileList {
        return parent::deployableFiles();
    }
}
// ..
