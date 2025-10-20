<?php
/**
 * Nextcloud - B2sharebridge App
 *
 * PHP Version 8
 *
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2025 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Util;

use CurlHandle;
use OCA\B2shareBridge\AppInfo\Application;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Curl, but object orientated
 * 
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2025 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class Curl
{
    private bool $_ssl;
    protected LoggerInterface $logger;

    /**
     * Summary of __construct
     *
     * @param \Psr\Log\LoggerInterface $logger Logger
     * @param bool                     $ssl    Enable SSL
     */
    public function __construct(LoggerInterface $logger, bool $ssl = true)
    {
        $this->logger = $logger;
        $this->_ssl = $ssl;
    }

    /**
     * Setup curl
     * @param array $header List of headers
     * 
     * @throws RuntimeException
     * 
     * @return CurlHandle|resource
     */
    private function setup(array $header = []): CurlHandle
    {
        $ch = curl_init();
        if (is_bool($ch)) {
            throw new RuntimeException("Failed to initialize curl");
        }
        $json_header = [
            'Accept:application/json',
        ];
        $defaults = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => array_merge($header, $json_header),
        ];
        curl_setopt_array($ch, $defaults);
        $this->setSSLRequest($ch);
        return $ch;
    }

    /**
     * Tears down curl connection
     * 
     * @param CurlHandle $ch
     * 
     * @return void
     */
    private function tearDown(CurlHandle $ch)
    {
        curl_close($ch);
    }

    /**
     * Activate/Deactivate SSL in a curl request
     * 
     * @param bool $ssl False/true
     * 
     * @return void
     */
    public function setSSL(bool $ssl)
    {
        $this->_ssl = $ssl;
    }

    /**
     * Activate/Deactivate SSL in a curl request
     * 
     * @param bool $ssl False/true
     * 
     * @return void
     */
    private function setSSLRequest(CurlHandle $ch)
    {
        $defaults = [];
        if ($this->_ssl) {
            $defaults[CURLOPT_SSL_VERIFYHOST] = 2;
            $defaults[CURLOPT_SSL_VERIFYPEER] = 1;
        } else {
            $defaults[CURLOPT_SSL_VERIFYHOST] = 0;
            $defaults[CURLOPT_SSL_VERIFYPEER] = 0;
        }
        curl_setopt_array($ch, $defaults);
    }

    /**
     * Send a curl get request to $urlPath
     *
     * @param string $urlPath URL
     * @param string $type    REST type, e.g. GET, DELETE, PUT, ...
     * @param array  $header  List of headers, e.g. ["Authorization: Bearer <Token>"]
     * 
     * @return bool|string
     */
    public function request(string $urlPath, string $type = 'GET', array $header = []): bool|string
    {
        $ch = $this->setup($header);
        curl_setopt($ch, CURLOPT_URL, $urlPath);

        if ($type != 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        }

        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        if (!$output) {
            $this->_logErrors($ch);
        }

        $this->tearDown($ch);
        return $output;
    }

    /**
     * Create upload object but do not the upload here
     *
     * @param string $file_upload_url the upload_url for the files bucket
     * @param mixed  $filehandle      file handle
     * @param string $filesize        local filename of file that should be submitted
     *
     * @return bool
     */
    public function upload(string $file_upload_url, mixed $filehandle, string $filesize): bool
    {
        $ch = $this->setup();
        $config = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $file_upload_url,
            CURLOPT_INFILE => $filehandle,
            CURLOPT_INFILESIZE => $filesize,
            CURLOPT_PUT => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 0, // do not timeout, instead break on low speed
            CURLOPT_LOW_SPEED_TIME => 60, // low speed settings
            CURLOPT_LOW_SPEED_LIMIT => 30,
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => [
                'Accept:application/json',
                'Content-Type: application/octet-stream'
            ]
        ];
        curl_setopt_array($ch, $config);

        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) {
            $this->_logErrors($ch);
            $this->tearDown($ch);
            return false;
        } else {
            $this->tearDown($ch);
            return true;
        }
    }

    /**
     * Summary of post
     *
     * @param mixed $post_url URL
     * @param mixed $data     DATA
     * 
     * @return bool|string     response
     */
    public function post($post_url, $data)
    {
        $ch = $this->setup();
        $config = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POSTREDIR => 3,
            CURLOPT_URL => $post_url,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            ]
        ];
        curl_setopt_array($ch, $config);
        $response = curl_exec($ch);
        if (!$response) {
            $this->_logErrors($ch);
        }
        $this->tearDown($ch);
        return $response;
    }

    /**
     * Summary of _logErrors
     * 
     * @param CurlHandle Curl handle
     *
     * @return void
     */
    private function _logErrors(CurlHandle $ch)
    {
        $info = $this->getInfo($ch);
        $errors = $this->getError($ch);
        $this->logger->warning("CURL INFO: " . print_r($info, true), ['app' => Application::APP_ID]);
        $this->logger->warning("CURL ERROR: " . print_r($errors, true), ['app' => Application::APP_ID]);
    }

    /**
     * Summary of getInfo
     * 
     * @param CurlHandle Curl handle
     *
     * @return mixed Info
     */
    public function getInfo(CurlHandle $ch): mixed
    {
        return curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    }

    /**
     * Summary of getError
     * 
     * @param CurlHandle Curl handle
     *
     * @return string Error Text
     */
    public function getError(CurlHandle $ch)
    {
        return curl_error($ch);
    }
}