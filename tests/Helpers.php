<?php


// phpcs:disable
namespace Tests;

// So that code doesn't exit
define('ABSPATH', __DIR__);

use RMS\WP2S\GitHub\File;

function setupTestFile($content = 'foo', $filename = '/tmp/index.html') {
    file_put_contents($filename, $content);
    return File::create($filename);
}

class TestFileMapper extends \RMS\WP2S\GitHub\FileMapper {
    protected function load_details(string $path_hash) {
        return [];
    }
}
// ..
