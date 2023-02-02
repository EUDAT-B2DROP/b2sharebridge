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

use CurlHandle;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

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
    protected CurlHandle $curl_client;
    protected array $result;

    /**
     * Create object for actual upload
     *
     */
    public function __construct(IConfig $config, LoggerInterface $logger)
    {
        $this->curl_client = curl_init();
    }

    /**
     * Publish to url via put, use uuid for filename. Use a token and set expect
     * to empty just as a workaround for local issues
     *
     * @param string $token users access token
     * @param string $community local filename of file that should be submitted
     * @param string $open_access
     * @param string $title
     * @param string $api_endpoint
     * @return string
     */
    public function create(string $token, string $community, string $open_access, string $title, string $api_endpoint): string
    {
        $this->result['url'] = $api_endpoint.'/'.uniqid();
        curl_setopt($this->curl_client, CURLOPT_URL, $this->result['url']);
        curl_setopt(
            $this->curl_client,
            CURLOPT_HTTPHEADER,
            array(
                'X-Auth-Token: '.$token,
                'Expect:'
            )
        );
        return $this->result['url'];  // I don't know, untestable
    }

    /**
     * Finalize file upload by actually doing it
     *
     * @return mixed
     */
    protected function finalize(): mixed
    {
        $tmp = curl_exec($this->curl_client);
        $response_code = curl_getinfo($this->curl_client)['http_code'];

        if ($response_code === 201) {
            $this->result['output'] = 'successfully transferred file';
            $this->result['status'] = 'success';

        } else {
            $this->result['output'] = 'error transferring file'.$tmp;
            $this->result['status'] = 'error';

        }
        curl_close($this->curl_client);
        return $this->result;
    }

    /**
     * Create upload object but do not the upload here
     *
     * @param string $file_upload_url users access token
     * @param string $filehandle local filename of file that should be submitted
     * @param string $filesize
     * @return bool
     */
    public function upload(string $file_upload_url, string $filehandle, string $filesize): bool
    {
        curl_setopt($this->curl_client, CURLOPT_URL, $file_upload_url);
        curl_setopt($this->curl_client, CURLOPT_INFILE, $filehandle);
        curl_setopt($this->curl_client, CURLOPT_INFILESIZE, $filesize);
        curl_setopt($this->curl_client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl_client, CURLOPT_PUT, true);
        curl_setopt($this->curl_client, CURLOPT_FORBID_REUSE, 1);
        $res = $this->finalize();
        return $res['status'] == 'success';
    }
}
