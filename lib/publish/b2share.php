<?php
namespace OCA\B2shareBridge\Publish;

class B2share_Client
{
    /**
     * @var string api-endpoint for b2share
     */
    private static $api_endpoint;

    /**
     * @param string $api_endpoint api endpoint baseurl for b2share
     */
    public function __construct($api_endpoint)
    {
        self::$api_endpoint = $api_endpoint;
    }

    /**
     * @param string $token users access token
     * @param string $filename local filename of file that should be submitted
     */
    public static function depositPipeline($token, $filename)
    {
        $api_url = sprintf('%s/api/records?access_token=%s', self::$api_endpoint, $token);
        $curl_client = curl_init($api_url);
        curl_setopt($curl_client, CURLOPT_RETURNTRANSFER, 1);
        $get = self::getAllObjects($curl_client, $token);
        if (!$get) {
            exit(1);
        }

        curl_setopt($curl_client, CURLOPT_POST, 1);
        $deposit_url = self::createDeposit($curl_client, $token);
        if (!$deposit_url) {
            print_r("deposit_url: $deposit_url");
            exit(1);
        }
        print_r("deposit_url: $deposit_url");

        $file_upload = self::uploadFile($curl_client, $token, $deposit_url, $filename);
        print_r($file_upload);
        $response = self::finalizeDeposit($curl_client, $token, $deposit_url);
        print_r($response);
        curl_close($curl_client);
    }

    /**
     * @param cURL_handle $curl_client object that provides curl requests
     * @param string $token user token
     * @return string containing the deposit URL
     */
    private static function createDeposit($curl_client, $token)
    {
        $api_url = sprintf('%s/api/depositions?access_token=%s', self::$api_endpoint, $token);
        curl_setopt($curl_client, CURLOPT_URL, $api_url);
        $response = self::executeRequest($curl_client);
        return $response['location'];
    }

    /**
     * @param cURL_handle $curl_client object that provides curl requests
     * @return Array php typed json object
     */
    private static function executeRequest($curl_client)
    {
        $result = curl_exec($curl_client);
        return json_decode($result, true);
    }

    /**
     * TODO: domain, title, description and open_access should be set by user
     * @param cURL_handle $curl_client object that provides curl requests
     * @param string $token user token
     * @param string $deposit_url url for the deposit
     * @return Array php typed json object with response
     */
    private static function finalizeDeposit($curl_client, $token, $deposit_url)
    {
        $api_url = sprintf('%s%s/commit?access_token=%s', self::$api_endpoint, $deposit_url, $token);
        curl_setopt($curl_client, CURLOPT_URL, $api_url);
        curl_setopt($curl_client, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl_client, CURLOPT_POSTFIELDS, json_encode(array(
            "domain" => "generic",
            "title" => "test request",
            "description" => "test request",
            "open_access" => "true"
        )));
        print_r($curl_client);
        $response = self::executeRequest($curl_client);
        return $response;
    }

    /**
     * @param cURL_handle $curl_client object that provides curl requests
     * @param string $token user token
     * @return Array php typed json object
     */
    public static function getAllObjects($curl_client, $token)
    {
        $api_url = sprintf('%s/api/records?access_token=%s', self::$api_endpoint, $token);
        curl_setopt($curl_client, CURLOPT_URL, $api_url);
        return self::executeRequest($curl_client);
    }

    /**
     * @param cURL_handle $curl_client object that provides curl requests
     * @param string $token user token
     * @param string $deposit_url b2share api url
     * @param string $filename filename of the local file to upload
     * @return Array php typed json object
     */
    private static function uploadFile($curl_client, $token, $deposit_url, $filename)
    {
        $api_url = sprintf('%s%s/files?access_token=%s', self::$api_endpoint, $deposit_url, $token);
        curl_setopt($curl_client, CURLOPT_URL, $api_url);
        $args['file'] = new \CURLFile($filename, 'multipart/form-data');
        curl_setopt($curl_client, CURLOPT_POSTFIELDS, $args);
        return self::executeRequest($curl_client);
    }
}

/**
 * if we are executed directly, create objects and execute requests
 * TODO: don't use static config but user environment(token) and server config(api) instead
 */
if ( basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"]) ) {
    $ini_array = parse_ini_file('../api_config.ini');
    $client = new B2share_Client($ini_array['api']);
    $client->depositPipeline($ini_array['token'], '../README.md');
}
else {
    echo "included/required";
}
