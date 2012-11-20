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
		$this->dropbox->setOAuthToken(null);
		$this->dropbox->setOAuthTokenSecret(null);

		$response = $this->dropbox->oAuthRequestToken();

		$this->assertArrayHasKey('oauth_token_secret', $response);
		$this->assertArrayHasKey('oauth_token', $response);
	}


	/**
	 * Tests Dropbox->oAuthAuthorizeURL()
	 */
	public function testGetAuthorizeURL()
	{
		$this->dropbox->setOAuthToken(null);
		$this->dropbox->setOAuthTokenSecret(null);

		$token_arr = $this->dropbox->oAuthRequestToken();

		$base_url = Dropbox::API_AUTH_URL . '/' . Dropbox::API_VERSION .
							'/oauth/authorize?';

		// test with no callback
		$params = array('oauth_token'=>$token_arr['oauth_token']);
		$expect_url = $base_url . http_build_query($params);
		$actual_url = $this->dropbox->oAuthAuthorizeURL($params['oauth_token']);
		$this->assertEquals($expect_url, $actual_url);

		// test with callback
		$params['oauth_callback'] = "http://localhost/foo/bar/baz";
		$expect_url = $base_url . http_build_query($params);
		$actual_url = $this->dropbox->oAuthAuthorizeURL(
		                              $params['oauth_token'],
		                              $params['oauth_callback']);
		$this->assertEquals($expect_url, $actual_url);
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
		$response1 = $this->dropbox->filesPost(BASE_PATH, realpath(__DIR__ . '/hah@.txt'));

		$response = $this->dropbox->filesGet(BASE_PATH . "hah@.txt");

		$this->assertArrayHasKey('content_type', $response);
		$this->assertArrayHasKey('data', $response);

		$this->dropbox->fileopsDelete(BASE_PATH . 'hah@.txt');
	}


	/**
	 * Tests Dropbox->filesGet()
	 */
	public function testFilesGetDollarSpace()
	{
		$response1 = $this->dropbox->filesPost(BASE_PATH, realpath(__DIR__ . '/hah $@.txt'));

		$response = $this->dropbox->filesGet(BASE_PATH . "hah $@.txt");

		$this->assertArrayHasKey('content_type', $response);
		$this->assertArrayHasKey('data', $response);

		$this->dropbox->fileopsDelete(BASE_PATH . 'hah $@.txt');
	}


	/**
	 * Tests Dropbox->filesGet()
	 */
	public function testFilesGetComma()
	{
		$response1 = $this->dropbox->filesPost(BASE_PATH, realpath(__DIR__ . '/hah, $@.txt'));

		$response = $this->dropbox->filesGet(BASE_PATH . "hah, $@.txt");

		$this->assertArrayHasKey('content_type', $response);
		$this->assertArrayHasKey('data', $response);

		$this->dropbox->fileopsDelete(BASE_PATH . 'hah, $@.txt');
	}


	/**
	 * Tests Dropbox->filesGet()
	 */
	public function testFilesGetMBName()
	{
		$this->dropbox->filesPost(BASE_PATH, realpath(__DIR__ . '/hàh@ $.txt'));

		$response = $this->dropbox->filesGet(BASE_PATH . 'hàh@ $.txt');

		$this->assertArrayHasKey('content_type', $response);
		$this->assertArrayHasKey('data', $response);

		$this->dropbox->fileopsDelete(BASE_PATH . 'hàh@ $.txt');
	}


	/**
	 * Tests Dropbox->filesPost()
	 */
	public function testFilesPost()
	{
		$this->assertTrue($this->dropbox->filesPost(BASE_PATH, realpath(__DIR__ . '/../dropbox.php')));
		$this->assertTrue($this->dropbox->filesPost(BASE_PATH, realpath(__DIR__ . '/with spaces.txt')));
		$this->assertTrue($this->dropbox->filesPost(BASE_PATH . 'with spaces', realpath(__DIR__ . '/with spaces.txt')));

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
		$this->dropbox->filesPost(BASE_PATH, realpath(__DIR__ . '/image.png'));

		$response = $this->dropbox->thumbnails(BASE_PATH . 'image.png');

		$this->assertArrayHasKey('content_type', $response);
		$this->assertArrayHasKey('data', $response);

		$this->dropbox->fileopsDelete(BASE_PATH . 'image.png');
	}


	/**
	 * Tests Dropbox->fileopsCopy()
	 */
	public function testFileopsCopy()
	{
		$this->dropbox->filesPost(BASE_PATH, realpath(__DIR__ . '/image.png'));

		$response = $this->dropbox->fileopsCopy(BASE_PATH . 'image.png', BASE_PATH . 'copy.png');

		$this->assertArrayHasKey('revision', $response);
		$this->assertArrayHasKey('modified', $response);
		$this->assertArrayHasKey('path', $response);

		// cleanup
		$this->dropbox->fileopsDelete(BASE_PATH . 'image.png');
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


	/**
	 * Tests Dropbox->fileopsDelete()
	 */
	public function testDeleteAllFiles()
	{
		$response = $this->dropbox->metadata(BASE_PATH);

		foreach($response['contents'] as $dbox_file) {
			$response = $this->dropbox->fileopsDelete($dbox_file['path']);
			$this->assertArrayHasKey('is_deleted', $response);
			$this->assertTrue($response['is_deleted']);
		}

	}

}

