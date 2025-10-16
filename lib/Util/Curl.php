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
    private CurlHandle $_ch;
    protected LoggerInterface $logger;

    /**
     * Summary of __construct
     *
     * @param \Psr\Log\LoggerInterface $logger Logger
     * @param bool                     $ssl    Enable SSL
     */
    public function __construct(LoggerInterface $logger, bool $ssl = true)
    {
        $this->_ch = curl_init();
        $defaults = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => [
                'Accept:application/json',
            ],
        ];
        curl_setopt_array($this->_ch, $defaults);
        $this->setSSL($ssl);
        $this->logger = $logger;
    }

    /**
     * Summary of __destruct
     */
    public function __destruct()
    {
        curl_close($this->_ch);
    }

    /**
     * Activate/Deactivate SSL
     * 
     * @param bool $ssl False/true
     * 
     * @return void
     */
    public function setSSL(bool $ssl)
    {
        $defaults = [];
        if ($ssl) {
            $defaults[CURLOPT_SSL_VERIFYHOST] = 2;
            $defaults[CURLOPT_SSL_VERIFYPEER] = 1;
        } else {
            $defaults[CURLOPT_SSL_VERIFYHOST] = 0;
            $defaults[CURLOPT_SSL_VERIFYPEER] = 0;
        }
        curl_setopt_array($this->_ch, $defaults);
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
        curl_setopt($this->_ch, CURLOPT_URL, $urlPath);
        if ($type != 'GET') {
            curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, $type);
        }
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($this->_ch);
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
        curl_setopt_array($this->_ch, $config);

        $response = curl_exec($this->_ch);
        curl_close($this->_ch);
        if (!$response) {
            $this->_logErrors();
            return false;
        } else {
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
        curl_setopt_array($this->_ch, $config);
        $response = curl_exec($this->_ch);
        if (!$response) {
            $this->_logErrors();
        }
        return $response;
    }

    /**
     * Summary of _logErrors
     *
     * @return void
     */
    private function _logErrors()
    {
        $info = $this->getInfo();
        $errors = $this->getError();
        $this->logger->warning("CURL INFO: " . print_r($info, true), ['app' => Application::APP_ID]);
        $this->logger->warning("CURL ERROR: " . print_r($errors, true), ['app' => Application::APP_ID]);
    }

    /**
     * Summary of getInfo
     * 
     * @return mixed Info
     */
    public function getInfo(): mixed
    {
        return curl_getinfo($this->_ch, CURLINFO_HEADER_SIZE);
    }

    /**
     * Summary of getError
     *
     * @return string Error Text
     */
    public function getError()
    {
        return curl_error($this->_ch);
    }
}