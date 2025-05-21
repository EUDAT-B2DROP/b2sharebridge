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
use OCA\B2shareBridge\AppInfo\Application;
use OCA\B2shareBridge\Model\Server;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

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
    protected LoggerInterface $logger;
    protected CurlHandle $curl_client;
    protected string $file_upload_url;
    protected string $error_message;


    /**
     * Create object for actual upload
     *
     * @param IConfig         $_config ignored
     * @param LoggerInterface $logger  logger
     */
    public function __construct(IConfig $_config, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->curl_client = curl_init();
    }

    /**
     * Activate or deactive SSL check
     * 
     * @param bool $checkSsl check SSL
     * 
     * @return void
     */
    public function setCheckSSL(bool $checkSsl)
    {
        $defaults = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HEADER => 1,
        );
        if (!$checkSsl) {
            $defaults[CURLOPT_SSL_VERIFYHOST] = 0;
            $defaults[CURLOPT_SSL_VERIFYPEER] = 0;
        }
        curl_setopt_array($this->curl_client, $defaults);
    }

    /**
     * Get the portion of the file upload URL
     * filename + access_token still need to be pasted
     *
     * @return string the file_upload_url for the files bucket
     */
    public function getFileUploadUrlPart(): string
    {
        return $this->file_upload_url;
    }

    /**
     * Get the error message from HTTP service
     *
     * @return string error message from the http interaction
     */
    public function getErrorMessage(): string
    {
        return $this->error_message;
    }


    /**
     * Publish to url via post, use uuid for filename. Use a token and set expect
     * to empty just as a workaround for local issues
     *
     * @param string $token       users access token
     * @param string $community   id of community metadata schema, defaults to EUDAT
     * @param string $open_access publish as open access, defaults to false
     * @param string $title       actual title of the deposit
     * @param Server $server      b2share server
     *
     * @return string  file URL in b2access
     */
    public function create(
        string $token,
        string $community,
        string $open_access,
        string $title,
        Server $server
    ): string {
        //now settype("false","boolean") evaluates to true, so:
        $b_open_access = false;
        if ($open_access === "true") {
            $b_open_access = true;
        }
        $data = json_encode(
            [
                'community' => $community,
                'titles' => [
                    [
                        'title' => $title
                    ]
                ],
                'open_access' => $b_open_access
            ]
        );

        $version_slash = $server->getVersion() == 2 ? '/' : '';
        $post_url = "{$server->getPublishUrl()}/api/records{$version_slash}?access_token={$token}";

        $config = array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POSTREDIR => 3,
            CURLOPT_URL => $post_url,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );
        curl_setopt_array($this->curl_client, $config);
        $response = curl_exec($this->curl_client);
        if (!$response) {
            return "";
        } else {
            $header_size = curl_getinfo($this->curl_client, CURLINFO_HEADER_SIZE);
            $body = substr($response, $header_size);
            $body_encoded = mb_convert_encoding($body, 'UTF-8', mb_list_encodings());
            $results = json_decode($body_encoded, false);
            if (property_exists($results, 'links')
                && property_exists($results->links, 'self')
                && property_exists($results->links, 'files')
            ) {
                $this->file_upload_url
                    = $results->links->files;
                if ($server->getVersion() == 2) {
                    return str_replace(
                        'draft',
                        'edit',
                        str_replace('/api', '', $results->links->self)
                    );
                } else {
                    $edit_url = "{$server->getPublishUrl()}/uploads/{$results->id}";
                    return $edit_url;
                }
            } else {
                $this->error_message = "Something went wrong in uploading.";
                if (property_exists($results, 'status')) {
                    if ($results->status === 403) {
                        $this->error_message = "403 - Authorization Required";
                    }
                }
                return "";
            }
        }
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
        $this->curl_client = curl_init();

        $config2 = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $file_upload_url,
            CURLOPT_INFILE => $filehandle,
            CURLOPT_INFILESIZE => $filesize,
            CURLOPT_PUT => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_HEADER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPHEADER => [
                'Accept:application/json',
                'Content-Type: application/octet-stream'
            ]
        ];
        curl_setopt_array($this->curl_client, $config2);

        $response = curl_exec($this->curl_client);
        curl_close($this->curl_client);
        if (!$response) {
            $info = curl_getinfo($this->curl_client);
            $errors = curl_error($this->curl_client);
            $this->logger->debug("CURL INFO: " . print_r($info, true), ['app' => Application::APP_ID]);
            $this->logger->debug("CURL ERROR: " . print_r($errors, true), ['app' => Application::APP_ID]);
            return false;
        } else {
            return true;
        }
    }
}
