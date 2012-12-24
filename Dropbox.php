<?php
namespace TijsVerkoyen\Dropbox;

/**
 * Dropbox class
 *
 * @author Tijs Verkoyen <php-dropbox@verkoyen.eu>
 * @version 1.0.7
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license BSD License
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
    const VERSION = '1.0.7';

    /**
     * A cURL instance
     *
     * @var resource
     */
    private $curl;

    /**
     * The application key
     *
     * @var string
     */
    private $applicationKey;

    /**
     * The application secret
     *
     * @var string
     */
    private $applicationSecret;

    /**
     * The oAuth-token
     *
     * @var string
     */
    private $oAuthToken = '';

    /**
     * The oAuth-token-secret
     *
     * @var string
     */
    private $oAuthTokenSecret = '';

    /**
     * The timeout
     *
     * @var int
     */
    private $timeOut = 10;

    /**
     * The user agent
     *
     * @var string
     */
    private $userAgent;

    // class methods
    /**
     * Default constructor
     *
     * @param $applicationKey string application key to use.
     * @param $applicationSecret string application secret to use.
     */
    public function __construct($applicationKey, $applicationSecret)
    {
        $this->setApplicationKey($applicationKey);
        $this->setApplicationSecret($applicationSecret);
    }

    /**
     * Default destructor
     */
    public function __destruct()
    {
        if($this->curl != null) curl_close($this->curl);
    }

    /**
     * Format the parameters as a querystring
     *
     * @param $parameters array parameters to pass.
     * @return string
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
        foreach ($parameters as $key => $value) {
            // sort by value
            if(is_array($value)) $parameters[$key] = natsort($value);
        }

        // process parameters
        foreach ($parameters as $key => $value) {
            $chunks[] = $key . '=' . str_replace('%25', '%', $value);
        }

        // return
        return implode('&', $chunks);
    }

    /**
     * Build the Authorization header
     * @later: fix me
     *
     * @param  array  $parameters The parameters.
     * @param  string $url        The URL.
     * @return string
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
        $return = 'Authorization: OAuth realm="", ';
        $return .= implode(',', $chunks);

        // prepend name and OAuth part
        return $return;
    }

    /**
     * Make an call to the oAuth
     * @todo	refactor me
     *
     * @todo refactor me
     * @return array
     * @param $url string url that has to be called.
     * @param $parameters array[optional] parameters to pass.
     * @param $method string[optional] HTTP-method should we use? Possible values are POST, GET.
     * @param $expectJSON bool[optional] we expect JSON?
     */
    private function doOAuthCall($url, array $parameters = null, $method = 'POST', $expectJSON = true)
    {
        // redefine
        $url = (string) $url;

        // append default parameters
        $parameters['oauth_consumer_key'] = $this->getApplicationKey();
        $parameters['oauth_signature_method'] = 'PLAINTEXT';
        $parameters['oauth_version'] = '1.0';
        $parameters['oauth_signature'] = $this->getApplicationSecret() . '&' . $this->getOAuthTokenSecret();

        if ($method == 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $parameters;
        } else {
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
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            $options[CURLOPT_FOLLOWLOCATION] = true;
        }
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
        if($errorNumber != '') throw new Exception($errorMessage, $errorNumber);

        // return
        if($expectJSON) return json_decode($response, true);

        // fallback
        return $response;
    }

    /**
     * Make the call
     *
     * @return string
     * @param $url string url to call.
     * @param $parameters array[optional] parameters.
     * @param $method bool[optional] method to use. Possible values are GET, POST.
     * @param $filePath string[optional] path to the file to upload.
     * @param $expectJSON bool[optional] we expect JSON?
     * @param $isContent bool[optional] this content?
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
        if (!in_array($method, $allowedMethods)) {
            throw new Exception(
                'Unknown method (' . $method . '). Allowed methods are: ' .
                implode(', ', $allowedMethods)
            );
        }

        // append default parameters
        $oauth['oauth_consumer_key'] = $this->getApplicationKey();
        $oauth['oauth_token'] = $this->getOAuthToken();
        $oauth['oauth_signature_method'] = 'PLAINTEXT';
        $oauth['oauth_version'] = '1.0';
        $oauth['oauth_signature'] = $this->getApplicationSecret() . '&' . $this->getOAuthTokenSecret();
        if ($isContent) {
            $headers[] = $this->calculateHeader($oauth, self::API_CONTENT_URL . '/' . $url);
        } else {
            $headers[] = $this->calculateHeader($oauth, self::API_URL . '/' . $url);
        }
        $headers[] = 'Expect:';

        // set data
        $data = $oauth;
        if(!empty($parameters)) $data = array_merge($data, $parameters);

         if ($filePath != null) {
            // process file
            $fileInfo = pathinfo($filePath);

            // add to the data
            $data['file'] = $fileInfo['basename'];

        }

        // based on the method, we should handle the parameters in a different way
        if ($method == 'POST') {
            // file provided?
            if ($filePath != null) {
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
            else {
                $options[CURLOPT_POSTFIELDS] = $this->buildQuery($parameters);
            }

            // enable post
            $options[CURLOPT_POST] = 1;
        } else {
            // add the parameters into the querystring
            if(!empty($parameters)) $url .= '?' . $this->buildQuery($parameters);

            $options[CURLOPT_POST] = false;
        }

        // set options
        if ($isContent) {
            $options[CURLOPT_URL] = self::API_CONTENT_URL . '/' . $url;
        } else {
            $options[CURLOPT_URL] = self::API_URL . '/' . $url;
        }

        $options[CURLOPT_PORT] = self::API_PORT;
        $options[CURLOPT_USERAGENT] = $this->getUserAgent();
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            $options[CURLOPT_FOLLOWLOCATION] = true;
        }
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

        if (!$expectJSON && $isContent) {
            // is it JSON?
            $json = @json_decode($response, true);
            if($json !== false && isset($json['error'])) throw new Exception($json['error']);

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
        if ($json === null) {
            // should we provide debug information
            if (self::DEBUG) {
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
            throw new Exception('Invalid response.');
        }

        // any error
        if (isset($json['error'])) {
            // should we provide debug information
            if (self::DEBUG) {
                // make it output proper
                echo '<pre>';

                // dump the header-information
                var_dump($headers);

                // dump the raw response
                var_dump($response);

                // end proper format
                echo '</pre>';
            }

            if(isset($json['error']) && is_string($json['error']))
                $message = $json['error'];
            elseif(isset($json['error']['hash']) && $json['error']['hash'] != '')
                $message = (string) $json['error']['hash'];
            else $message = 'Invalid response.';

            // throw exception
            throw new Exception($message);
        }

        // return
        return $json;
    }

    /**
     * Get the application key
     *
     * @return string
     */
    private function getApplicationKey()
    {
        return $this->applicationKey;
    }

    /**
     * Get the application secret
     *
     * @return string
     */
    private function getApplicationSecret()
    {
        return $this->applicationSecret;
    }

    /**
     * Get the oAuth-token
     *
     * @return string
     */
    private function getOAuthToken()
    {
        return $this->oAuthToken;
    }

    /**
     * Get the oAuth-token-secret
     *
     * @return string
     */
    private function getOAuthTokenSecret()
    {
        return $this->oAuthTokenSecret;
    }

    /**
     * Get the timeout
     *
     * @return int
     */
    public function getTimeOut()
    {
        return (int) $this->timeOut;
    }

    /**
     * Get the useragent that will be used.
     * Our version will be prepended to yours.
     * It will look like: "PHP Dropbox/<version> <your-user-agent>"
     *
     * @return string
     */
    public function getUserAgent()
    {
        return (string) 'PHP Dropbox/' . self::VERSION . ' ' . $this->userAgent;
    }

    /**
     * Set the application key
     *
     * @param $key string application key to use.
     */
    private function setApplicationKey($key)
    {
        $this->applicationKey = (string) $key;
    }

    /**
     * Set the application secret
     *
     * @param $secret string application secret to use.
     */
    private function setApplicationSecret($secret)
    {
        $this->applicationSecret = (string) $secret;
    }

    /**
     * Set the oAuth-token
     *
     * @param $token string token to use.
     */
    public function setOAuthToken($token)
    {
        $this->oAuthToken = (string) $token;
    }

    /**
     * Set the oAuth-secret
     *
     * @param $secret string secret to use.
     */
    public function setOAuthTokenSecret($secret)
    {
        $this->oAuthTokenSecret = (string) $secret;
    }

    /**
     * Set the timeout
     *
     * @param $seconds int timeout in seconds.
     */
    public function setTimeOut($seconds)
    {
        $this->timeOut = (int) $seconds;
    }

    /**
     * Get the useragent that will be used.
     * Our version will be prepended to yours.
     * It will look like: "PHP Dropbox/<version> <your-user-agent>"
     *
     * @param $userAgent string user-agent, it should look like <app-name>/<app-version>.
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = (string) $userAgent;
    }

    /**
     * Build the signature for the data
     *
     * @return string
     * @param $key string key to use for signing.
     * @param $data string data that has to be signed.
     */
    private function hmacsha1($key, $data)
    {
        return base64_encode(hash_hmac('sha1', $data, $key, true));
    }

    /**
     * URL-encode method for internatl use
     *
     * @return string
     * @param $value mixed value to encode.
     */
    private static function urlencode_rfc3986($value)
    {
        if (is_array($value)) {
            return array_map(array(__CLASS__, 'urlencode_rfc3986'), $value);
        } else {
            return str_replace('%7E', '~', rawurlencode($value));
        }
    }

    // oauth resources
    /**
     * Step 1 of authentication. Obtain an OAuth request token to be used for the rest of the authentication process.
     * This method corresponds to Obtaining an Unauthorized Request Token in the OAuth Core 1.0 specification.
     * A request token and the corresponding request token secret, URL-encoded. This token/secret pair is meant to be used with /oauth/access_token to complete the authentication process and cannot be used for any other API calls. See Service Provider Issues an Unauthorized Request Token in the OAuth Core 1.0 specification for additional discussion of the values returned when fetching a request token.
     *
     * @return array
     */
    public function oAuthRequestToken()
    {
        // make the call
        $response = $this->doOAuthCall('1/oauth/request_token', null, 'POST', false);

        // process response
        $response = (array) explode('&', $response);
        $return = array();

        // loop chunks
        foreach ($response as $chunk) {
            // split again
            $chunks = explode('=', $chunk, 2);

            // store return
            if(count($chunks) == 2) $return[$chunks[0]] = $chunks[1];
        }

        // return
        return $return;
    }

    /**
     * Step 2 of authentication. Applications should direct the user to /oauth/authorize. This isn't an API call per se, but rather a web endpoint that lets the user sign in to Dropbox and choose whether to grant the application the ability to access files on their behalf. The page served by /oauth/authorize should be presented to the user through their web browser. Without the user's authorization in this step, it isn't possible for your application to obtain an access token from /oauth/access_token.
     * This method corresponds to Obtaining User Authorization in the OAuth Core 1.0 specification.
     *
     * @param string           $oauthToken    The request token obtained via /oauth/request_token.
     * @param string[optional] $oauthCallback After the either decides to authorize or disallow your application, they are redirected to this URL.
     * @param string[optional] $locale        If the locale specified is a supported language, Dropbox will direct users to a translated version of the authorization website. See https://www.dropbox.com/help/31/en for more information about supported locales.
     */
    public function oAuthAuthorize($oauthToken, $oauthCallback = null, $locale = null)
    {
        // build parameters
        $parameters = array();
        $parameters['oauth_token'] = (string) $oauthToken;
        if($oauthCallback !== null) $parameters['oauth_callback'] = (string) $oauthCallback;
        if($locale !== null) $parameters['locale'] = (string) $locale;

        // build url
        $url = self::API_AUTH_URL . '/1/oauth/authorize?' . http_build_query($parameters);

        // redirect
        header('Location: ' . $url);
        exit();
    }

    /**
     * Step 3 of authentication. After the /oauth/authorize step is complete, the application can call /oauth/access_token to acquire an access token.
     * This method corresponds to Obtaining an Access Token in the OAuth Core 1.0 specification.
     *
     * @return array
     * @param $oauthToken string token returned after authorizing.
     */
    public function oAuthAccessToken($oauthToken)
    {
        // build parameters
        $parameters = array();
        $parameters['oauth_token'] = (string) $oauthToken;

        // make the call
        $response = $this->doOAuthCall('1/oauth/access_token', $parameters, 'POST', false);

        // validate
        $json = @json_decode($response, true);
        if (isset($json['error'])) {
            throw new Exception($json['error']);
        }

        // process response
        $response = (array) explode('&', $response);
        $return = array();

        // loop chunks
        foreach ($response as $chunk) {
            // split again
            $chunks = explode('=', $chunk, 2);

            // store return
            if(count($chunks) == 2) $return[$chunks[0]] = $chunks[1];
        }

        // return
        return $return;
    }

    // account resources
    /**
     * Get the user account information.
     *
     * @return array
     */
    public function accountInfo()
    {
        // make the call
        return (array) $this->doCall('1/account/info');
    }

    // files & metadata
    /**
     * Retrieves file contents relative to the user's Dropbox root or the application's directory within the user's Dropbox.
     *
     * @return string
     * @param $path string of the directory wherin the file is located.
     * @param $sandbox bool[optional] mode?
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
     * @return bool
     * @param $path string of the directory wherin the file should be uploaded.
     * @param $localFile string to the local file.
     * @param $sandbox bool[optional] mode?
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
     * the user's application sandbox.
     * If <path> represents a directory and the list parameter is true, the metadata will
     * also include a listing of metadata for the directory's contents.
     *
     * @return array
     * @param  string[optional] $path           The path to the file or folder.
     * @param  int[optional]    $fileLimit      Default is 10,000 (max is 25,000). When listing a folder, the service will not report listings containing more than the specified amount of files and will instead respond with a 406 (Not Acceptable) status response.
     * @param  string[optional] $hash           Each call to /metadata on a folder will return a hash field, generated by hashing all of the metadata contained in that response. On later calls to /metadata, you should provide that value via this parameter so that if nothing has changed, the response will be a 304 (Not Modified) status code instead of the full, potentially very large, folder listing. This parameter is ignored if the specified path is associated with a file or if list=false. A folder shared between two users will have the same hash for each user.
     * @param  bool[optional]   $list           If true, the folder's metadata will include a contents field with a list of metadata entries for the contents of the folder. If false, the contents field will be omitted.
     * @param  bool[optional]   $includeDeleted Only applicable when list is set. If this parameter is set to true, then contents will include the metadata of deleted children. Note that the target of the metadata call is always returned even when it has been deleted (with is_deleted set to true) regardless of this flag.
     * @param  string[optional] $rev            If you include a particular revision number, then only the metadata for that revision will be returned.
     * @param  bool[optional]   $sandbox        The metadata returned will have its size field translated based on the given locale.
     */
    public function metadata($path = '', $fileLimit = 10000, $hash = false, $list = true, $includeDeleted = false, $rev = null, $locale = null, $sandbox = false)
    {
        // build url
        $url = '1/metadata/';
        $url .= ($sandbox) ? 'sandbox/' : 'dropbox/';
        $url .= trim((string) $path, '/');

        // build parameters
        $parameters = null;
        $parameters['file_limit'] = (int) $fileLimit;
        if((bool) $hash) $parameters['hash'] = '';
        $parameters['list'] = ($list) ? 'true' : 'false';
        if((bool) $includeDeleted) $parameters['include_deleted'] = 'true';
        if($rev !== null) $parameters['rev'] = (string) $rev;
        if($locale !== null) $parameters['locale'] = (string) $locale;

        // make the call
        return (array) $this->doCall($url, $parameters);
    }

    /**
     * Get a minimized thumbnail for a photo.
     *
     * @return string return a base64_encode string with the JPEG-data
     * @param $path string path to the photo.
     * @param $size string[optional] size, possible values are: 'small' (32x32), 'medium' (64x64), 'large' (128x128).
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
     * @return array
     * @param $fromPath string specifies either a file or folder to be copied to the location specified by toPath. This path is interpreted relative to the location specified by root.
     * @param $toPath string specifies the destination path including the new name for file or folder. This path is interpreted relative to the location specified by root.
     * @param $sandbox bool[optional] mode?
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
     * @return array
     * @param $path string path to the new folder to create, relative to root.
     * @param $sandbox bool[optional] mode?
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
     * @return array
     * @param $path string specifies either a file or folder to be deleted. This path is interpreted relative to the location specified by root.
     * @param $sandbox bool[optional] mode?
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
     * @return array
     * @param $fromPath string specifies either a file or folder to be copied to the location specified by toPath. This path is interpreted relative to the location specified by root.
     * @param $toPath string specifies the destination path including the new name for file or folder. This path is interpreted relative to the location specified by root.
     * @param $sandbox bool[optional] mode?
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
