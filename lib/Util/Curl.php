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

namespace OCA\B2shareBridge\Util;
use CurlHandle;

use OCA\B2shareBridge\AppInfo\Application;
use Psr\Log\LoggerInterface;

class Curl
{
    private CurlHandle $ch;
    protected LoggerInterface $logger;
    public function __construct(LoggerInterface $logger, bool $ssl = true)
    {
        $this->ch = curl_init();
        $defaults = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => [
                'Accept:application/json',
            ],
        ];
        if (!$ssl) {
            $defaults[CURLOPT_SSL_VERIFYHOST] = 0;
            $defaults[CURLOPT_SSL_VERIFYPEER] = 0;
        }
        curl_setopt_array($this->ch, $defaults);
        $this->logger = $logger;
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    /**
     * Send a curl get request to $urlPath
     *
     * @param string $urlPath URL
     * @param string $type    REST type, e.g. GET, DELETE, PUT, ...
     * 
     * @return bool|string
     */
    public function request($urlPath, $type = 'GET'): bool|string
    {
        curl_setopt($this->ch, CURLOPT_URL, $urlPath);
        if ($type != 'GET') {
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $type);
        }
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($this->ch);
        if (!$output) {
            $this->_logErrors();
        }
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
        curl_setopt_array($this->ch, $config);

        $response = curl_exec($this->ch);
        curl_close($this->ch);
        if (!$response) {
            $this->_logErrors();
            return false;
        } else {
            return true;
        }
    }

    public function post($post_url, $data)
    {

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
        curl_setopt_array($this->ch, $config);
        $response = curl_exec($this->ch);
        if (!$response) {
            $this->_logErrors();
        }
        return $response;
    }

    private function _logErrors()
    {
        $info = $this->getInfo();
        $errors = $this->getError();
        $this->logger->debug("CURL INFO: " . print_r($info, true), ['app' => Application::APP_ID]);
        $this->logger->debug("CURL ERROR: " . print_r($errors, true), ['app' => Application::APP_ID]);
    }

    public function getInfo()
    {
        return curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
    }

    public function getError()
    {
        return curl_error($this->ch);
    }
}