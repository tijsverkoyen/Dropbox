<?php

require_once 'config.php';
require_once '../Dropbox.php';
require_once 'PHPUnit/Framework/TestCase.php';

use \TijsVerkoyen\Dropbox\Dropbox;

/**
 * Dropbox test case.
 */
class DropboxTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Dropbox
     */
    private $dropbox;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        // call parent
        parent::setUp();

        // create instance
        $this->dropbox = new Dropbox(APPLICATION_KEY, APPLICATION_SECRET);
        $this->dropbox->setOAuthToken(TOKEN);
        $this->dropbox->setOAuthTokenSecret(TOKEN_SECRET);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // unset instance
        $this->dropbox = null;

        // call parent
        parent::tearDown();
    }

    /**
     * Check if an item is a directory
     * @param $item
     */
    private function isDir($item)
    {
        $this->assertInternalType('array', $item);
        $this->assertArrayHasKey('size', $item);
        $this->assertInternalType('string', $item['size']);
        $this->assertArrayHasKey('hash', $item);
        $this->assertInternalType('string', $item['hash']);
        $this->assertArrayHasKey('bytes', $item);
        $this->assertInternalType('int', $item['bytes']);
        $this->assertArrayHasKey('thumb_exists', $item);
        $this->assertInternalType('bool', $item['thumb_exists']);
        $this->assertArrayHasKey('rev', $item);
        $this->assertArrayHasKey('modified', $item);
        $this->assertInternalType('string', $item['modified']);
        $this->assertArrayHasKey('path', $item);
        $this->assertInternalType('string', $item['path']);
        $this->assertArrayHasKey('is_dir', $item);
        $this->assertInternalType('bool', $item['is_dir']);
        $this->assertTrue($item['is_dir']);
        $this->assertArrayHasKey('icon', $item);
        $this->assertInternalType('string', $item['icon']);
        $this->assertArrayHasKey('root', $item);
        $this->assertInternalType('string', $item['root']);
        $this->assertArrayHasKey('revision', $item);
        $this->assertInternalType('int', $item['revision']);
    }

    /**
     * Check if an item is a file
     * @param $item
     */
    private function isFile($item)
    {
        $this->assertInternalType('array', $item);
        $this->assertArrayHasKey('size', $item);
        $this->assertInternalType('string', $item['size']);
        $this->assertArrayHasKey('rev', $item);
        $this->assertInternalType('string', $item['rev']);
        $this->assertArrayHasKey('thumb_exists', $item);
        $this->assertInternalType('bool', $item['thumb_exists']);
        $this->assertArrayHasKey('bytes', $item);
        $this->assertInternalType('int', $item['bytes']);
        $this->assertArrayHasKey('modified', $item);
        $this->assertInternalType('string', $item['modified']);
        $this->assertArrayHasKey('client_mtime', $item);
        $this->assertInternalType('string', $item['client_mtime']);
        $this->assertArrayHasKey('path', $item);
        $this->assertInternalType('string', $item['path']);
        $this->assertArrayHasKey('is_dir', $item);
        $this->assertInternalType('bool', $item['is_dir']);
        $this->assertFalse($item['is_dir']);
        $this->assertArrayHasKey('icon', $item);
        $this->assertInternalType('string', $item['icon']);
        $this->assertArrayHasKey('root', $item);
        $this->assertInternalType('string', $item['root']);
        $this->assertArrayHasKey('mime_type', $item);
        $this->assertInternalType('string', $item['mime_type']);
        $this->assertArrayHasKey('revision', $item);
        $this->assertInternalType('int', $item['revision']);
    }

    /**
     * Tests Dropbox->getTimeOut()
     */
    public function testGetTimeOut()
    {
        $this->dropbox->setTimeOut(5);
        $this->assertEquals(5, $this->dropbox->getTimeOut());
    }

    /**
     * Tests Dropbox->getUserAgent()
     */
    public function testGetUserAgent()
    {
        $this->dropbox->setUserAgent('testing/1.0.0');
        $this->assertEquals('PHP Dropbox/' . Dropbox::VERSION . ' testing/1.0.0' , $this->dropbox->getUserAgent());
    }

    /**
     * Tests Dropbox->oAuthRequestToken()
     */
    public function testOAuthRequestToken()
    {
        $this->dropbox->setOAuthToken('');
        $this->dropbox->setOAuthTokenSecret('');
        $response = $this->dropbox->oAuthRequestToken();
        $this->assertArrayHasKey('oauth_token_secret', $response);
        $this->assertArrayHasKey('oauth_token', $response);
    }

    /**
     * Tests Dropbox->accountInfo()
     */
    public function testAccountInfo()
    {
        $response = $this->dropbox->accountInfo();

        $this->assertArrayHasKey('referral_link', $response);
        $this->assertArrayHasKey('display_name', $response);
        $this->assertArrayHasKey('uid', $response);
        $this->assertArrayHasKey('email', $response);
    }

    /**
     * Tests Dropbox->filesGet()
     */
    public function testFilesGet()
    {
        $response = $this->dropbox->filesGet(BASE_PATH . 'hàh@, $.txt');
        $this->assertArrayHasKey('content_type', $response);
        $this->assertArrayHasKey('data', $response);
    }

    /**
     * Tests Dropbox->metadata()
     */
    public function testMetadata()
    {
        $response = $this->dropbox->metadata(BASE_PATH);
        $this->isDir($response);
    }

    /**
     * Tests Dropbox->delta()
     */
    public function testDelta()
    {
        $response = $this->dropbox->delta();
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('reset', $response);
        $this->assertInternalType('bool', $response['reset']);
        $this->assertArrayHasKey('cursor', $response);
        $this->assertArrayHasKey('has_more', $response);
        $this->assertInternalType('bool', $response['has_more']);
        $this->assertArrayHasKey('entries', $response);
        foreach ($response['entries'] as $row) {
            $this->assertInternalType('array', $row);
        }
    }

    /**
     * Tests Dropbox->revisions()
     */
    public function testRevisions()
    {
        $response = $this->dropbox->revisions(BASE_PATH .'hàh@, $.txt');
        $this->assertInternalType('array', $response);
        foreach ($response as $row) {
            $this->isFile($row);
        }
    }

    /**
     * Tests Dropbox->restore()
     */
    public function testRestore()
    {
        $response = $this->dropbox->restore(BASE_PATH .'hàh@, $.txt', '368c7df600088e34');
        $this->isFile($response);
    }

    /**
     * Tests Dropbox->search()
     */
    public function testSearch()
    {
        $response = $this->dropbox->search(BASE_PATH, 'txt');
        foreach ($response as $row) {
            $this->isFile($row);
        }
    }

    /**
     * Tests Dropbox->shares()
     */
    public function testShares()
    {
        $response = $this->dropbox->shares(BASE_PATH);
        $this->assertArrayHasKey('url', $response);
        $this->assertArrayHasKey('expires', $response);
    }

    /**
     * Tests Dropbox->thumbnails()
     */
    public function testThumbnails()
    {
        $response = $this->dropbox->thumbnails(BASE_PATH . 'image.png');

        $this->assertArrayHasKey('content_type', $response);
        $this->assertArrayHasKey('data', $response);
    }

    /**
     * Tests Dropbox->fileopsCopy()
     */
    public function testFileopsCopy()
    {
        $response = $this->dropbox->fileopsCopy(BASE_PATH . 'image.png', BASE_PATH . 'copy.png');
        $this->isFile($response);
        $this->dropbox->fileopsDelete(BASE_PATH . 'copy.png');
    }

    /**
     * Tests Dropbox->fileopsCreateFolder()
     */
    public function testFileopsCreateFolder()
    {
        $response = $this->dropbox->fileopsCreateFolder(BASE_PATH . 'created');
        $this->isDir($response);
        $this->dropbox->fileopsDelete(BASE_PATH . 'created');
    }

    /**
     * Tests Dropbox->fileopsMove()
     */
    public function testFileopsMove()
    {
        $this->dropbox->fileopsCreateFolder(BASE_PATH . 'will_be_moved');
        $response = $this->dropbox->fileopsMove(BASE_PATH . 'will_be_moved', BASE_PATH . 'moved');
        $this->isDir($response);
        $this->dropbox->fileopsDelete(BASE_PATH . 'moved');
    }
}
