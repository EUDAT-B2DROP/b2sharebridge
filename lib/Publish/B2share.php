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

    /**
     * Create object for actual upload
     *
     * @param string  $api_endpoint api endpoint baseurl for b2share
     * @param boolean $check_ssl    whether to check security for https
     */
    public function __construct($api_endpoint, $check_ssl)
    {
        $this->api_endpoint = $api_endpoint;
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
     * Publish to url via post, use uuid for filename. Use a token and set expect
     * to empty just as a workaround for local issues
     *
     * @param string $token       users access token
     * @param string $filename    local filename of file that should be submitted
     * @param string $community   id of community metadata schema, defaults to EUDAT
     * @param string $open_access publish as open access, defaults to false
     * @param string $title       actual title of the deposit
     *
     * @return null
     */
    public function create(
        $token,
        $filename,
        $community = "e9b9792e-79fb-4b07-b6b4-b9c2bd06d095",
        $open_access = false,
        $title = "Deposit title"
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
        Util::writeLog('b2share_cron', "Data: ".$data, 3);

        $config = array(
            CURLOPT_URL =>
                $this->api_endpoint.'/api/records/?access_token='.$token,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: '.strlen($data))
        );
        curl_setopt_array($this->curl_client, $config);


        $response = curl_exec($this->curl_client);
        if (!$response) {
            return false;
        } else {
            $header_size = curl_getinfo($this->curl_client, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            $results = json_decode(utf8_encode($body));
            if (array_key_exists('links', $results)
                and array_key_exists('self', $results->links)
                and array_key_exists('files', $results->links)
            ) {
                $this->file_upload_url
                    = $results->links->files.'/'.$filename.'?access_token='.$token;
                return str_replace(
                    'draft',
                    'edit',
                    str_replace('/api', '', $results->links->self)
                );
            } else {
                return false;
            }
        }
    }

    /**
     * Create upload object but do not the upload here
     *
     * @param string $filehandle file handle
     * @param string $filesize   local filename of file that should be submitted
     *
     * @return array
     */
    public function upload($filehandle, $filesize)
    {
        $config = array(
            CURLOPT_URL => $this->file_upload_url,
            CURLOPT_INFILE => $filehandle,
            CURLOPT_INFILESIZE => $filesize,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_PUT => true,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => array(
        'Accept:application/json',
                'Content-Type: application/octet-stream'
            )
        );
        curl_setopt_array($this->curl_client, $config);

        $response = curl_exec($this->curl_client);
        curl_close($this->curl_client);
        if (!$response) {
            return false;
        } else {
            return true;
        }
    }
}
