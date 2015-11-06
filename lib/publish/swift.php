<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 */

namespace OCA\Eudat\Publish;

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
     * @param string $token users access token
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
        $this->result['output'] = curl_exec($this->curl_client);
        curl_close($this->curl_client);
        $this->result['status'] = 'success';
        return $this->result;
    }

    /**
     * @param string $filehandle users access token
     * @param string $filesize local filename of file that should be submitted
     */
    public function upload($filehandle, $filesize)
    {
        curl_setopt($this->curl_client, CURLOPT_INFILE, $filehandle);
        curl_setopt($this->curl_client, CURLOPT_INFILESIZE, $filesize);
        curl_setopt($this->curl_client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl_client, CURLOPT_PUT, true);
    }

}
