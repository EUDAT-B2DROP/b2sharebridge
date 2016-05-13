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

/**
 * Implement a backend that is able to move data from owncloud to OpenStack Swift
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class Swift implements IPublish
{
    private $_api_endpoint;
    private $_curl_client;
    private $_result;

    /**
     * Create object for actual upload
     *
     * @param string $api_endpoint api endpoint baseurl for b2share
     */
    public function __construct($api_endpoint)
    {
        $this->_api_endpoint = $api_endpoint;
        $this->_curl_client = curl_init();
    }

    /**
     * Publish to url via put, use uuid for filename. Use a token and set expect
     * to empty just as a workaround for local issues
     *
     * @param string $token    users access token
     * @param string $filename local filename of file that should be submitted
     *
     * @return null
     */
    public function create($token, $filename)
    {
        $this->_result['url'] = $this->_api_endpoint.'/'.uniqid();
        curl_setopt($this->_curl_client, CURLOPT_URL, $this->_result['url']);
        curl_setopt(
            $this->_curl_client,
            CURLOPT_HTTPHEADER,
            array(
                'X-Auth-Token: '.$token,
                'Expect:'
            )
        );
    }

    /**
     * Finalize file upload by actually doing it
     *
     * @return null
     */
    public function finalize()
    {
        $tmp = curl_exec($this->_curl_client);
        $response_code = curl_getinfo($this->_curl_client)['http_code'];

        if ($response_code === 201) {
            $this->_result['output'] = 'successfully transferred file';
            $this->_result['status'] = 'success';

        } else {
            $this->_result['output'] = 'error transferring file'.$tmp;
            $this->_result['status'] = 'error';

        }
        curl_close($this->_curl_client);
        return $this->_result;
    }

    /**
     * Create upload object but do not the upload here
     *
     * @param string $filehandle users access token
     * @param string $filesize   local filename of file that should be submitted
     *
     * @return null
     */
    public function upload($filehandle, $filesize)
    {
        curl_setopt($this->_curl_client, CURLOPT_INFILE, $filehandle);
        curl_setopt($this->_curl_client, CURLOPT_INFILESIZE, $filesize);
        curl_setopt($this->_curl_client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_curl_client, CURLOPT_PUT, true);
        curl_setopt($this->_curl_client, CURLOPT_FORBID_REUSE, 1);
    }
}
