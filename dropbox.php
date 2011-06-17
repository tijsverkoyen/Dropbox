<?php

/**
 * Dropbox class
 *
 * This source file can be used to communicate with Dropbox (http://dropbox.com)
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them. Reporting can be done by sending an email to php-dropbox-bugs[at]verkoyen[dot]eu.
 * If you report a bug, make sure you give me enough information (include your code).
 *
 * Changelog since 1.0.5
 * - Fixed filesPost so it can handle folders with spaces.
 *
 * Changelog since 1.0.4
 * - Fixed filesPost so it returns a boolean.
 * - Some code styling
 *
 * Changelog since 1.0.3
 * - Corrected the authorize-URL (thx to Jacob Budin).
 *
 * Changelog since 1.0.2
 * - Added methods to enable oauth-usage.
 *
 * Changelog since 1.0.1
 * - Bugfix: when doing multiple calles where GET and POST is mixed, the postfields should be reset (thx to Daniel Hüsken)
 *
 * Changelog since 1.0.0
 * - fixed some issues with generation off the basestring
 *
 * License
 * Copyright (c), Tijs Verkoyen. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author		Tijs Verkoyen <php-dropbox@verkoyen.eu>
 * @version		1.0.6
 *
 * @copyright	Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license		BSD License
 */
class Dropbox
{
	// internal constant to enable/disable debugging
	const DEBUG = false;

	// url for the dropbox-api
	const API_URL = 'https://api.dropbox.com';
	const API_AUTH_URL = 'https://www.dropbox.com';
	const API_CONTENT_URL = 'https://api-content.dropbox.com';

	// port for the dropbox-api
	const API_PORT = 443;

	// current version
	const VERSION = '1.0.6';


	/**
	 * A cURL instance
	 *
	 * @var	resource
	 */
	private $curl;


	/**
	 * The application key
	 *
	 * @var	string
	 */
	private $applicationKey;


	/**
	 * The application secret
	 *
	 * @var	string
	 */
	private $applicationSecret;


	/**
	 * The oAuth-token
	 *
	 * @var	string
	 */
	private $oAuthToken = '';


	/**
	 * The oAuth-token-secret
	 *
	 * @var	string
	 */
	private $oAuthTokenSecret = '';


	/**
	 * The timeout
	 *
	 * @var	int
	 */
	private $timeOut = 60;


	/**
	 * The user agent
	 *
	 * @var	string
	 */
	private $userAgent;


// class methods
	/**
	 * Default constructor
	 *
	 * @return	void
	 * @param	string $applicationKey		The application key to use.
	 * @param	string $applicationSecret	The application secret to use.
	 */
	public function __construct($applicationKey, $applicationSecret)
	{
		$this->setApplicationKey($applicationKey);
		$this->setApplicationSecret($applicationSecret);
	}


	/**
	 * Default destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if($this->curl != null) curl_close($this->curl);
	}


	/**
	 * Format the parameters as a querystring
	 *
	 * @return	string
	 * @param	array $parameters	The parameters to pass.
	 */
	private function buildQuery(array $parameters)
	{
		// no parameters?
		if(empty($parameters)) return '';

		// encode the keys
		$keys = self::urlencode_rfc3986(array_keys($parameters));

		// encode the values
		$values = self::urlencode_rfc3986(array_values($parameters));

		// reset the parameters
		$parameters = array_combine($keys, $values);

		// sort parameters by key
		uksort($parameters, 'strcmp');

		// loop parameters
		foreach($parameters as $key => $value)
		{
			// sort by value
			if(is_array($value)) $parameters[$key] = natsort($value);
		}

		// process parameters
		foreach($parameters as $key => $value) $chunks[] = $key . '=' . str_replace('%25', '%', $value);

		// return
		return implode('&', $chunks);
	}


	/**
	 * All OAuth 1.0 requests use the same basic algorithm for creating a signature base string and a signature.
	 * The signature base string is composed of the HTTP method being used, followed by an ampersand ("&") and then the URL-encoded base URL being accessed,
	 * complete with path (but not query parameters), followed by an ampersand ("&").
	 * Then, you take all query parameters and POST body parameters (when the POST body is of the URL-encoded type, otherwise the POST body is ignored),
	 * including the OAuth parameters necessary for negotiation with the request at hand, and sort them in lexicographical order by first parameter name and
	 * then parameter value (for duplicate parameters), all the while ensuring that both the key and the value for each parameter are URL encoded in isolation.
	 * Instead of using the equals ("=") sign to mark the key/value relationship, you use the URL-encoded form of "%3D". Each parameter is then joined by the
	 * URL-escaped ampersand sign, "%26".
	 *
	 * @return	string
	 * @param	string $url			The url to use.
	 * @param	string $method		The method that will be called.
	 * @param	array $parameters	The parameters to pass.
	 */
	private function calculateBaseString($url, $method, array $parameters)
	{
		// redefine
		$url = str_replace('%20', ' ', (string) $url);
		$parameters = (array) $parameters;

		// init var
		$pairs = array();
		$chunks = array();

		// sort parameters by key
		uksort($parameters, 'strcmp');

		// loop parameters
		foreach($parameters as $key => $value)
		{
			// sort by value
			if(is_array($value)) $parameters[$key] = natsort($value);
		}

		// process queries
		foreach($parameters as $key => $value)
		{
			// only add if not already in the url
			if(substr_count($url, $key . '=' . $value) == 0) $chunks[] = self::urlencode_rfc3986($key) . '%3D' . self::urlencode_rfc3986($value);
		}

		$urlChunks = explode('/', $url);
		$i = 0;

		foreach($urlChunks as &$chunk)
		{
			if($i > 4) $chunk = self::urlencode_rfc3986($chunk);
			else $chunk = urlencode($chunk);

			$i++;

		}

		// build base
		$base = $method . '&';
		$base .= implode('%2F', $urlChunks);
		$base .= (substr_count($url, '?')) ? '%26' : '&';
		$base .= implode('%26', $chunks);
		$base = str_replace(array('%3F', '%20'), array('&', '%2520'), $base);

		// return
		return $base;
	}


	/**
	 * Build the Authorization header
	 * @later: fix me
	 *
	 * @return	string
	 * @param	array $parameters	The parameters to pass.
	 * @param	string $url			The url to use.
	 */
	private function calculateHeader(array $parameters, $url)
	{
		// redefine
		$url = (string) $url;

		// divide into parts
		$parts = parse_url($url);

		// init var
		$chunks = array();

		// process queries
		foreach($parameters as $key => $value) $chunks[] = str_replace('%25', '%', self::urlencode_rfc3986($key) . '="' . self::urlencode_rfc3986($value) . '"');

		// build return
		$return = 'Authorization: OAuth realm="' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '", ';
		$return .= implode(',', $chunks);

		// prepend name and OAuth part
		return $return;
	}


	/**
	 * Make an call to the oAuth
	 * @todo	refactor me
	 *
	 * @return	array
	 * @param	string $url						The url that has to be called.
	 * @param	array[optional] $parameters		The parameters to pass.
	 * @param	string[optional] $method		Which HTTP-method should we use? Possible values are POST, GET.
	 * @param	bool[optional] $expectJSON		Do we expect JSON?
	 */
	private function doOAuthCall($url, array $parameters = null, $method = 'POST', $expectJSON = true)
	{
		// redefine
		$url = (string) $url;

		// append default parameters
		$parameters['oauth_consumer_key'] = $this->getApplicationKey();
		$parameters['oauth_nonce'] = md5(microtime() . rand());
		$parameters['oauth_timestamp'] = time();
		$parameters['oauth_signature_method'] = 'HMAC-SHA1';
		$parameters['oauth_version'] = '1.0';

		// calculate the base string
		$base = $this->calculateBaseString(self::API_URL . '/' . $url, 'POST', $parameters);

		// add sign into the parameters
		$parameters['oauth_signature'] = $this->hmacsha1($this->getApplicationSecret() . '&' . $this->getOAuthTokenSecret(), $base);

		// calculate header
		$header = $this->calculateHeader($parameters, self::API_URL . '/' . $url);

		if($method == 'POST')
		{
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $this->buildQuery($parameters);
		}

		else
		{
			// reset post
			$options[CURLOPT_POST] = 0;
			unset($options[CURLOPT_POSTFIELDS]);

			// add the parameters into the querystring
			if(!empty($parameters)) $url .= '?' . $this->buildQuery($parameters);
		}

		// set options
		$options[CURLOPT_URL] = self::API_URL . '/' . $url;
		$options[CURLOPT_PORT] = self::API_PORT;
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		if(ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) $options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_TIMEOUT] = (int) $this->getTimeOut();
		$options[CURLOPT_SSL_VERIFYPEER] = false;
		$options[CURLOPT_SSL_VERIFYHOST] = false;
		$options[CURLOPT_HTTPHEADER] = array('Expect:');

		// init
		$this->curl = curl_init();

		// set options
		curl_setopt_array($this->curl, $options);

		// execute
		$response = curl_exec($this->curl);
		$headers = curl_getinfo($this->curl);

		// fetch errors
		$errorNumber = curl_errno($this->curl);
		$errorMessage = curl_error($this->curl);

		// error?
		if($errorNumber != '') throw new DropboxException($errorMessage, $errorNumber);

		// return
		if($expectJSON) return json_decode($response, true);

		// fallback
		return $response;
	}


	/**
	 * Make the call
	 *
	 * @return	string
	 * @param	string $url						The url to call.
	 * @param	array[optional] $parameters		Optional parameters.
	 * @param	bool[optional] $method			The method to use. Possible values are GET, POST.
	 * @param	string[optional] $filePath		The path to the file to upload.
	 * @param	bool[optional] $expectJSON		Do we expect JSON?
	 * @param	bool[optional] $isContent		Is this content?
	 */
	private function doCall($url, array $parameters = null, $method = 'GET', $filePath = null, $expectJSON = true, $isContent = false)
	{
		// allowed methods
		$allowedMethods = array('GET', 'POST');

		// redefine
		$url = (string) $url;
		$parameters = (array) $parameters;
		$method = (string) $method;
		$expectJSON = (bool) $expectJSON;

		// validate method
		if(!in_array($method, $allowedMethods)) throw new DropboxException('Unknown method (' . $method . '). Allowed methods are: ' . implode(', ', $allowedMethods));

		// append default parameters
		$oauth['oauth_consumer_key'] = $this->getApplicationKey();
		$oauth['oauth_nonce'] = md5(microtime() . rand());
		$oauth['oauth_timestamp'] = time();
		$oauth['oauth_token'] = $this->getOAuthToken();
		$oauth['oauth_signature_method'] = 'HMAC-SHA1';
		$oauth['oauth_version'] = '1.0';

		// set data
		$data = $oauth;
		if(!empty($parameters))
		{
			// convert to UTF-8
			foreach($parameters as &$value) $value = utf8_encode($value);

			// merge
			$data = array_merge($data, $parameters);
		}

		if($filePath != null)
		{
			// process file
			$fileInfo = pathinfo($filePath);

			// add to the data
			$data['file'] = $fileInfo['basename'];

		}

		// calculate the base string
		if($isContent) $base = $this->calculateBaseString(self::API_CONTENT_URL . '/' . $url, $method, $data);
		else $base = $this->calculateBaseString(self::API_URL . '/' . $url, $method, $data);

		// based on the method, we should handle the parameters in a different way
		if($method == 'POST')
		{
			// file provided?
			if($filePath != null)
			{
				// build a boundary
				$boundary = md5(time());

				// init var
				$content = '--' . $boundary . "\r\n";

				// set file
				$content .= 'Content-Disposition: form-data; name=file; filename="' . rawurldecode($fileInfo['basename']) . '"' . "\r\n";
				$content .= 'Content-Type: application/octet-stream' . "\r\n";
				$content .= "\r\n";
				$content .= file_get_contents($filePath);
				$content .= "\r\n";
				$content .= "--" . $boundary . '--';

				// build headers
				$headers[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;
				$headers[] = 'Content-Length: ' . strlen($content);

				// set content
				$options[CURLOPT_POSTFIELDS] = $content;
			}

			// no file
			else $options[CURLOPT_POSTFIELDS] = $this->buildQuery($parameters);

			// enable post
			$options[CURLOPT_POST] = 1;
		}

		else
		{
			// reset post
			$options[CURLOPT_POST] = 0;
			unset($options[CURLOPT_POSTFIELDS]);

			// add the parameters into the querystring
			if(!empty($parameters)) $url .= '?' . $this->buildQuery($parameters);
		}

		// add sign into the parameters
		$oauth['oauth_signature'] = $this->hmacsha1($this->getApplicationSecret() . '&' . $this->getOAuthTokenSecret(), $base);

		if($isContent) $headers[] = $this->calculateHeader($oauth, self::API_CONTENT_URL . '/' . $url);
		else $headers[] = $this->calculateHeader($oauth, self::API_URL . '/' . $url);
		$headers[] = 'Expect:';

		// set options
		if($isContent) $options[CURLOPT_URL] = self::API_CONTENT_URL . '/' . $url;
		else $options[CURLOPT_URL] = self::API_URL . '/' . $url;
		$options[CURLOPT_PORT] = self::API_PORT;
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		if(ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) $options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_TIMEOUT] = (int) $this->getTimeOut();
		$options[CURLOPT_SSL_VERIFYPEER] = false;
		$options[CURLOPT_SSL_VERIFYHOST] = false;
		$options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
		$options[CURLOPT_HTTPHEADER] = $headers;

		// init
		if($this->curl == null) $this->curl = curl_init();

		// set options
		curl_setopt_array($this->curl, $options);

		// execute
		$response = curl_exec($this->curl);
		$headers = curl_getinfo($this->curl);

		// fetch errors
		$errorNumber = curl_errno($this->curl);
		$errorMessage = curl_error($this->curl);

		if(!$expectJSON && $isContent)
		{
			// is it JSON?
			$json = @json_decode($response, true);
			if($json !== false && isset($json['error'])) throw new DropboxException($json['error']);

			// set return
			$return['content_type'] = $headers['content_type'];
			$return['data'] = base64_encode($response);

			// return
			return $return;
		}

		// we don't expect JSON, return the response
		if(!$expectJSON) return $response;

		// replace ids with their string values, added because of some PHP-version can't handle these large values
		$response = preg_replace('/id":(\d+)/', 'id":"\1"', $response);

		// we expect JSON, so decode it
		$json = @json_decode($response, true);

		// validate JSON
		if($json === null)
		{
			// should we provide debug information
			if(self::DEBUG)
			{
				// make it output proper
				echo '<pre>';

				// dump the header-information
				var_dump($headers);

				// dump the error
				var_dump($errorMessage);

				// dump the raw response
				var_dump($response);

				// end proper format
				echo '</pre>';
			}

			// throw exception
			throw new DropboxException('Invalid response.');
		}

		// any error
		if(isset($json['error']))
		{
			// should we provide debug information
			if(self::DEBUG)
			{
				// make it output proper
				echo '<pre>';

				// dump the header-information
				var_dump($headers);

				// dump the raw response
				var_dump($response);

				// end proper format
				echo '</pre>';
			}

			if(isset($json['error']) && is_string($json['error'])) $message = $json['error'];
			elseif(isset($json['error']['hash']) && $json['error']['hash'] != '') $message = (string) $json['error']['hash'];
			else $message = 'Invalid response.';

			// throw exception
			throw new DropboxException($message);
		}

		// return
		return $json;
	}


	/**
	 * Get the application key
	 *
	 * @return	string
	 */
	private function getApplicationKey()
	{
		return $this->applicationKey;
	}


	/**
	 * Get the application secret
	 *
	 * @return	string
	 */
	private function getApplicationSecret()
	{
		return $this->applicationSecret;
	}


	/**
	 * Get the oAuth-token
	 *
	 * @return	string
	 */
	private function getOAuthToken()
	{
		return $this->oAuthToken;
	}


	/**
	 * Get the oAuth-token-secret
	 *
	 * @return	string
	 */
	private function getOAuthTokenSecret()
	{
		return $this->oAuthTokenSecret;
	}


	/**
	 * Get the timeout
	 *
	 * @return	int
	 */
	public function getTimeOut()
	{
		return (int) $this->timeOut;
	}


	/**
	 * Get the useragent that will be used. Our version will be prepended to yours.
	 * It will look like: "PHP Dropbox/<version> <your-user-agent>"
	 *
	 * @return	string
	 */
	public function getUserAgent()
	{
		return (string) 'PHP Dropbox/' . self::VERSION . ' ' . $this->userAgent;
	}


	/**
	 * Set the application key
	 *
	 * @return	void
	 * @param	string $key		The application key to use.
	 */
	private function setApplicationKey($key)
	{
		$this->applicationKey = (string) $key;
	}


	/**
	 * Set the application secret
	 *
	 * @return	void
	 * @param	string $secret	The application secret to use.
	 */
	private function setApplicationSecret($secret)
	{
		$this->applicationSecret = (string) $secret;
	}


	/**
	 * Set the oAuth-token
	 *
	 * @return	void
	 * @param	string $token	The token to use.
	 */
	public function setOAuthToken($token)
	{
		$this->oAuthToken = (string) $token;
	}


	/**
	 * Set the oAuth-secret
	 *
	 * @return	void
	 * @param	string $secret	The secret to use.
	 */
	public function setOAuthTokenSecret($secret)
	{
		$this->oAuthTokenSecret = (string) $secret;
	}


	/**
	 * Set the timeout
	 *
	 * @return	void
	 * @param	int $seconds	The timeout in seconds.
	 */
	public function setTimeOut($seconds)
	{
		$this->timeOut = (int) $seconds;
	}


	/**
	 * Get the useragent that will be used. Our version will be prepended to yours.
	 * It will look like: "PHP Dropbox/<version> <your-user-agent>"
	 *
	 * @return	void
	 * @param	string $userAgent	Your user-agent, it should look like <app-name>/<app-version>.
	 */
	public function setUserAgent($userAgent)
	{
		$this->userAgent = (string) $userAgent;
	}


	/**
	 * Build the signature for the data
	 *
	 * @return	string
	 * @param	string $key		The key to use for signing.
	 * @param	string $data	The data that has to be signed.
	 */
	private function hmacsha1($key, $data)
	{
		return base64_encode(hash_hmac('SHA1', $data, $key, true));
	}


	/**
	 * URL-encode method for internatl use
	 *
	 * @return	string
	 * @param	mixed $value	The value to encode.
	 */
	private static function urlencode_rfc3986($value)
	{
		if(is_array($value)) return array_map(array('Dropbox', 'urlencode_rfc3986'), $value);
		else
		{
			$search = array('+', ' ', '%7E', '%');
			$replace = array('%20', '%20', '~', '%25');

			return str_replace($search, $replace, rawurlencode($value));
		}
	}


// oauth resources
	/**
	 * Call for obtaining an OAuth request token.
	 * Returns a request token and the corresponding request token secret. This token and secret cannot be used to sign requests for the /metadata and /file content API calls.
	 * Their only purpose is for signing a request to oauth/access_token once the user has gone through the application authorization steps provided by oauth/authorize.
	 *
	 * @return	array
	 */
	public function oAuthRequestToken()
	{
		// make the call
		$response = $this->doOAuthCall('0/oauth/request_token', null, 'POST', false);

		// process response
		$response = (array) explode('&', $response);
		$return = array();

		// loop chunks
		foreach($response as $chunk)
		{
			// split again
			$chunks = explode('=', $chunk, 2);

			// store return
			if(count($chunks) == 2) $return[$chunks[0]] = $chunks[1];
		}

		// return
		return $return;
	}


	/**
	 * Redirect the user to the oauth/authorize location so that Dropbox can authenticate the user and ask whether or not the user wants to authorize the application to access
	 * file metadata and content on its behalf. oauth/authorize is not an API call per se, because it does not have a return value, but rather directs the user to a page on
	 * api.dropbox.com where they are provided means to log in to Dropbox and grant authority to the application requesting it.
	 * The page served by oauth/authorize should be presented to the user through their web browser.
	 * Please note, without directing the user to a Dropbox-provided page via oauth/authorize, it is impossible for your application to use the request token it received
	 * via oauth/request_token to obtain an access token from oauth/access_token.
	 *
	 * @return	void
	 * @param	string $oauthToken					The request token of the application requesting authority from a user.
	 * @param	string[optional] $oauthCallback		After the user authorizes an application, the user is redirected to the application-served URL provided by this parameter.
	 */
	public function oAuthAuthorize($oauthToken, $oauthCallback = null)
	{
		// build parameters
		$parameters = array();
		$parameters['oauth_token'] = (string) $oauthToken;
		if($oauthCallback !== null) $parameters['oauth_callback'] = (string) $oauthCallback;

		// build url
		$url = self::API_AUTH_URL . '/0/oauth/authorize?' . http_build_query($parameters);

		// redirect
		header('Location: ' . $url);
		exit;
	}


	/**
	 * This call returns a access token and the corresponding access token secret.
	 * Upon return, the authorization process is now complete and the access token and corresponding secret are used to sign requests for the metadata and file content API calls.
	 *
	 * @return	array
	 * @param	string $oauthToken	The token returned after authorizing.
	 */
	public function oAuthAccessToken($oauthToken)
	{
		// build parameters
		$parameters = array();
		$parameters['oauth_token'] = (string) $oauthToken;

		// make the call
		$response = $this->doOAuthCall('0/oauth/access_token', $parameters, 'POST', false);

		// process response
		$response = (array) explode('&', $response);
		$return = array();

		// loop chunks
		foreach($response as $chunk)
		{
			// split again
			$chunks = explode('=', $chunk, 2);

			// store return
			if(count($chunks) == 2) $return[$chunks[0]] = $chunks[1];
		}

		// return
		return $return;
	}


// token resources
	/**
	 * The token call provides a consumer/secret key pair you can use to consistently access the user's account.
	 * This is the preferred method of authentication over storing the username and password.
	 * Use the key pair as a signature with every subsequent call.
	 * The request must be signed using the application's developer and secret key token. Request or access tokens are necessary.
	 *
	 * Warning: DO NOT STORE THE USER'S PASSWORD! The way this call works is you call it once with the user's email and password and then
	 * keep the token around for later. You do NOT (I repeat NOT) call this before everything you do or on each program startup.
	 * We watch for this and will shut down your application with little notice if we catch you.
	 * In fact, the Objective-C code does this for you so you can't get it wrong.
	 *
	 * @return	array				Upon successful verification of the user's credentials, returns an array representation of the access token and secret.
	 * @param	string $email		The email account of the user.
	 * @param	string $password	The password of the user.
	 */
	public function token($email, $password)
	{
		// build parameters
		$parameters = array();
		$parameters['email'] = (string) $email;
		$parameters['password'] = (string) $password;

		// make the call
		$response = (array) $this->doOAuthCall('0/token', $parameters);

		// validate and set
		if(isset($response['token'])) $this->setOAuthToken($response['token']);
		if(isset($response['secret'])) $this->setOAuthTokenSecret($response['secret']);

		// return
		return $response;
	}


// account resources
	/**
	 * Given a set of account information, the account call allows an application to create a new Dropbox user account.
	 * This is useful for situations where the trusted third party application is possibly the user's first interaction with Dropbox.
	 *
	 * @return	bool
	 * @param	string $email		The email account of the user.
	 * @param	string $password	The password for the user.
	 * @param	string $firstName	The user's first name.
	 * @param	string $lastName	The user's last name.
	 */
	public function account($email, $password, $firstName, $lastName)
	{
		// build parameters
		$parameters['email'] = (string) $email;
		$parameters['first_name'] = (string) $firstName;
		$parameters['last_name'] = (string) $lastName;
		$parameters['password'] = (string) $password;

		return (bool) ($this->doCall('0/account', $parameters, 'POST', null, false) == 'OK');
	}


	/**
	 * Get the user account information.
	 *
	 * @return	array
	 */
	public function accountInfo()
	{
		// make the call
		return (array) $this->doCall('0/account/info');
	}


// files & metadata
	/**
	 * Retrieves file contents relative to the user's Dropbox root or the application's directory within the user's Dropbox.
	 *
	 * @return	string
	 * @param	string $path				Path of the directory wherin the file is located.
	 * @param	bool[optional] $sandbox		Sandbox mode?
	 */
	public function filesGet($path, $sandbox = false)
	{
		// build url
		$url = '0/files/';
		$url .= ($sandbox) ? 'sandbox/' : 'dropbox/';
		$url .= trim((string) $path, '/');

		// make the call
		return $this->doCall($url, null, 'GET', null, false, true);
	}


	/**
	 * Uploads file contents relative to the user's Dropbox root or the application's directory within the user's Dropbox.
	 *
	 * @return	bool
	 * @param	string $path				Path of the directory wherin the file should be uploaded.
	 * @param	string $localFile			Path to the local file.
	 * @param	bool[optional] $sandbox		Sandbox mode?
	 */
	public function filesPost($path, $localFile, $sandbox = false)
	{
		// build url
		$url = '0/files/';
		$url .= ($sandbox) ? 'sandbox/' : 'dropbox/';
		$url .= str_replace(' ', '%20', trim((string) $path, '/'));

		// make the call
		$return = $this->doCall($url, null, 'POST', $localFile, true, true);

		// return the result
		return (bool) (isset($return['result']) && $return['result'] == 'winner!');
	}


	/**
	 * Returns metadata for the file or directory at the given <path> location relative to the user's Dropbox or
	 * the user's application sandbox. If <path> represents a directory and the list parameter is true, the metadata will
	 * also include a listing of metadata for the directory's contents.
	 *
	 * @return	array
	 * @param	string[optional] $path		The path to the file/director to get the metadata for.
	 * @param	int[optional] $fileLimit	When listing a directory, the service will not report listings containing more than $fileLimit files.
	 * @param	bool[optional] $hash		Listing return values include a hash representing the state of the directory's contents.
	 * @param	bool[optional] $list		If true, this call returns a list of metadata representations for the contents of the directory. If false, this call returns the metadata for the directory itself.
	 * @param	bool[optional] $sandbox		Sandbox mode?
	 */
	public function metadata($path = '', $fileLimit = 10000, $hash = false, $list = true, $sandbox = false)
	{
		// build url
		$url = '0/metadata/';
		$url .= ($sandbox) ? 'sandbox/' : 'dropbox/';
		$url .= trim((string) $path, '/');

		// build parameters
		$parameters = null;
		$parameters['file_limit'] = (int) $fileLimit;
		if((bool) $hash) $parameters['hash'] = '';
		$parameters['list'] = ($list) ? 'true': 'false';

		// make the call
		return (array) $this->doCall($url, $parameters);
	}


	/**
	 * Get a minimized thumbnail for a photo.
	 *
	 * @return	string					Will return a base64_encode string with the JPEG-data
	 * @param	string $path			The path to the photo.
	 * @param	string[optional] $size	The size, possible values are: 'small' (32x32), 'medium' (64x64), 'large' (128x128).
	 */
	public function thumbnails($path, $size = 'small')
	{
		// build url
		$url = '0/thumbnails/dropbox/';
		$url .= trim((string) $path, '/');

		// build parameters
		$parameters['size'] = (string) $size;

		// make the call
		return $this->doCall($url, $parameters, 'GET', null, false, true);
	}


// file operations
	/**
	 * Copy a file or folder to a new location.
	 *
	 * @return	array
	 * @param	string $fromPath			fromPath specifies either a file or folder to be copied to the location specified by toPath. This path is interpreted relative to the location specified by root.
	 * @param	string $toPath				toPath specifies the destination path including the new name for file or folder. This path is interpreted relative to the location specified by root.
	 * @param	bool[optional] $sandbox		Sandbox mode?
	 */
	public function fileopsCopy($fromPath, $toPath, $sandbox = false)
	{
		// build url
		$url = '0/fileops/copy';

		// build parameters
		$parameters['from_path'] = (string) $fromPath;
		$parameters['to_path'] = (string) $toPath;
		$parameters['root'] = ($sandbox) ? 'sandbox' : 'dropbox';

		// make the call
		return $this->doCall($url, $parameters, 'POST');
	}


	/**
	 * Create a folder relative to the user's Dropbox root or the user's application sandbox folder.
	 *
	 * @return	array
	 * @param	string $path				The path to the new folder to create, relative to root.
	 * @param	bool[optional] $sandbox		Sandbox mode?
	 */
	public function fileopsCreateFolder($path, $sandbox = false)
	{
		// build url
		$url = '0/fileops/create_folder';

		// build parameters
		$parameters['path'] = trim((string) $path, '/');
		$parameters['root'] = ($sandbox) ? 'sandbox' : 'dropbox';

		// make the call
		return $this->doCall($url, $parameters, 'POST');
	}


	/**
	 * Deletes a file or folder.
	 *
	 * @return	array
	 * @param	string $path				path specifies either a file or folder to be deleted. This path is interpreted relative to the location specified by root.
	 * @param	bool[optional] $sandbox		Sandbox mode?
	 */
	public function fileopsDelete($path, $sandbox = false)
	{
		// build url
		$url = '0/fileops/delete';

		// build parameters
		$parameters['path'] = trim((string) $path, '/');
		$parameters['root'] = ($sandbox) ? 'sandbox' : 'dropbox';

		// make the call
		return $this->doCall($url, $parameters, 'POST');
	}


	/**
	 * Move a file or folder to a new location.
	 *
	 * @return	array
	 * @param	string $fromPath			fromPath specifies either a file or folder to be copied to the location specified by toPath. This path is interpreted relative to the location specified by root.
	 * @param	string $toPath				toPath specifies the destination path including the new name for file or folder. This path is interpreted relative to the location specified by root.
	 * @param	bool[optional] $sandbox		Sandbox mode?
	 */
	public function fileopsMove($fromPath, $toPath, $sandbox = false)
	{
		// build url
		$url = '0/fileops/move';

		// build parameters
		$parameters['from_path'] = (string) $fromPath;
		$parameters['to_path'] = (string) $toPath;
		$parameters['root'] = ($sandbox) ? 'sandbox' : 'dropbox';

		// make the call
		return $this->doCall($url, $parameters, 'POST');
	}
}


/**
 * Dropbox Exception class
 *
 * @author	Tijs Verkoyen <php-dropbox@verkoyen.eu>
 */
class DropboxException extends Exception
{
}

?>