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

class Swift implements IPublish
{
    /**
     * @var string api-endpoint for b2share
     */
    private $api_endpoint;
    private $curl_client;
    private $result;

    /**
     * @param string $api_endpoint api endpoint baseurl for b2share
     */
    public function __construct($api_endpoint)
    {
        $this->api_endpoint = $api_endpoint;
        $this->curl_client = curl_init();
    }

    /**
     * @param string $token    users access token
     * @param string $filename local filename of file that should be submitted
     *
     * publish to purl via put, use uuid for filename. Use a token and set expect to empty just as a workaround for
     * local issues
     */
    public function create($token, $filename)
    {
        $this->result['url'] = $this->api_endpoint.'/'.uniqid();
        curl_setopt($this->curl_client, CURLOPT_URL, $this->result['url']);
        curl_setopt($this->curl_client, CURLOPT_HTTPHEADER, array('X-Auth-Token: '.$token, 'Expect:'));
    }

    /**
     *
     */
    public function finalize()
    {
        $tmp = curl_exec($this->curl_client);
        $response_code = curl_getinfo($this->curl_client)['http_code'];

        if ($response_code === 201) {
            $this->result['output'] = 'successfully transferred file';
            $this->result['status'] = 'success';

        }
        else {
            $this->result['output'] = 'error transferring file'.$tmp;
            $this->result['status'] = 'error';

        }
        curl_close($this->curl_client);
        return $this->result;
    }

    /**
     * @param string $filehandle users access token
     * @param string $filesize   local filename of file that should be submitted
     */
    public function upload($filehandle, $filesize)
    {
        curl_setopt($this->curl_client, CURLOPT_INFILE, $filehandle);
        curl_setopt($this->curl_client, CURLOPT_INFILESIZE, $filesize);
        curl_setopt($this->curl_client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl_client, CURLOPT_PUT, true);
        curl_setopt($this->curl_client, CURLOPT_FORBID_REUSE, 1);
    }

}
