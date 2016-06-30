<?php
/**
 * OwnCloud - B2sharebridge App
 *
 * PHP Version 5-7
 *
 * @category  Owncloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2015 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Publish;


use OCP\Util;

/**
 * Implement a backend that is able to move data from owncloud to B2SHARE
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class B2share implements IPublish
{
    private $_api_endpoint;
    private $_curl_client;
    private $_result;
    private $_file_upload_url;

    /**
     * Create object for actual upload
     *
     * @param string $api_endpoint api endpoint baseurl for b2share
     */
    public function __construct($api_endpoint)
    {
        $this->_api_endpoint = $api_endpoint;
        $this->_curl_client = curl_init();
        $defaults = array(
            CURLOPT_URL => $this->_api_endpoint . '/api/records/',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HEADER => 1
        );
        curl_setopt_array($this->_curl_client, $defaults);
    }

    /**
     * Publish to url via put, use uuid for filename. Use a token and set expect
     * to empty just as a workaround for local issues
     *
     * @param string $token     users access token
     * @param string $filename  local filename of file that should be submitted
     * @param string $community id of community metadata schema, defaults to EUDAT
     *
     * @return null
     */
    public function create(
        $token,
        $filename,
        $community = "e9b9792e-79fb-4b07-b6b4-b9c2bd06d095"
    ) {
        curl_setopt($this->_curl_client, CURLOPT_POST, 1);

        $data = json_encode(
            array(
                "community" => $community,
                "title" => basename($filename),
                "open_access" => true
            )
        );
        curl_setopt(
            $this->_curl_client,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        curl_setopt($this->_curl_client, CURLOPT_POSTFIELDS, $data);

        /* if request to open deposit was successful, extract file upload link
         * due to b2share offering it via a "Link" key containing two values, this
         * is  not so beautiful right now.
         */
        if (!$response = curl_exec($this->_curl_client)) {
            return false;
        } else {
            $header_size = curl_getinfo($this->_curl_client, CURLINFO_HEADER_SIZE);
            $headers = self::getHeadersFromCurlResponse(
                substr(
                    $response,
                    0,
                    $header_size
                )
            );
            if (is_array($headers)
                and is_array($headers[0])
                and array_key_exists('Link', $headers[0])
            ) {
                $this->_file_upload_url = explode(
                    ';',
                    $headers[0]['Link']
                )[0].'/'.basename($filename);
                Util::writeLog(
                    'b2share_bridge',
                    'User uploading file to:' . $this->_file_upload_url,
                    1
                );
                return true;
            } else {
                Util::writeLog(
                    'b2share_bridge',
                    'User wants to upload data but b2share did not sent a target',
                    3
                );
                return false;
            }
        }
    }

    /**
     * Parse plain curl response headers to array, thanks to
     * stackoverflow.com/questions/10589889/returning-header-as-array-using-curl
     *
     * @param string $headerContent actual headers as plain text
     *
     * @return array containing all headers
     */
    static function getHeadersFromCurlResponse($headerContent)
    {
        $headers = array();
        // Split the string on every "double" new line.
        $arrRequests = explode("\r\n\r\n", $headerContent);
        // Loop of response headers. The "count() -1" is to
        //avoid an empty row for the extra line break before the body of response.
        for ($index = 0; $index < count($arrRequests) - 1; $index++) {
            foreach (explode("\r\n", $arrRequests[$index]) as $i => $line) {
                if ($i === 0) {
                    $headers[$index]['http_code'] = $line;
                } else {
                    list ($key, $value) = explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }
        return $headers;
    }

    /**
     * Finalize file upload by actually doing it
     *
     * @return null
     */
    public function finalize()
    {
        Util::writeLog(
            'b2share_bridge',
            'Finalize not implemented, we do not close deposits for now:',
            1
        );
    }

    /**
     * Create upload object but do not the upload here
     *
     * @param string $filehandle file handle
     * @param string $filesize   local filename of file that should be submitted
     *
     * @return null
     */
    public function upload($filehandle, $filesize)
    {
        $tmp = curl_init();
        $config = array(
            CURLOPT_URL => $this->_file_upload_url,
            CURLOPT_INFILE => $filehandle,
            CURLOPT_INFILESIZE => $filesize,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_PUT => true,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: multipart/form-data',
                'Accept: */*',
                'Expect: 100-continue',
            )
        );
        curl_setopt_array($tmp, $config);

        $response = curl_exec($tmp);
        $tmp2 = curl_getinfo($tmp);
        Util::writeLog(
            'b2share_bridge',
            $response.'#####'.curl_getinfo($tmp,CURLINFO_HEADER_OUT),
            3
        );
    }
}