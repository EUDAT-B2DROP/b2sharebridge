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
class B2share implements Ipublish
{
    protected $api_endpoint;
    protected $curl_client;
    protected $file_upload_url;
    protected $error_message;

    /**
     * Create object for actual upload
     *
     * @param string  $api_endpoint api endpoint baseurl for b2share
     * @param boolean $check_ssl    whether to check security for https
     */
    public function __construct($check_ssl)
    {
        $this->curl_client = curl_init();
        $defaults = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HEADER => 1,
        );
        if (!$check_ssl) {
            $defaults[CURLOPT_SSL_VERIFYHOST] = 0;
            $defaults[CURLOPT_SSL_VERIFYPEER] = 0;
        }
        curl_setopt_array($this->curl_client, $defaults);
    }

    /**
     * Get the portion of the file upload URL
     * filename + access_token still need to be pasted
     * 
     * @return the file_upload_url for the files bucket
     */
    public function getFileUploadUrlPart()
    {
        return $this->file_upload_url;
    }

    /**
     * Get the error message from HTTP service
     * 
     * @return the error message from the http interaction
     */
    public function getErrorMessage()
    {
        if (is_null($this->error_message)) {
            return '';
        }
        return $this->error_message;
    }


    /**
     * Publish to url via post, use uuid for filename. Use a token and set expect
     * to empty just as a workaround for local issues
     *
     * @param string  $token        users access token
     * @param string  $community    id of community metadata schema, defaults to EUDAT
     * @param boolean $open_access  publish as open access, defaults to false
     * @param string  $title        actual title of the deposit
     * @param string  $api_endpoint api url 
     * @return null
     */
    public function create(
        $token,
        $community = "e9b9792e-79fb-4b07-b6b4-b9c2bd06d095",
        $open_access = false,
        $title = "Deposit title",
        $api_endpoint = "https://trng-b2share.eudat.eu"
    ) {
        //now settype("false","boolean") evaluates to true, so:
        $b_open_access = false;
        if ($open_access==="true") {
               $b_open_access = true;
        }
        $data = json_encode(
            [
                'community'   => $community,
                'titles'      => [[
                    'title'   => $title
                ]],
                'open_access' => $b_open_access
            ]
        );

        $config = array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POSTREDIR => 3,
            CURLOPT_URL =>
                $api_endpoint.'/api/records/?access_token='.$token,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: '.strlen($data))
        );
        curl_setopt_array($this->curl_client, $config);
        $response = curl_exec($this->curl_client);
        \OC::$server->getLogger()->error($response);
        if (!$response) {
            return false;
        } else {
            $header_size = curl_getinfo($this->curl_client, CURLINFO_HEADER_SIZE);
            $body = substr($response, $header_size);
            $results = json_decode(utf8_encode($body));
            if (array_key_exists('links', $results)
                and array_key_exists('self', $results->links)
                and array_key_exists('files', $results->links)
            ) {
                $this->file_upload_url
                    = $results->links->files;
                return str_replace(
                    'draft',
                    'edit',
                    str_replace('/api', '', $results->links->self)
                );
            } else {
                $this->error_message = "Something went wrong in uploading.";
                if (array_key_exists('status', $results)) { 
                    if ($results->status==='403') {
                        $this->error_message = "403 - Authorization Required";
                    }
                }
                return false;
            }
        }
    }

    /**
     * Create upload object but do not the upload here
     *
     * @param string $file_upload_url the upload_url for the files bucket
     * @param string $filehandle      file handle
     * @param string $filesize        local filename of file that should be submitted
     *
     * @return boolean
     */
    public function upload($file_upload_url, $filehandle, $filesize)
    {
        $this->curl_client = curl_init();

        $config2 = array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $file_upload_url,
            CURLOPT_INFILE => $filehandle,
            CURLOPT_INFILESIZE => $filesize,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_PUT => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => array(
                'Accept:application/json',
                'Content-Type: application/octet-stream'
            )
        );
        curl_setopt_array($this->curl_client, $config2);

        $response = curl_exec($this->curl_client);
        \OC::$server->getLogger()->info($response);
        curl_close($this->curl_client);
        if (!$response) {
            return false;
        } else {
            return true;
        }
    }
}
