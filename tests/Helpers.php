<?php


// phpcs:disable
namespace Tests;

// So that code doesn't exit
define('ABSPATH', __DIR__);

\WP_Mock::bootstrap();

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

class TestOptionSet extends \RMS\WP2S\GitHub\OptionSet {
    public function __construct($load_from_db = false, $data = []) {
        parent::__construct(false, $data);
    }

    public function findByName($name) {
        if ( $name === 'personal_access_token' ) {
            return new \RMS\WP2S\GitHub\Option($name, 'Test Personal Access Token');
        }
        return parent::findByName($name);
    }
}

class TestUtil {
    public static function randomSha() {
        return sha1(rand(1,99));
    }
}

class TestPullRequest extends \RMS\WP2S\GitHub\PullRequest {
}

class TestBranch extends \RMS\WP2S\GitHub\Branch {
}

class TestRequest {
    private $url;
    private $type;
    private $body;

    public function __construct($token, $url, $type = 'GET') {
        $this->url = $url;
        $this->type = $type;
        $this->body = null;
    }

    public function body($body) {
        $this->body = $body;
    }

    public function exec() {
        $response = new TestResponse();
        $response->body($this->getResponseBody());
        return $response;
    }

    private function getResponseBody() {
        // create_blob
        if ( preg_match('|/blobs$|', $this->url) ) {
            $sha = TestUtil::randomSha();
            return json_encode([
                'url' => sprintf(
                    'https://api.github.com/repos/%s/%s/git/blobs/%s',
                    'account',
                    'repo',
                    $sha
                ),
                'sha' => $sha,
            ]);
        }

        // get_latest_commit_hash
        elseif ( preg_match('|/refs/heads$|', $this->url) ) {
            return '[
                {
                    "ref": "refs/heads/master",
                    "node_id": "MDM6UmVmMjU5Nzc3MTU5OnJlZnMvaGVhZHMvbWFzdGVy",
                    "url": "https://api.github.com/repos/Red-Madrone-Solutions/wp2s-addon-github-static/git/refs/heads/master",
                    "object": {
                        "sha": "548ce6a6742cab21d877eada746e4ce5d432d9ac",
                        "type": "commit",
                        "url": "https://api.github.com/repos/Red-Madrone-Solutions/wp2s-addon-github-static/git/commits/548ce6a6742cab21d877eada746e4ce5d432d9ac"
                    }
                },
                {
                    "ref": "refs/heads/production",
                    "node_id": "MDM6UmVmMjU5Nzc3MTU5OnJlZnMvaGVhZHMvcHJvZHVjdGlvbg==",
                    "url": "https://api.github.com/repos/Red-Madrone-Solutions/wp2s-addon-github-static/git/refs/heads/production",
                    "object": {
                        "sha": "83db88575a87b57cd0ffd75ff174f023113e73ff",
                        "type": "commit",
                        "url": "https://api.github.com/repos/Red-Madrone-Solutions/wp2s-addon-github-static/git/commits/83db88575a87b57cd0ffd75ff174f023113e73ff"
                    }
                },
                {
                    "ref": "refs/heads/rms-wp2s-gh-deploy-branch-1589239953",
                    "node_id": "MDM6UmVmMjU5Nzc3MTU5OnJlZnMvaGVhZHMvcm1zLXdwMnMtZ2gtZGVwbG95LWJyYW5jaC0xNTg5MjM5OTUz",
                    "url": "https://api.github.com/repos/Red-Madrone-Solutions/wp2s-addon-github-static/git/refs/heads/rms-wp2s-gh-deploy-branch-1589239953",
                    "object": {
                        "sha": "b91d895cc46365c0d3f3b5e5db84141adacf8c20",
                        "type": "commit",
                        "url": "https://api.github.com/repos/Red-Madrone-Solutions/wp2s-addon-github-static/git/commits/b91d895cc46365c0d3f3b5e5db84141adacf8c20"
                    }
                }
              ]';
        }

        // create_branch
        elseif ( preg_match('|/git/refs$|', $this->url) && $this->isPost() ) {
            return json_encode([
                'ref'     => 'refs/heads/featureA',
                'node_id' => 'MDM6UmVmcmVmcy9oZWFkcy9mZWF0dXJlQQ==',
                'url'     => 'https://api.github.com/repos/octocat/Hello-World/git/refs/heads/featureA',
                'object' => [
                    'sha' => TestUtil::randomSha(),
                ],
            ]);
        }

        // create_tree
        elseif ( preg_match('|/git/trees$|', $this->url) && $this->isPost() ) {
            $tree_hash = TestUtil::randomSha();
            return json_encode([
                'sha' => $tree_hash,
                'url' => sprintf(
                    'https://api.github.com/repos/octocat/Hello-World/trees/%s',
                    $tree_hash
                ),
            ]);
        }

        // create_commit
        elseif ( preg_match('|/git/commits$|', $this->url) && $this->isPost() ) {
            $commit_hash = TestUtil::randomSha();
            return json_encode([
                'sha'     => $commit_hash,
                'node_id' => 'MDY6Q29tbWl0NzYzODQxN2RiNmQ1OWYzYzQzMWQzZTFmMjYxY2M2MzcxNTU2ODRjZA==',
                'url'     => sprintf(
                    'https://api.github.com/repos/octocat/Hello-World/git/commits/%s',
                    $commit_hash
                ),

            ]);
        }

        // update_reference
        elseif ( preg_match('|/git/refs/|', $this->url) && $this->isPatch() ) {
            $commit_hash = TestUtil::randomSha();
            return json_encode([
                'ref' => 'refs/heads/featureA',
                'node_id' => 'MDM6UmVmcmVmcy9oZWFkcy9mZWF0dXJlQQ==',
                'url' => 'https://api.github.com/repos/octocat/Hello-World/git/refs/heads/featureA',
                'object' => [
                    'type' => 'commit',
                    'sha' => $commit_hash,
                    'url' => sprintf(
                        'https://api.github.com/repos/octocat/Hello-World/git/commits/%s',
                        $commit_hash
                    ),
                ]
            ]);
        }

        // create_pull_request
        elseif ( preg_match('|/pulls$|', $this->url) && $this->isPost() ) {
            return json_encode([
                'number' => 1234,
            ]);
        }
    }

    private function isPost() {
        return $this->type === 'POST';
    }

    private function isPatch() {
        return $this->type === 'PATCH';
    }
}

class TestResponse extends \RMS\WP2S\GitHub\Response {
    protected $status_code = 200;
}
// ..
