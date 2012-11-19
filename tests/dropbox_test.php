<?php

require_once 'config.php';
require_once '../dropbox.php';

require_once 'PHPUnit/Framework/TestCase.php';

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
		parent::setUp();

		$this->dropbox = new Dropbox(APPLICATION_KEY, APPLICATION_SECRET);
		$this->dropbox->setOAuthToken(TOKEN);
		$this->dropbox->setOAuthTokenSecret(TOKEN_SECRET);
	}


	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->dropbox = null;

		parent::tearDown();
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
	 * Tests Dropbox->filesPost()
	 */
	public function testFilesPost()
	{
		$this->assertTrue($this->dropbox->filesPost(BASE_PATH, realpath('../dropbox.php')));
		$this->assertTrue($this->dropbox->filesPost(BASE_PATH, realpath('./with spaces.txt')));
		$this->assertTrue($this->dropbox->filesPost(BASE_PATH . 'with spaces', realpath('./with spaces.txt')));

		// cleanup
		$this->dropbox->fileopsDelete(BASE_PATH . 'dropbox.php');
		$this->dropbox->fileopsDelete(BASE_PATH . 'with spaces.txt');
		$this->dropbox->fileopsDelete(BASE_PATH . 'with spaces/with spaces.txt');
	}


	/**
	 * Tests Dropbox->metadata()
	 */
	public function testMetadata()
	{
		$response = $this->dropbox->metadata(BASE_PATH);

		$this->assertArrayHasKey('hash', $response);
		$this->assertArrayHasKey('revision', $response);
		$this->assertArrayHasKey('modified', $response);
		$this->assertArrayHasKey('path', $response);
		$this->assertArrayHasKey('contents', $response);
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

		$this->assertArrayHasKey('revision', $response);
		$this->assertArrayHasKey('modified', $response);
		$this->assertArrayHasKey('path', $response);

		// cleanup
		$this->dropbox->fileopsDelete(BASE_PATH . 'copy.png');
	}


	/**
	 * Tests Dropbox->fileopsCreateFolder()
	 */
	public function testFileopsCreateFolder()
	{
		$response = $this->dropbox->fileopsCreateFolder(BASE_PATH . 'created');

		$this->assertArrayHasKey('revision', $response);
		$this->assertArrayHasKey('modified', $response);
		$this->assertArrayHasKey('path', $response);

		// cleanup
		$this->dropbox->fileopsDelete(BASE_PATH . 'created');
	}


	/**
	 * Tests Dropbox->fileopsMove()
	 */
	public function testFileopsMove()
	{
		$this->dropbox->fileopsCreateFolder(BASE_PATH . 'will_be_moved');

		$response = $this->dropbox->fileopsMove(BASE_PATH . 'will_be_moved', BASE_PATH . 'moved');

		$this->assertArrayHasKey('hash', $response);
		$this->assertArrayHasKey('revision', $response);
		$this->assertArrayHasKey('modified', $response);
		$this->assertArrayHasKey('path', $response);

		// cleanup
		$this->dropbox->fileopsDelete(BASE_PATH . 'moved');
	}
}

